<?php
namespace Berkman\AtlasViewerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class PageTileGenCommand extends ContainerAwareCommand
{
    const NO_WORKING_DIR_FAILED_CODE = 3;
    const LOCATE_MAPFILE_FAILED_CODE = 4;
    const TILE_GEN_TIMEOUT = 3600; // 1 hour
    const TILE_GEN_FAILED_CODE = 5;

    protected function configure()
    {
        $this
            ->setName('atlas_viewer:page:generate_tiles')
            ->setDescription('Create tiles from a page')
            ->addArgument('page-id', InputArgument::REQUIRED, 'The ID of the page')
            ->addArgument('working-dir', InputArgument::REQUIRED, 'The directory in which to work')
            ->addArgument('output-dir', InputArgument::REQUIRED, 'The web-accessible directory')
            ->addArgument('alert-email', InputArgument::OPTIONAL, 'An email address to send alerts to')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        // Get the page
        $output->writeln('Finding the page...');
        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $page = $em->find('BerkmanAtlasViewerBundle:Page', $input->getArgument('page-id'));
        if (!$page) {
            throw new \ErrorException('Could not find page.');
        }
        $output->writeln('Found page.');

        // Check and prepare the working directory
        $output->writeln('Setting up the working directory...');
        $workingDir = $input->getArgument('working-dir') . '/' . $page->getAtlas()->getId();
        $outputDir = $input->getArgument('output-dir') . '/' . $page->getAtlas()->getId() . '/' . $page->getId();
        $mapFile = $workingDir . '/extracted/' . $page->getFilename();
        $tmpTileDir = $workingDir . '/tiles/' . $page->getId();
        if (!file_exists($workingDir)) {
            throw new \ErrorException('Atlas working directory does not exist: ' . $workingDir, self::NO_WORKING_DIR_FAILED_CODE);
        }
        if (!file_exists($mapFile)) {
            throw new \ErrorException('Could not locate map file: ' . $page->getFilename(), self::LOCATE_MAPFILE_FAILED_CODE);
        }
        if (!file_exists($tmpTileDir)) {
            mkdir($tmpTileDir, 0777, true);
        }
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
        else {
            $this->emptyDir($outputDir);
        } 
        $output->writeln('Working directory set up.');

        // Run the script to generate tiles
        $output->writeln('Generating tiles...');
        $command = 'gdal2tiles.py -n -w none -s ' . escapeshellarg('EPSG:' . $page->getEpsgCode()) . ' ' . escapeshellarg($mapFile) . ' ' . escapeshellarg($tmpTileDir);

        $process = new Process($command);
        $process->setTimeout(self::TILE_GEN_TIMEOUT);
        $process->run();
        if ($process->isSuccessful()) {
            rename($tmpTileDir, $outputDir);
        }
        else {
            throw new \ErrorException('Could not make tiles for file: ' . $mapFile, self::TILE_GEN_FAILED_CODE);
        }
        $output->writeln('Tile generation complete.');

        // Figure out bounds and zoom levels for the atlas page
        $output->writeln('Figuring out bounds and zoom levels...');
        $doc = new \DOMDocument();
        $doc->load($outputDir . '/tilemapresource.xml');
        $xpath = new \DOMXpath($doc);
        $bbox = $xpath->query('//BoundingBox')->item(0);
        $bounds = array(
            'minx' => $bbox->getAttribute('minx'),
            'miny' => $bbox->getAttribute('miny'),
            'maxx' => $bbox->getAttribute('maxx'),
            'maxy' => $bbox->getAttribute('maxy')
        );
        $zoomLevels = array();
        $tileSets = $xpath->query('//TileSet');
        foreach($tileSets as $tileSet) {
            $zoomLevels[] = $tileSet->getAttribute('order');
        }

        // Create the new page
        $page->setBoundingBox($bounds);
        $page->setMinZoomLevel(min($zoomLevels));
        $page->setMaxZoomLevel(max($zoomLevels));
        $output->writeln('Bounds and zoom levels acquired.');
        $em->persist($page);
        $em->flush();
        $output->writeln('Finished');
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
