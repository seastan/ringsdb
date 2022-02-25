<?php

namespace AppBundle\Command;

use AppBundle\Entity\Card;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\VarDumper\VarDumper;

class ScrapBeornScenarioDataCommand extends ContainerAwareCommand {

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
                'url',
                null,
                InputOption::VALUE_REQUIRED,
                'Custom Hall of Beorn URL'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $name = $input->getOption('name');
        $skip = $input->getOption('skip');
        $url = $input->getOption('url');

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();

        $this->command($em, $name, $skip, $url);
        $output->writeln("Done.");
    }

	function command($em, $name, $skip, $url) {
		$res = '';
		$name = $name ?: null;
		$skip = $skip ?: 0;
		$url = $url ?: 'http://hallofbeorn.com';
		if ($url[-1] != '/') $url = $url.'/';
		$url = $url.'LotR/ScenarioDetails/';

		if ($name) {
			/* @var $allScenarios \AppBundle\Entity\Scenario[] */
			$allScenarios = [$em->getRepository('AppBundle:Scenario')->findOneBy(['name' => $name])];
		}
		else {
			/* @var $allScenarios \AppBundle\Entity\Scenario[] */
			$allScenarios = $em->getRepository('AppBundle:Scenario')->findAll();
		}

		$i = 0;
		foreach ($allScenarios as $scenario) {
			if ($skip > $i++) {
				continue;
			}

			$beornscenario = str_replace('ALeP - ', '', $scenario->getName());
			$beornscenario = str_replace([' ', 'ú', 'î', 'û', ','], ['-', '%C3%BA', '%C3%AE', '%C3%BB', ''], $beornscenario);
			$output_line = $beornscenario;
			VarDumper::dump($output_line);
			$res .= $output_line . "\n<br>";
			$json = file_get_contents($url.$beornscenario);

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