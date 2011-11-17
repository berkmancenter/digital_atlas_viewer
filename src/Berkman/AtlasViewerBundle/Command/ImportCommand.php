<?php
namespace Berkman\AtlasViewerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use Berkman\AtlasViewerBundle\Entity\Page;

class ImportCommand extends ContainerAwareCommand
{
    const TEMP_DIR = '/DAV/tmp';
    const TILE_DIR = '/DAV/web/tiles';
    const MAP_DIR_NAME = 'maps';

    protected function configure()
    {
        $this
            ->setName('atlas_viewer:atlas:import')
            ->setDescription('Download a zip, extract the jp2 and j2w files, and create the tiles.')
            ->addArgument('atlas-id', InputArgument::REQUIRED, 'The ID of the atlas to which these pages should be added')
            ->addArgument('url', InputArgument::REQUIRED, 'The URL of the ZIP file containing the atlas maps')
            ->addArgument('epsg-code', InputArgument::REQUIRED, 'The EPSG code of the maps in the atlas')
            ->addArgument('doc-root', InputArgument::REQUIRED, 'The output directory for the tiles')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Prepare the current working directory
        $cwd = $input->getArgument('doc-root') . self::TEMP_DIR;
        chdir($cwd);
        $this->emptyDir($cwd);
        mkdir(self::MAP_DIR_NAME);

        // Get the atlas to which these pages will be attached.
        $output->writeln('Finding the atlas...');
        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $atlas = $em->getRepository('BerkmanAtlasViewerBundle:Atlas')->find($input->getArgument('atlas-id'));
        if (!$atlas) {
            throw new \ErrorException('Could not find atlas.');
        }
        $output->writeln('Found atlas');

        // Download the atlas zip
        $url = $input->getArgument('url');
        $output->writeln('Starting file download...');
        $process = new Process('wget -qO atlas.zip ' . escapeshellarg($url));
        $process->setTimeout(30 * 60);
        $process->run();
        if (!$process->isSuccessful()) {
            $this->sendErrorEmail('We couldn\'t fetch the atlas from the URL specified.', $atlas);
            throw new \ErrorException('Could not fetch an atlas from: ' . $url);
        }
        $output->writeln('File download complete');
        $output->writeln('Starting unzip...');

        // Unzip the successfully downloaded atlas
        $process = new Process('unzip atlas.zip -d ' . self::MAP_DIR_NAME);
        $process->setTimeout(10 * 60);
        $process->run();
        if (!$process->isSuccessful()) {
            $this->sendErrorEmail('We couldn\'t successfully extract the atlas.  Are you sure it\'s a correctly formed ZIP file?', $atlas);
            throw new \ErrorException('Could not extract atlas from: ' . $url);
        }
        $output->writeln('Unzip complete');

        // Find and count the actual map files from the zip
        $files = scandir(self::MAP_DIR_NAME);
        $maps = array();
        foreach($files as $file) {
            if (is_file(self::MAP_DIR_NAME . '/' . $file) && in_array(pathinfo(self::MAP_DIR_NAME . '/' . $file, PATHINFO_EXTENSION), array('jp2', 'tif'))) {
                $maps[] = $file;
            }
        }

        // Create a tile directory for this particular atlas
        $tilesDir = $input->getArgument('doc-root') . self::TILE_DIR . '/' . $input->getArgument('atlas-id');
        if (!is_dir($tilesDir)) {
            mkdir($tilesDir);
        }

        // Generate tiles
        $output->writeln('Starting to generate tiles for ' . count($maps) . ' maps...');
        $i = 1;
        foreach($maps as $map) {
            // Make temporary directory to store tiles
            $outputDir = $tilesDir . '/tmp/' . $i;
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            // Run the script to generate tiles
            $command = 'gdal2tiles.py -s ' . escapeshellarg('EPSG:' . $input->getArgument('epsg-code')) .
                       ' -n -w none ' . escapeshellarg(self::MAP_DIR_NAME . '/' . $map) .
                       ' ' . escapeshellarg($outputDir);
            $process = new Process($command);
            $process->setTimeout(2 * 60 * 60);
            $process->run();
            if (!$process->isSuccessful()) {
                $this->sendErrorEmail('We couldn\'t create tiles for one of the pages. The offending filename is: ' . $map . '.  \\nThe tile generation script had this to say:\\n' . $process->getErrorOutput(), $atlas);
                throw new \ErrorException('Could not make tiles for file: ' . $map);
            }

            // Figure out bounds and zoom levels for the atlas page
            $doc = new \DOMDocument();
            $doc->load($outputDir . '/tilemapresource.xml');
            $xpath = new \DOMXpath($doc);
            $bounds = array();
            $bbox = $xpath->query('//BoundingBox')->item(0);
            $bounds['minx'] = $bbox->getAttribute('minx');
            $bounds['miny'] = $bbox->getAttribute('miny');
            $bounds['maxx'] = $bbox->getAttribute('maxx');
            $bounds['maxy'] = $bbox->getAttribute('maxy');

            $zoomLevels = array();
            $tileSets = $xpath->query('//TileSet');
            foreach($tileSets as $tileSet) {
                $zoomLevels[] = $tileSet->getAttribute('order');
            }

            // Create the new page
            $page = new Page();
            $page->setName($i);
            $page->setEpsgCode($input->getArgument('epsg-code'));
            $page->setMetadata(array('Title' => 'My Map'));
            $page->setBoundingBox($bounds);
            $page->setMinZoomLevel(min($zoomLevels));
            $page->setMaxZoomLevel(max($zoomLevels));
            $page->setAtlas($atlas);
            $em->persist($page);
            $em->flush();

            // Move the tiles from the tmp tile directory to their final home
            rename($outputDir, $tilesDir . '/' . $page->getId());

            $output->writeln('Finished ' . $i . '/' . count($maps));
            $i++;
        }

        $this->emptyDir(self::TEMP_DIR);
        $output->writeln('Finished');

        $mailer = $this->getContainer()->get('mailer');
        $message = \Swift_Message::newInstance()
            ->setSubject('Atlas Viewer - Tile Generation Completed')
            ->setFrom('jclark.symfony@gmail.com')
            ->setTo($atlas->getOwner()->getEmail())
            ->setBody(
                $this->getContainer()->get('templating')->render(
                    'BerkmanAtlasViewerBundle:Importer:successEmail.txt.twig',
                    array(
                        'name' => $atlas->getOwner()->getUsername(),
                        'atlas_id' => $atlas->getId()
                    )
                )
            )
        ;
        $mailer->send($message);
    }

    private function sendErrorEmail($errorMsg, $atlas) {
        $mailer = $this->getContainer()->get('mailer');
        $message = \Swift_Message::newInstance()
            ->setSubject('Atlas Viewer - Tile Generation Failure')
            ->setFrom('jclark.symfony@gmail.com')
            ->setTo($atlas->getOwner()->getEmail())
            ->setBody(
                $this->getContainer()->get('templating')->render(
                    'BerkmanAtlasViewerBundle:Importer:errorEmail.txt.twig',
                    array(
                        'name' => $atlas->getOwner()->getUsername(),
                        'error' => $errorMsg
                    )
                )
            )
        ;
        $mailer->send($message);
    }

    private function emptyDir($dir, $remove = false) { 
        if (is_dir($dir)) { 
            $objects = scandir($dir); 
            foreach ($objects as $object) { 
                if ($object != "." && $object != "..") { 
                    if (filetype($dir."/".$object) == "dir") {
                        $this->emptyDir($dir."/".$object, true);
                    }
                    else {
                        unlink($dir."/".$object); 
                    }
                } 
            } 
            reset($objects); 
            if ($remove == true) {
                rmdir($dir);
            }
        } 
    } 
}
