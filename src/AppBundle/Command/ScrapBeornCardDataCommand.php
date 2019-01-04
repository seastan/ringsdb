<?php

namespace AppBundle\Command;

use AppBundle\Entity\Card;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\VarDumper\VarDumper;


class ScrapBeornCardDataCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('app:beorn:html')
             ->setDescription('Download new card data from Hall of Beorn')
             ->addArgument(
                 'beornset',
                 InputArgument::OPTIONAL,
                 'Name of the set to download, following Hall of Beorn\'s name (ex "Core Set")'
             )
             ->addOption(
                 'skip',
                 null,
                 InputOption::VALUE_REQUIRED,
                 'Number of cards to skip'
             )
             ->addOption(
                 'force-data',
                 null,
                 InputOption::VALUE_NONE,
                 'Redefine card data'
             )
             ->addOption(
                 'force-image',
                 null,
                 InputOption::VALUE_NONE,
                 'Redownload card image'
             )
             ->addOption(
                 'show-texts',
                 null,
                 InputOption::VALUE_NONE,
                 'Show card text and flavor'
	     )
             ->addOption(
                 'skip-data',
                 null,
                 InputOption::VALUE_NONE,
                 'Skip data import'
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $questionHelper = $this->getHelper('question');

        $assets_helper = $this->getContainer()->get('templating.helper.assets');
        $rootDir = $this->getContainer()->get('kernel')->getRootDir();

        $allSpheres = $em->getRepository('AppBundle:Sphere')->findAll();
        $allTypes = $em->getRepository('AppBundle:Type')->findAll();

        $setname = $input->getArgument('beornset');
        $skip = $input->getOption('skip') ?: 0;
        $forceData = $input->getOption('force-data');
        $forceImage = $input->getOption('force-image');
        $showTexts = $input->getOption('show-texts');
        $skipData = $input->getOption('skip-data');

        $sets = [];
        if ($setname) {
            $sets[] = $setname;
        } else {
            $sets = [
                'The Dead Marshes',
                'Return to Mirkwood',
                'Khazad-dûm',
                'The Redhorn Gate',
                'Road to Rivendell',
                'The Watcher in the Water',
                'The Long Dark',
                'Foundations of Stone',
                'Shadow and Flame',
                'Heirs of Númenor',
                'The Steward\'s Fear',
                'The Drúadan Forest',
                'Encounter at Amon Dîn',
                'Assault on Osgiliath',
                'The Blood of Gondor',
                'The Morgul Vale',
                'The Voice of Isengard',
                'The Dunland Trap',
                'The Three Trials',
                'Trouble in Tharbad',
                'The Nîn-in-Eilph',
                'Celebrimbor\'s Secret',
                'The Antlered Crown',
                'The Lost Realm',
                'The Wastes of Eriador',
                'Escape from Mount Gram',
                'Across the Ettenmoors',
                'The Treachery of Rhudaur',
                'The Battle of Carn Dûm',
                'The Dread Realm',
                'The Grey Havens',
                'Flight of the Stormcaller',
		'The Thing in the Depths',
		'Temple of the Deceived',
		'The Drowned Ruins',
		'A Storm on Cobas Haven',
		'The City of Corsairs',
		'The Sands of Harad',
		'Race Across Harad',
		'Beneath the Sands',
		'The Black Serpent',
		'The Dungeons of Cirith Gurat',
		'The Crossings of Poros',
		'The Wilds of Rhovanion',
		'The Withered Heath',
		'Roam Across Rhovanion',
		'Fire in the Night',
                'The Hobbit: Over Hill and Under Hill',
                'The Hobbit: On the Doorstep',
                'The Black Riders',
                'The Road Darkens',
                'The Treason of Saruman',
                'The Land of Shadow',
		'The Flame of the West',
		'The Mountain of Fire'
            ];
        }

        foreach ($sets as $set) {
            $pack = $em->getRepository('AppBundle:Pack')->findOneBy(['name' => $set]);

            if (!$pack) {
                $output->writeln("<error>Cannot find pack [" . $set . "]</error>");
                die();
            }

            $beornset = str_replace([' '], ['%20'], $set);
            $html = file_get_contents("http://hallofbeorn.com/LotR?Sort=Set_Number&CardSet=$beornset");
            $output->writeln("a");

            $crawler = new Crawler($html);
            $output->writeln("b");

            $cardsUrls = $crawler->filter('a[href^="/LotR/Details"][style]')->extract('href');
            $output->writeln("c");

            $i = 0;

            $question = new ConfirmationQuestion("Shall I import the cards from the set =< $set >= ?");
            if (!$questionHelper->ask($input, $output, $question)) {
            	break;
            }

            foreach ($cardsUrls as $url) {
	        $output->writeln("Grabbing image from: http://hallofbeorn.com$url");
                if ($skip > $i++) {
                    continue;
                }

                $cardCrawler = new Crawler(file_get_contents("http://hallofbeorn.com$url"));
		$output->writeln("1");

                // Type and Sphere
                $c = $cardCrawler->filter('div.statTypeBox')->first();
                $type = $c->filter('div > div')->last()->text();
                $sphere = null;
		$output->writeln("2");

                if ($c->filter('img')->count() > 0) {
                    $sphere = basename($c->filter('img')->attr('src'), '.png');
		    $sphere = substr( $sphere, 0, strrpos( $sphere, '-' ) );
                } else {
                    $sphere = 'Neutral';
                }
		$output->writeln("3");

                // Name and Uniqueness
                $c = $cardCrawler->filter('div.titleNameBox > div')->first();
                $name = $c->text();
                $isUnique = $c->filter('img[src="/Images/unique-card.png"]')->count() > 0;
		$output->writeln("4");

                $output->writeln("\n\n\n\n\n\n\n\n");
                VarDumper::dump("Importing card number $i: $name");
		$output->writeln("5");

                // Set, Number and Quantity
                $c = $cardCrawler->filter('div.titleNameBox > div')->last();
		$output->writeln("6");

                $t = $c->filter('span')->last()->text();
                preg_match('/^#(\d+) \(x(\d+)\)$/', $t, $matches);
                $position = $matches[1];
                $quantity = $matches[2];
		$output->writeln("7");

                // Image URL
                $imageurl = $cardCrawler->filter('div.titleBox > img')->last()->attr('src');
		$output->writeln("8");

                // Threat, Willpower, Attack, Defense, Hit Points
                $c = $cardCrawler->filter('div.statValueBox')->first();
		$output->writeln("9");

                $cost = $threat = $c->filter('span')->eq(1)->text();
		$output->writeln("9a");
                $limit = ($type == 'Hero') ? 1 : 3;
		$output->writeln("9b");
                $willpower = null;
		$output->writeln("9c");
                $attack = null;
		$output->writeln("9d");
                $defense = null;
		$output->writeln("9e");
                $health = null;
		$output->writeln("9f");
                $victory = null;
		$output->writeln("9g");
                $quest = null;
		$output->writeln("9h");

                if ($type == 'Hero' || $type == 'Ally') {
		$output->writeln("9i");
                    $willpower = $c->filter('img[src="/Images/willpower-med.png"]')->previousAll()->last()->text();
		$output->writeln("9j");
                    $attack = $c->filter('img[src="/Images/attack-med.png"]')->previousAll()->last()->text();
		$output->writeln("9k");
                    $defense = $c->filter('img[src="/Images/defense-med.png"]')->previousAll()->last()->text();
		$output->writeln("9l");
                    $health = $c->filter('img[src="/Images/heart-med.png"]')->previousAll()->last()->text();
		$output->writeln("9m");
                } else if ($type == 'Player-Side-Quest') {
                    $type = 'Player Side Quest';
		$output->writeln("9n");
                    $quest = $c->filter('span')->eq(4)->text();
		$output->writeln("9o");
                }
		$output->writeln("10");

                // Traits, text and flavor
                $c = $cardCrawler->filter('div.statTextBox')->first();
                $traits = $c->filter('a[title="Trait Search"] i')->extract('_text');
                $traits = implode(' ', $traits);
		$output->writeln("11");

                $text = $c->filter('p:not(.flavor-text)')->each(function(Crawler $node, $i) {
                    return $node->html();
                });
		$output->writeln("12");

                $text = implode("<br>", $text);
		$output->writeln("13");

                $flavor = $c->filter('p.flavor-text')->each(function(Crawler $node, $i) {
                    return $node->html();
                });
		$output->writeln("14");

                $flavor = implode("<br>", $flavor);
		$output->writeln("15");

                //if ($type == 'Boon') {
                //    $type = 'Attachment';
                //    $sphere = 'Boon';
                //}

                // OCTGN
                //$octgn = substr($cardCrawler->filter('img[title^="OCTGN"]')->attr('title'), -36);

		// Get matching RingsDB card
                $card = $em->getRepository('AppBundle:Card')->findOneBy(['name' => $name, 'pack' => $pack]);
                if ($card && !$forceData && !$forceImage) {
                    // shortcut: we already know this card
                    continue;
                }
		$output->writeln("16");

                if ($card && !$forceData) {
		   $output->writeln("<error>Card already known and --force-data not set.</error>");
                   continue;
                }

                $objSphere = null;
                foreach ($allSpheres as $oneSphere) {
                    if ($sphere === $oneSphere->getName()) {
                        $objSphere = $oneSphere;
                    }
                }
		$output->writeln("17");

                if (!$objSphere) {
                    $output->writeln("<error>Cannot find sphere [$sphere] for this card</error>");
                    die();
                }
		$output->writeln("18");

                $objType = null;
                foreach ($allTypes as $oneType) {
                    if ($type === $oneType->getName()) {
                        $objType = $oneType;
                    }
                }
		$output->writeln("19");

                if (!$objType) {
                    $output->writeln("<error>Cannot find type [$type] for this card</error>");
                    die();
                }
		$output->writeln("20");

                $text = str_replace(['“', '”', '’', '&rsquo;'], ['"', '"', '\'', '\''], $text);
                $text = preg_replace('/<a title="Search:.*?>(.*?)<\/a>/', '\\1', $text);
                $text = preg_replace('/<a title="Keyword:.*?>(.*?)<\/a>/', '\\1', $text);
                $text = preg_replace_callback('/<img .*?src="\/Images\/(.*?)\..*?>/', function($m) {
                    return strtolower("[$m[1]]");
                }, $text);
                $text = str_replace(['<br />', '<br>'], ["\n", "\n"], $text);
                $text = str_replace("</b><b>", " ", $text);
                $text = str_replace("</b>: ", ":</b> ", $text);
                $text = preg_replace("/ +/", " ", $text);
                $text = preg_replace("/\n+/", "\n", $text);
                $text = trim($text);
		$output->writeln("21");

                if ($text && $showTexts) {
                    $output->writeln("Card text:");
                    VarDumper::dump($text);
                }
		$output->writeln("22");

                $flavor = str_replace(['<br />', '<br>'], ["\n", "\n"], $flavor);
                $flavor = preg_replace('/([a-z])–/s', '\\1-', $flavor);
                $flavor = preg_replace('/–(.*)$/s', '<cite>\\1</cite>', $flavor);
                $flavor = preg_replace("/ +/", " ", $flavor);
                $flavor = preg_replace("/\n+/", "\n", $flavor);
		$output->writeln("23");


                if ($flavor && $showTexts) {
                    $output->writeln("Card flavor:");
                    VarDumper::dump($flavor);
                }
		$output->writeln("24");

                $question = new ConfirmationQuestion("Shall I import this card?");
                if (!$questionHelper->ask($input, $output, $question)) {
                    continue;
                }
		$output->writeln("25");

                if (!$card) {
                    $card = new Card();
                }
		$output->writeln("26");

                $card->setPosition($position);
                if ($pack->getCycle()->getIsSaga()) {
                    $card->setCode(sprintf("%02d%d%03d", $pack->getCycle()->getPosition(), $pack->getPosition(), $position));
                } else {
                    $card->setCode(sprintf("%02d%03d", $pack->getCycle()->getPosition(), $position));
                }
		$output->writeln("27");


                $card->setType($objType);
                $card->setSphere($objSphere);
                $card->setPack($pack);

                $card->setName($name);
                $card->setTraits($traits);
                $card->setText($text);
                $card->setFlavor($flavor);
                $card->setIsUnique($isUnique);
		$output->writeln("28");

                if ($type === 'Hero') {
                    $cost = null;
                } else {
                    $threat = null;
                }

		$output->writeln("29");
                $card->setCost($cost !== '' ? $cost : null);
                $card->setThreat($threat !== '' ? $threat : null);
                $card->setWillpower($willpower !== '' ? $willpower : null);
                $card->setAttack($attack !== '' ? $attack : null);
                $card->setDefense($defense !== '' ? $defense : null);
                $card->setHealth($health !== '' ? $health : null);
                $card->setVictory($victory !== '' ? $victory : null);
                $card->setQuest($quest !== '' ? $quest : null);

                $card->setQuantity($quantity);
                $card->setDeckLimit($limit);
                $card->setHasErrata(false);

                //$card->setIllustrator(trim($data['illustrator']));

		$output->writeln("30");
                $em->persist($card);

		$output->writeln("31");
                // trying to download image file
                $card_code = $card->getCode();
                $asseturl = $assets_helper->getUrl('bundles/cards/' . $card_code . '.png');
                $imagepath = $rootDir . '/../web' . preg_replace('/\?.*/', '', $asseturl);
                $dirname = dirname($imagepath);
                $outputfile = $dirname . DIRECTORY_SEPARATOR . $card_code . ".png";

		$output->writeln("32");
                if (!file_exists($outputfile) || $forceImage) {
 		    $output->writeln("33");
                    $imageurl = preg_replace('/û/', '%C3%BB', $imageurl);
                    $u = dirname($imageurl) . '/' . urlencode(basename($imageurl, '.jpg')) . '.jpg';
		    $output->writeln("34");

                    $image = file_get_contents($u);

		    $output->writeln("35");
                    if (!$image) {
                        $output->writeln("<error>Cannot download image for this card</error>");
                        die();
                    }
		    $output->writeln("36");

                    file_put_contents($outputfile, $image);
		    $output->writeln("37");
                }
		$output->writeln("38");
                $em->flush();
		$output->writeln("39");
            }
        }

        $em->flush();
        $output->writeln("Done.");
    }
}
