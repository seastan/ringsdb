<?php

namespace AppBundle\Command;

use AppBundle\Repository\DecklistRepository;
use AppBundle\Repository\DeckRepository;
use AppBundle\Entity\Decklist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteDecklistCommand extends Command {
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var DeckRepository
     */
    private $deckRepository;

    /**
     * @var DecklistRepository
     */
    private $decklistRepository;

    public function __construct(EntityManagerInterface $em, DeckRepository $deckRepository, DecklistRepository $decklistRepository) {
        parent::__construct();
        $this->em = $em;
        $this->deckRepository = $deckRepository;
        $this->decklistRepository = $decklistRepository;
    }

    /**
     * @return void
     */
    protected function configure() {
        $this
            ->setName('app:decklist:delete')
            ->setDescription('Delete one decklist')
            ->addArgument(
                'decklist_id',
                InputArgument::REQUIRED,
                'Id of the decklist'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->em;
        
        $decklist_id = $input->getArgument('decklist_id');
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist) {
            $output->writeln("Decklist not found");
            return 1;
        }
        
        $successors = $this->decklistRepository->findBy(array(
            'precedent' => $decklist
        ));
        
        foreach($successors as $successor) {
            /* @var $successor \AppBundle\Entity\Decklist */
            $successor->setPrecedent(null);
        }
        
        $children = $this->deckRepository->findBy(array(
            'parent' => $decklist
        ));

        foreach($children as $child) {
            /* @var $child \AppBundle\Entity\Deck */
            $child->setParent(null);
        }
        
        $em->flush();
        $em->remove($decklist);
        $em->flush();
        
        $output->writeln("Decklist deleted");

        return 0;
    }
}
