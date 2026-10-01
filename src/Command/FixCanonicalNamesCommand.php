<?php

namespace App\Command;

use App\Repository\ScenarioRepository;
use App\Services\Texts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixCanonicalNamesCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Texts
     */
    private $texts;

    /**
     * @var ScenarioRepository
     */
    private $scenarioRepository;

    public function __construct(EntityManagerInterface $em, Texts $texts, ScenarioRepository $scenarioRepository)
    {
        parent::__construct();
        $this->em = $em;
        $this->texts = $texts;
        $this->scenarioRepository = $scenarioRepository;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('app:fix-canonical-names')
             ->setDescription('Fix canonical names for scenarios');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->em;

        $texts = $this->texts;
        $count = 0;

        $scenarios = $this->scenarioRepository->findAll();
        foreach ($scenarios as $scenario) {
            $nameCanonical = $texts->slugify($scenario->getName());

            if ($nameCanonical !== $scenario->getNameCanonical()) {
                $scenario->setNameCanonical($nameCanonical);
                ++$count;
            }
        }

        $em->flush();
        $output->writeln(date('c')." Fixed $count scenario canonical names.");

        return 0;
    }
}
