<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Decklist;
use App\Repository\DecklistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixThreatCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly DecklistRepository $decklistRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:fix-threat')
             ->setDescription('Fix starting threat for decklists');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = 0;

        /* @var $decklists Decklist[] */
        $decklists = $this->decklistRepository->findAll();
        foreach ($decklists as $decklist) {
            /* @var $decklist Decklist */
            $decklist->setStartingThreat($decklist->getSlots()->getStartingThreat());
            ++$count;
        }

        $this->entityManager->flush();
        $output->writeln(date('c')." Fixed $count starting threats.");

        return 0;
    }
}
