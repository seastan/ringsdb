<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Decklist;
use App\Repository\DecklistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixSignaturesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    private DecklistRepository $decklistRepository;

    public function __construct(EntityManagerInterface $entityManager, DecklistRepository $decklistRepository)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->decklistRepository = $decklistRepository;
    }

    protected function configure(): void
    {
        $this->setName('app:fix-signatures')
             ->setDescription('Fix canonical names for decklists');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = 0;

        /* @var $decklists Decklist[] */
        $decklists = $this->decklistRepository->findAll();
        foreach ($decklists as $decklist) {
            /* @var $decklist Decklist */

            $content = [
                'main' => $decklist->getSlots()->getContent(),
                'side' => $decklist->getSideslots()->getContent(),
            ];
            $this_content = json_encode($content);
            $this_signature = md5((string) $this_content);

            if ($this_signature !== $decklist->getSignature()) {
                $decklist->setSignature($this_signature);
                ++$count;
            }
        }

        $this->entityManager->flush();
        $output->writeln(date('c')." Fixed $count decklist signatures.");

        return 0;
    }
}
