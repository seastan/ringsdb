<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Deck;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveUserCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly DeckRepository $deckRepository, private readonly DecklistRepository $decklistRepository, private readonly UserRepository $userRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:user:remove')
            ->setDescription('Lock one user and delete all its content')
            ->addArgument(
                'user_id',
                InputArgument::REQUIRED,
                'Id of the user'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user_id = $input->getArgument('user_id');
        $user = $this->userRepository->find($user_id);

        if (!$user) {
            $output->writeln('User not found');

            return 1;
        }

        $output->writeln('User '.$user->getUsername());

        $decks = $this->deckRepository->findBy([
            'user' => $user,
        ]);

        $output->writeln(count($decks).' decks');

        foreach ($decks as $deck) {
            $children = $this->decklistRepository->findBy([
                'parent' => $deck,
            ]);

            foreach ($children as $child) {
                $child->setParent();
            }

            $this->entityManager->remove($deck);
        }

        $output->writeln('Decks deleted');

        $decklists = $this->decklistRepository->findBy([
            'user' => $user,
        ]);

        $output->writeln(count($decklists).' decklists');

        foreach ($decklists as $decklist) {
            $successors = $this->decklistRepository->findBy([
                'precedent' => $decklist,
            ]);
            foreach ($successors as $successor) {
                $successor->setPrecedent();
            }

            $children = $this->deckRepository->findBy([
                'parent' => $decklist,
            ]);
            foreach ($children as $child) {
                /* @var $child Deck */
                $child->setParent();
            }

            $this->entityManager->remove($decklist);
        }

        $output->writeln('Decklists deleted');

        $user->setLocked(true);

        $output->writeln('User locked');

        $this->entityManager->flush();

        return 0;
    }
}
