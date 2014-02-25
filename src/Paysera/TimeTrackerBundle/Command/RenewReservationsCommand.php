<?php

namespace Paysera\TimeTrackerBundle\Command;

use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Paysera\TimeTrackerBundle\Service\Tracker;

/**
 * Command to renew reservations for needed entries
 */
class RenewReservationsCommand extends ContainerAwareCommand
{
     /**
     * Command configure
     *
     * @see \Symfony\Component\Console\Command.Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('paysera:renew-entry-reservations')
            ->setDescription('Renews reservations for entries');
    }

    /**
     * Execute this command
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|void
     * @see \Symfony\Component\Console\Command.Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Tracker $service */
        $service = $this->getContainer()->get('paysera_time_tracker.tracker');
        $service->renewReservations();
    }

}