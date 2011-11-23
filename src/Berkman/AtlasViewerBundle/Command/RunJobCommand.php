<?php
namespace Berkman\AtlasViewerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class RunJobCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('atlas_viewer:job:run')
            ->setDescription('Run the next pending job in the job queue')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $repo = $em->getRepository('BerkmanAtlasViewerBundle:Job');

        // I think we're using lockrun, so I don't have to worry about keeping track of this
        /*$runningJobs = $repo->createQueryBuilder('job')
            ->select('COUNT(job.pid)')
            ->where('job.pid IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();*/

        //if ($runningJobs == 0) {
            $job = $repo->createQueryBuilder('job')
                ->select('job')
                ->orderBy('job.id', 'ASC')
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();

            if ($job) {
                $process = new Process($job->getCommand());
                $process->setTimeout($job->getTimeout());
                $process->run(function ($type, $buffer) use ($output) {
                    if ('err' === $type) {
                        $output->writeln('ERR > '.$buffer);
                    } else {
                        $output->write($buffer);
                    }
                });
                if ($process->isSuccessful()) {
                    $em->remove($job);
                    $em->flush();
                }
                else {
                    $em->remove($job);
                    $em->flush();
                }
            }

            /*$pid = shell_exec($job->getCommand());
            $job->setPid($pid);
            $em->persist($job);
            $em->flush();*/
        //}
    }
}
