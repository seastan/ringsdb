<?php

namespace AppBundle\Command;

use AppBundle\Entity\User;
use AppBundle\Entity\Decklist;
use AppBundle\Entity\Deck;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveUserCommand extends Command {
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
        $this
            ->setName('app:user:remove')
            ->setDescription('Lock one user and delete all its content')
            ->addArgument(
                'user_id',
                InputArgument::REQUIRED,
                'Id of the user'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->em;

        $user_id = $input->getArgument('user_id');
        $user = $em->getRepository(User::class)->find($user_id);

        if (!$user) {
            $output->writeln("User not found");
            return 1;
        }

        $output->writeln("User " . $user->getUsername());

        $decks = $em->getRepository(Deck::class)->findBy([
            'user' => $user
        ]);

        $output->writeln(count($decks) . " decks");

        foreach ($decks as $deck) {
            $children = $em->getRepository(Decklist::class)->findBy([
                'parent' => $deck
            ]);

            foreach ($children as $child) {
                $child->setParent(null);
            }

            $em->remove($deck);
        }

        $output->writeln("Decks deleted");

        $decklists = $em->getRepository(Decklist::class)->findBy([
            'user' => $user
        ]);

        $output->writeln(count($decklists) . " decklists");

        foreach ($decklists as $decklist) {
            $successors = $em->getRepository(Decklist::class)->findBy([
                'precedent' => $decklist
            ]);
            foreach ($successors as $successor) {
                $successor->setPrecedent(null);
            }

            $children = $em->getRepository(Deck::class)->findBy([
                'parent' => $decklist
            ]);
            foreach ($children as $child) {
                /* @var $child \AppBundle\Entity\Deck */
                $child->setParent(null);
            }

            $em->remove($decklist);
        }

        $output->writeln("Decklists deleted");

        $user->setLocked(true);

        $output->writeln("User locked");

        $em->flush();

        return 0;
    }
}
