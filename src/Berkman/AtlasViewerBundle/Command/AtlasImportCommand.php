<?php
namespace Berkman\AtlasViewerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Finder\Finder;

use Doctrine\Common\Collections\ArrayCollection;

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
            ->addOption('send-email', 'm', InputOption::VALUE_NONE, 'Whether or not to send an email to the atlas owner when the process completes')
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Remove all pages from atlas before adding new pages')
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

        // Remove existing pages from atlas
        if ($input->hasOption('overwrite')) {
            $pages = $atlas->getPages();
            foreach($pages as $page) {
                $em->remove($page);
            }
            $atlas->setPages(new ArrayCollection());
            $em->flush();
        }


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
            $errorMsg = 'We couldn\'t download the atlas from: ' . $importUrl . ".\r\n\r\n"
                . "The error reported was:\r\n\r\n" . $process->getErrorOutput();
            $this->sendErrorEmail($errorMsg, $atlas);
            throw new \ErrorException('Could not fetch an atlas from: ' . $importUrl, self::DOWNLOAD_FAILED_CODE);
        }
        $output->writeln('File download complete.');

        // Unzip the successfully downloaded atlas
        $output->writeln('Starting unzip...');
        $process = new Process('unzip atlas.zip -d ' . $extractedDir);
        $process->setTimeout(self::UNZIP_TIMEOUT);
        $process->run();
        if (!$process->isSuccessful()) {
            $errorMsg = 'We couldn\'t extract the zip downloaded from: ' . $importUrl . ".\r\n\r\n"
                . "The error reported was:\r\n\r\n" . $process->getErrorOutput();
            $this->sendErrorEmail($errorMsg, $atlas);
            throw new \ErrorException('Could not extract atlas from: ' . $importUrl, self::UNZIP_FAILED_CODE);
        }
        $output->writeln('Unzip complete');

        // Find and count the actual map files from the zip
        $output->writeln('Starting file parsing...');
        $finder = new Finder();
        $metadataFinder = new Finder();
        $finder->files()->in($extractedDir)->name('*.jp2')->name('*.tif');
        $metadataFinder->files()->in($extractedDir)->name('*.xml');
        $count = 0;
        $pageTitle = '';
        $pageMetadata = array();
        foreach($finder as $file) {
            $count++;
            /*foreach ($metadataFinder as $possibleMetadataFile) {
                $distance = levenshtein($possibleMetadataFile->getBasename($possibleMetadataFile->getExtension()), $file->getBasename($file->getExtension())) < 5;
                $output->writeln('Distance: ' . $distance);
                if ($distance) {
                    $metadataFile = $possibleMetadataFile->getRealPath();
                }
            }*/
            $metadataFile = $file->getPath() . '/' . $file->getBasename($file->getExtension()) . 'xml';
            if (file_exists($metadataFile)) {
                $doc = new \DOMDocument();
                $doc->recover = true;
                $doc->load($metadataFile);
                $xpath = new \DOMXpath($doc);
                $pageTitle = $xpath->query('//citeinfo/title')->item(0)->textContent;
                $pubDate = $xpath->query('//citeinfo/pubdate')->item(0)->textContent;
                $recordUrl = $xpath->query('//citeinfo/onlink')->item(0)->textContent;
                $placeSystem = $xpath->query('//placekt')->item(0)->textContent;
                $places = $xpath->query('//placekey');
                $placeNames = array();
                foreach ($places as $place) {
                    $placeNames[] = $place->textContent;
                }
                $pageMetadata = array(
                    'Title' => $pageTitle,
                    'Publication Date' => $pubDate,
                    'Record URL' => '<a href="' . $recordUrl . '">' . $recordUrl . '</a>',
                    'Place Naming System' => $placeSystem,
                    'Places' => implode(' - ', $placeNames)
                );
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
        }
        $em->persist($atlas);
        $em->flush();

        $output->writeln('Found and created ' . $count . ' pages.');

        if ($input->getOption('send-email')) {
            $mailer = $this->getContainer()->get('mailer');
            $successMessage = 'We downloaded the atlas, extracted it, and created ' . $count . ' page';
            $successMessage .= $count > 1 ? 's' : '';
            $successMessage .= ".\r\n\r\nTo edit the atlas or start tile generation, visit: " 
                . $this->getContainer()->get('router')->generate('atlas_edit', array( 'id' => $atlas->getId()), true);
            $message = \Swift_Message::newInstance()
                ->setSubject('Digital Atlas Viewer - Task Completed')
                ->setFrom('jclark.symfony@gmail.com')
                ->setTo($atlas->getOwner()->getEmail())
                ->setBody(
                    $this->getContainer()->get('templating')->render(
                        'BerkmanAtlasViewerBundle:Email:successEmail.txt.twig',
                        array(
                            'name' => $atlas->getOwner()->getUsername(),
                            'message' => $successMessage
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
            ->setSubject('Digital Atlas Viewer - Atlas Import Failure')
            ->setFrom('jclark.symfony@gmail.com')
            ->setTo($atlas->getOwner()->getEmail())
            ->setBody(
                $this->getContainer()->get('templating')->render(
                    'BerkmanAtlasViewerBundle:Email:errorEmail.txt.twig',
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
