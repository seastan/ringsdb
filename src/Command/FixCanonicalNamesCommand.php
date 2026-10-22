<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ScenarioRepository;
use App\Services\Texts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixCanonicalNamesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    private Texts $texts;

    private ScenarioRepository $scenarioRepository;

    public function __construct(EntityManagerInterface $entityManager, Texts $texts, ScenarioRepository $scenarioRepository)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->texts = $texts;
        $this->scenarioRepository = $scenarioRepository;
    }

    protected function configure(): void
    {
        $this->setName('app:fix-canonical-names')
             ->setDescription('Fix canonical names for scenarios');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
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

        $this->entityManager->flush();
        $output->writeln(date('c')." Fixed $count scenario canonical names.");

        return 0;
    }
}
