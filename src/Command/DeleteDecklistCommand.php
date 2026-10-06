<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Deck;
use App\Entity\Decklist;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteDecklistCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly DeckRepository $deckRepository, private readonly DecklistRepository $decklistRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:decklist:delete')
            ->setDescription('Delete one decklist')
            ->addArgument(
                'decklist_id',
                InputArgument::REQUIRED,
                'Id of the decklist'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $decklist_id = $input->getArgument('decklist_id');
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist) {
            $output->writeln('Decklist not found');

            return 1;
        }

        $successors = $this->decklistRepository->findBy([
            'precedent' => $decklist,
        ]);

        foreach ($successors as $successor) {
            /* @var $successor Decklist */
            $successor->setPrecedent();
        }

        $children = $this->deckRepository->findBy([
            'parent' => $decklist,
        ]);

        foreach ($children as $child) {
            /* @var $child Deck */
            $child->setParent();
        }

        $this->entityManager->flush();
        $this->entityManager->remove($decklist);
        $this->entityManager->flush();

        $output->writeln('Decklist deleted');

        return 0;
    }
}
