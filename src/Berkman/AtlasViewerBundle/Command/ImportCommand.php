<?php
namespace Berkman\AtlasViewerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Berkman\AtlasViewerBundle\Entity\Page;

class ImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('atlas_viewer:import')
            ->setDescription('Download a zip, extract the jp2 and j2w files, and create the tiles.')
            ->addArgument('atlas-id', InputArgument::REQUIRED, 'The ID of the atlas to which these pages should be added')
            ->addArgument('url', InputArgument::REQUIRED, 'The URL of the ZIP file containing the atlas maps')
            ->addArgument('epsg-code', InputArgument::REQUIRED, 'The EPSG code of the maps in the atlas')
            ->addArgument('output-dir', InputArgument::REQUIRED, 'The output directory for the tiles')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Finding the atlas...');
        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $atlas = $em->getRepository('BerkmanAtlasViewerBundle:Atlas')->find($input->getArgument('atlas-id'));
        if (!$atlas) {
            throw new \ErrorException('Could not find atlas.');
        }
        $output->writeln('Found atlas');

        $url = $input->getArgument('url');
        $output->writeln('Starting file download...');
        exec('wget -qO atlas.zip ' . escapeshellarg($url));
        $output->writeln('File download complete');
        $output->writeln('Starting unzip...');

        exec('rm -rf maps & mkdir maps');
        exec('unzip atlas.zip -d maps');
        $output->writeln('Unzip complete');

        $files = scandir('maps');
        $maps = array();
        foreach($files as $file) {
            if (is_file('maps/'.$file) && pathinfo('maps/'.$file, PATHINFO_EXTENSION) == 'jp2') {
                $maps[] = $file;
            }
        }
        $output->writeln('Starting to generate tiles for ' . count($maps) . ' maps...');

        if (!is_dir($input->getArgument('output-dir'))) {
            mkdir($input->getArgument('output-dir'));
        }
        $i = 1;
        foreach($maps as $map) {
            $outputDir = $input->getArgument('output-dir') . '/' . $i;
            if (!is_dir($outputDir)) {
                mkdir($outputDir);
            }
            $command = 'gdal2tiles.py -s ' . escapeshellarg('EPSG:' . $input->getArgument('epsg-code')) . ' -n -w none ' . escapeshellarg('maps/'.$map) . ' ' . escapeshellarg($outputDir) . ' &> /dev/null';
            exec($command);
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

            rename($outputDir, $input->getArgument('output-dir') . '/' . $page->getId());

            $output->writeln('Finished ' . $i . '/' . count($maps));
            $i++;
        }

        exec('rm -rf maps atlas.zip');
        $output->writeln('Finished');
    }
}
