<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class FixSignaturesCommand extends ContainerAwareCommand {
    protected function configure() {
        $this->setName('app:fix-signatures')
             ->setDescription('Fix canonical names for decklists');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $count = 0;

        /* @var $decklists \AppBundle\Entity\Decklist[] */
        $decklists = $em->getRepository('AppBundle:Decklist')->findAll();
        foreach ($decklists as $decklist) {
            /* @var $decklist \AppBundle\Entity\Decklist */

            $content = [
                'main' => $decklist->getSlots()->getContent(),
                'side' => $decklist->getSideslots()->getContent(),
            ];
            $this_content = json_encode($content);
            $this_signature = md5($this_content);

            if ($this_signature !== $decklist->getSignature()) {
                $decklist->setSignature($this_signature);
                $count++;
            }
        }

        $em->flush();
        $output->writeln(date('c') . " Fixed $count decklist signatures.");
    }
}
