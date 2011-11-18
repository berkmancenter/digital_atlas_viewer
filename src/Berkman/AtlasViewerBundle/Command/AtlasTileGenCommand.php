<?php
namespace Berkman\AtlasViewerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use Berkman\AtlasViewerBundle\Entity\Page;

class AtlasTileGenCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('atlas_viewer:atlas:generate_tiles')
            ->setDescription('Create tiles for every page in an atlas')
            ->addArgument('atlas-id', InputArgument::REQUIRED, 'The ID of the atlas')
            ->addArgument('working-dir', InputArgument::REQUIRED, 'The directory in which to work')
            ->addArgument('output-dir', InputArgument::REQUIRED, 'The web-accessible directory')
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

        $pages = $atlas->getPages();
        $numPages = count($pages);
        $output->writeln('Generating tiles for ' . $numPages . ' pages...');

        $i = 1;
        foreach ($pages as $page) {
            $command = $this->getApplication()->find('atlas_viewer:page:generate_tiles');

            $arguments = array(
                'command' => 'atlas_viewer:page:generate_tiles',
                'page-id' => $page->getId(),
                'working-dir' => $input->getArgument('working-dir'),
                'output-dir'  => $input->getArgument('output-dir')
            );

            $input = new ArrayInput($arguments);
            $command->run($input, $output);
            $output->writeln('Finished page ' . $i . '/' . $numPages);
            $i++;
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
