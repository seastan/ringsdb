<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class FixCanonicalNamesCommand extends ContainerAwareCommand {
    protected function configure() {
        $this->setName('app:fix-canonical-names')
             ->setDescription('Fix canonical names for scenarios');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $texts = $this->getContainer()->get('texts');
        $count = 0;

        $scenarios = $em->getRepository('AppBundle:Scenario')->findAll();
        foreach ($scenarios as $scenario) {
            $nameCanonical = $texts->slugify($scenario->getName());

            if ($nameCanonical !== $scenario->getNameCanonical()) {
                $scenario->setNameCanonical($nameCanonical);
                $count++;
            }
        }

        $em->flush();
        $output->writeln(date('c') . " Fixed $count scenario canonical names.");
    }
}
