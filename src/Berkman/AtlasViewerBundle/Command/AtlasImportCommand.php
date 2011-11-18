<?php
namespace Berkman\AtlasViewerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Finder\Finder;

use Berkman\AtlasViewerBundle\Entity\Page;

class AtlasImportCommand extends ContainerAwareCommand
{

    const DOWNLOAD_TIMEOUT = 3600; // 1 hour
    const DOWNLOAD_FAILED_CODE = 1;
    const UNZIP_TIMEOUT = 1800; // 30 minutes
    const UNZIP_FAILED_CODE = 2;

    protected function configure()
    {
        $this
            ->setName('atlas_viewer:atlas:import')
            ->setDescription('Download a zip, extract the map files, and generate pages from the maps')
            ->addArgument('atlas-id', InputArgument::REQUIRED, 'The ID of the atlas to import')
            ->addArgument('working-dir', InputArgument::REQUIRED, 'The directory in which to work')
            ->addArgument('alert-email', InputArgument::OPTIONAL, 'An email address to send alerts to')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get the atlas to which these pages will be attached.
        $output->writeln('Finding the atlas...');
        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $atlas = $em->find('BerkmanAtlasViewerBundle:Atlas', $input->getArgument('atlas-id'));
        if (!$atlas) {
            throw new \ErrorException('Could not find atlas.');
        }
        $output->writeln('Found atlas.');

        // Prepare the current working directory
        $workingDir = $input->getArgument('working-dir') . '/' . $atlas->getId();
        $extractedDir = 'extracted';
        if (file_exists($workingDir)) {
            $this->emptyDir($workingDir); 
        }
        else {
            mkdir($workingDir);
        }
        chdir($workingDir);
        mkdir($extractedDir);

        // Download the atlas zip
        $importUrl = $atlas->getUrl();
        $output->writeln('Starting file download...');
        $process = new Process('wget -qO atlas.zip ' . escapeshellarg($importUrl));
        $process->setTimeout(self::DOWNLOAD_TIMEOUT);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \ErrorException('Could not fetch an atlas from: ' . $url, self::DOWNLOAD_FAILED_CODE);
        }
        $output->writeln('File download complete.');

        // Unzip the successfully downloaded atlas
        $output->writeln('Starting unzip...');
        $process = new Process('unzip atlas.zip -d ' . $extractedDir);
        $process->setTimeout(self::UNZIP_TIMEOUT);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \ErrorException('Could not extract atlas from: ' . $url, self::UNZIP_FAILED_CODE);
        }
        $output->writeln('Unzip complete');

        // Find and count the actual map files from the zip
        $output->writeln('Starting file parsing...');
        $finder = new Finder();
        $finder->files()->in($extractedDir)->name('*.jp2')->name('*.tif');
        $count = 1;
        $pageTitle = '';
        $pageMetadata = array();
        foreach($finder as $file) {
            $metadataFile = $file->getBasename($file->getExtension()) . 'xml';
            if (file_exists($metadataFile)) {
                $doc = new \DOMDocument();
                $doc->load($metadataFile);
                $xpath = new \DOMXpath($doc);
                $pageTitle = $xpath->query('//citeinfo/title')->item(0)->textContent;
                $pageMetadata = array('More Metadata' => 'foobar');
            }
            else {
                $pageTitle = $count;
            }
            $page = new Page();
            $page->setName($pageTitle);
            $page->setEpsgCode($atlas->getDefaultEpsgCode());
            $page->setMetadata($pageMetadata);
            $page->setFilename($file->getFilename());
            $page->setAtlas($atlas);
            $em->persist($page);
            $count++;
        }
        $em->persist($atlas);
        $em->flush();

        $output->writeln('Found and created ' . iterator_count($finder) . ' pages.');

        if ($input->hasArgument('alert-email')) {
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
