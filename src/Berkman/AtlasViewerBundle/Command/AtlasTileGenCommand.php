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
            ->addOption('send-email', 'm', InputOption::VALUE_NONE, 'Whether or not to send an email to the atlas owner when the process completes')
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

        $i = 0;
        foreach ($pages as $page) {
            $i++;
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
        }

        $output->writeln('Generating tiles to normalize zoom levels across atlas...');
        $i = 0;
        foreach ($pages as $page) {
            $i++;
            if ($page->getMinZoomLevel() - $atlas->getMinZoomLevel() > 0) {
                $command = $this->getApplication()->find('atlas_viewer:page:generate_tiles');
                $newZoomLevels = $atlas->getMinZoomLevel() . ' - ' . $page->getMinZoomLevel();

                $arguments = array(
                    'command' => 'atlas_viewer:page:generate_tiles',
                    'page-id' => $page->getId(),
                    'working-dir' => $input->getArgument('working-dir'),
                    'output-dir'  => $input->getArgument('output-dir'),
                    '-z' => $newZoomLevels
                );

                $input = new ArrayInput($arguments);
                $command->run($input, $output);
            }
            $output->writeln('<comment>Finished page ' . $i . '/' . $numPages.'</comment>');
        }
        $output->writeln('<info>Finished</info>');

        if ($input->hasOption('send-email')) {
            $mailer = $this->getContainer()->get('mailer');
            $successMessage = 'We successfully generated tiles for ' . $i . ' page';
            $successMessage .= $i > 1 ? 's' : '';
            $successMessage .= ".\r\n\r\nTo view the atlas, visit: " 
                . $this->getContainer()->get('router')->generate('atlas_show', array( 'id' => $atlas->getId()), true);
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
}
