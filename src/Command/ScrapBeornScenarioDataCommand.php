<?php

namespace App\Command;

use App\Entity\Scenario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use App\Entity\Card;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\VarDumper\VarDumper;

class ScrapBeornScenarioDataCommand extends Command {

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
        $this->setName('app:beorn:scenario')
            ->setDescription('Download scenario statistics data from Hall of Beorn')
            ->addOption(
                'skip',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of cards to skip'
            )
            ->addOption(
                'name',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the particular scenario'
            )
            ->addOption(
                'customjson',
                null,
                InputOption::VALUE_REQUIRED,
                'Custom Hall of Beorn JSON'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $name = $input->getOption('name');
        $skip = $input->getOption('skip');
        $customjson = $input->getOption('customjson');

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->em;

        $this->command($em, $name, $skip, $customjson);
        $output->writeln("Done.");

        return 0;
    }

	/**
	 * @param mixed $em
	 * @param mixed $name
	 * @param mixed $skip
	 * @param mixed $customjson
	 * @return string
	 */
	public static function command($em, $name, $skip, $customjson) {
		$res = '';
		$name = $name ?: null;
		$skip = $skip ?: 0;
		$customjson = $customjson ?: null;

		if ($name) {
			/* @var $allScenarios \App\Entity\Scenario[] */
			$allScenarios = [$em->getRepository(Scenario::class)->findOneBy(['name' => $name])];
		}
		else {
			/* @var $allScenarios \App\Entity\Scenario[] */
			$allScenarios = $em->getRepository(Scenario::class)->findAll();
		}

		$i = 0;
		foreach ($allScenarios as $scenario) {
			if ($skip > $i++) {
				continue;
			}

			$beornscenario = strtr($scenario->getName(), ['ALeP - ' => '', ' ' => '-', 'ú' => '%C3%BA', 'î' => '%C3%AE', 'û' => '%C3%BB', ',' => '']);
			$output_line = $beornscenario;
			VarDumper::dump($output_line);
			$res .= $output_line . "\n<br>";
			$url = 'http://hallofbeorn.com/LotR/ScenarioDetails/';
			if ($customjson) {
				$json = $customjson;
			}
			else {
				$json = file_get_contents($url.$beornscenario);
			}

			if (!$json || $json == '{}') {
				$output_line = 'Could not find scenario ' . $scenario->getName();
				VarDumper::dump($output_line);
				$res .= $output_line . "\n<br>";
				continue;
			}
			$beorn = json_decode($json);

			$scenario->setHasEasy($beorn->HasEasy);
			$scenario->setHasNightmare($beorn->HasNightmare);
			$scenario->setEasyCards($beorn->EasyCards);
			$scenario->setEasyEnemies($beorn->EasyEnemies);
			$scenario->setEasyLocations($beorn->EasyLocations);
			$scenario->setEasyTreacheries($beorn->EasyTreacheries);
			$scenario->setEasyShadows($beorn->EasyShadows);
			$scenario->setEasyObjectives($beorn->EasyObjectives);
			$scenario->setEasyObjectiveAllies($beorn->EasyObjectiveAllies);
			$scenario->setEasyObjectiveLocations($beorn->EasyObjectiveLocations);
			$scenario->setEasySurges($beorn->EasySurges);
			$scenario->setEasyEncounterSideQuests($beorn->EasyEncounterSideQuests);

			$scenario->setNormalCards($beorn->NormalCards);
			$scenario->setNormalEnemies($beorn->NormalEnemies);
			$scenario->setNormalLocations($beorn->NormalLocations);
			$scenario->setNormalTreacheries($beorn->NormalTreacheries);
			$scenario->setNormalShadows($beorn->NormalShadows);
			$scenario->setNormalObjectives($beorn->NormalObjectives);
			$scenario->setNormalObjectiveAllies($beorn->NormalObjectiveAllies);
			$scenario->setNormalObjectiveLocations($beorn->NormalObjectiveLocations);
			$scenario->setNormalSurges($beorn->NormalSurges);
			$scenario->setNormalEncounterSideQuests($beorn->NormalEncounterSideQuests);

			$scenario->setNightmareCards($beorn->NightmareCards);
			$scenario->setNightmareEnemies($beorn->NightmareEnemies);
			$scenario->setNightmareLocations($beorn->NightmareLocations);
			$scenario->setNightmareTreacheries($beorn->NightmareTreacheries);
			$scenario->setNightmareShadows($beorn->NightmareShadows);
			$scenario->setNightmareObjectives($beorn->NightmareObjectives);
			$scenario->setNightmareObjectiveAllies($beorn->NightmareObjectiveAllies);
			$scenario->setNightmareObjectiveLocations($beorn->NightmareObjectiveLocations);
			$scenario->setNightmareSurges($beorn->NightmareSurges);
			$scenario->setNightmareEncounterSideQuests($beorn->NightmareEncounterSideQuests);

			$em->flush();
		}

		$em->flush();
		$res .= 'Done';
		return $res;
	}
}