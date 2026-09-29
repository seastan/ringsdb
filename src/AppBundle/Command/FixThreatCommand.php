<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixThreatCommand extends Command {
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em) {
        parent::__construct();
        $this->em = $em;
    }

    /**
     * @return void
     */
    protected function configure() {
        $this->setName('app:fix-threat')
             ->setDescription('Fix starting threat for decklists');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->em;

        $count = 0;

        /* @var $decklists \AppBundle\Entity\Decklist[] */
        $decklists = $em->getRepository('AppBundle:Decklist')->findAll();
        foreach ($decklists as $decklist) {
            /* @var $decklist \AppBundle\Entity\Decklist */
            $decklist->setStartingThreat($decklist->getSlots()->getStartingThreat());
            $count++;
        }

        $em->flush();
        $output->writeln(date('c') . " Fixed $count starting threats.");

        return 0;
    }
}
