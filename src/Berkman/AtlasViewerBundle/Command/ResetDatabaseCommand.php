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

class ResetDatabaseCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('atlas_viewer:database:reset')
            ->setDescription('Drop the schema, recreate it, load fixtures, and initialize the ACL.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
            $command = $this->getApplication()->find('doctrine:schema:drop');
            $arguments = array(
                'command' => 'doctrine:schema:drop',
                '--force' => true
            );
            $input = new ArrayInput($arguments);
            $command->run($input, $output);

            $command = $this->getApplication()->find('doctrine:query:sql');
            $arguments = array(
                'command' => 'doctrine:schema:drop',
                'sql' => 'DROP TABLE IF EXISTS acl_classes, acl_entries, acl_object_identities, acl_object_identity_ancestors, acl_security_identities CASCADE; DROP SEQUENCE IF EXISTS acl_entries_id_seq, acl_security_identities_id_seq;'
            );
            $input = new ArrayInput($arguments);
            $command->run($input, $output);

            $command = $this->getApplication()->find('doctrine:schema:create');
            $arguments = array(
                'command' => 'doctrine:schema:create'
            );
            $input = new ArrayInput($arguments);
            $command->run($input, $output);

            $command = $this->getApplication()->find('doctrine:fixtures:load');
            $arguments = array(
                'command' => 'doctrine:fixtures:load'
            );
            $input = new ArrayInput($arguments);
            $command->run($input, $output);

            $command = $this->getApplication()->find('init:acl');
            $arguments = array(
                'command' => 'init:acl'
            );
            $input = new ArrayInput($arguments);
            $command->run($input, $output);
    }
}
