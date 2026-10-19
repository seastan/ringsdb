<?php

namespace App\Command;

use App\Repository\DecklistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixSignaturesCommand extends Command {
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var DecklistRepository
     */
    private $decklistRepository;

    public function __construct(EntityManagerInterface $em, DecklistRepository $decklistRepository) {
        parent::__construct();
        $this->em = $em;
        $this->decklistRepository = $decklistRepository;
    }

    /**
     * @return void
     */
    protected function configure() {
        $this->setName('app:fix-signatures')
             ->setDescription('Fix canonical names for decklists');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->em;

        $count = 0;

        /* @var $decklists \App\Entity\Decklist[] */
        $decklists = $this->decklistRepository->findAll();
        foreach ($decklists as $decklist) {
            /* @var $decklist \App\Entity\Decklist */

            $content = [
                'main' => $decklist->getSlots()->getContent(),
                'side' => $decklist->getSideslots()->getContent(),
            ];
            $this_content = json_encode($content);
            $this_signature = md5((string) $this_content);

            if ($this_signature !== $decklist->getSignature()) {
                $decklist->setSignature($this_signature);
                $count++;
            }
        }

        $em->flush();
        $output->writeln(date('c') . " Fixed $count decklist signatures.");

        return 0;
    }
}
