<?php

namespace AppBundle\Command;

use AppBundle\Entity\Decklist;
use AppBundle\Entity\Deck;
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

    public function __construct(EntityManagerInterface $em) {
        parent::__construct();
        $this->em = $em;
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
        $decklist = $em->getRepository(Decklist::class)->find($decklist_id);
        if (!$decklist) {
            $output->writeln("Decklist not found");
            return 1;
        }
        
        $successors = $em->getRepository(Decklist::class)->findBy(array(
            'precedent' => $decklist
        ));
        
        foreach($successors as $successor) {
            /* @var $successor \AppBundle\Entity\Decklist */
            $successor->setPrecedent(null);
        }
        
        $children = $em->getRepository(Deck::class)->findBy(array(
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
