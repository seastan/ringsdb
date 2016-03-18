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

class ScrapBeornCardDataCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('app:cards:beorn')
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
                'The Hobbit: Over Hill and Under Hill',
                'The Hobbit: On the Doorstep',
                'The Black Riders',
                'The Road Darkens',
                'The Treason of Saruman',
                'The Land of Shadow',
            ];
        }

        foreach ($sets as $set) {
            $pack = $em->getRepository('AppBundle:Pack')->findOneBy(['name' => $set]);

            if (!$pack) {
                $output->writeln("<error>Cannot find pack [" . $set . "]</error>");
                die();
            }

            $beornset = str_replace([' '], ['%20'], $set);
            $html = file_get_contents("http://hallofbeorn.com/Cards/Search?CardSet=$beornset");

            $crawler = new Crawler($html);

            $cardsUrls = $crawler->filter('a[href^="/Cards/Details"][style]')->extract('href');

            $i = 0;

            $question = new ConfirmationQuestion("Shall I import the cards from the set =< $set >= ?");
            if (!$questionHelper->ask($input, $output, $question)) {
            	break;
            }

            foreach ($cardsUrls as $url) {
                if ($skip > $i++) {
                    continue;
                }

                $cardCrawler = new Crawler(file_get_contents("http://hallofbeorn.com$url"));

                // Type and Sphere
                $c = $cardCrawler->filter('div.statTypeBox')->first();
                $type = $c->filter('div > div')->last()->text();
                $sphere = null;

                if ($c->filter('img')->count() > 0) {
                    $sphere = basename($c->filter('img')->attr('src'), '.png');
                } else {
                    $sphere = 'Neutral';
                }

                // Name and Uniqueness
                $c = $cardCrawler->filter('div.titleNameBox > div')->first();
                $name = $c->text();
                $isUnique = $c->filter('img[src="/Images/unique-card.png"]')->count() > 0;

                $output->writeln("\n\n\n\n\n\n\n\n");
                dump("Importing card number $i: $name");

                // Set, Number and Quantity
                $c = $cardCrawler->filter('div.titleNameBox > div')->last();

                $t = $c->filter('span')->last()->text();
                preg_match('/^#(\d+) \(x(\d+)\)$/', $t, $matches);
                $position = $matches[1];
                $quantity = $matches[2];

                // Image URL
                $imageurl = $cardCrawler->filter('div.titleBox > img')->last()->attr('src');

                // Threat, Willpower, Attack, Defense, Hit Points
                $c = $cardCrawler->filter('div.statValueBox')->first();

                $cost = $threat = $c->filter('span')->eq(1)->text();
                $limit = ($type == 'Hero') ? 1 : 3;
                $willpower = null;
                $attack = null;
                $defense = null;
                $health = null;
                $victory = null;
                $quest = null;

                if ($type == 'Hero' || $type == 'Ally') {
                    $willpower = $c->filter('img[src="/Images/willpower.gif"]')->previousAll()->last()->text();
                    $attack = $c->filter('img[src="/Images/attack.gif"]')->previousAll()->last()->text();
                    $defense = $c->filter('img[src="/Images/defense.gif"]')->previousAll()->last()->text();
                    $health = $c->filter('img[src="/Images/heart.png"]')->previousAll()->last()->text();
                } else if ($type == 'Player-Side-Quest') {
                    $type = 'Player Side Quest';
                    $quest = $c->filter('span')->eq(4)->text();
                }

                // Traits, text and flavor
                $c = $cardCrawler->filter('div.statTextBox')->first();
                $traits = $c->filter('a[title="Trait Search"] i')->extract('_text');
                $traits = implode(' ', $traits);

                $text = $c->filter('p:not(.flavor-text)')->each(function(Crawler $node, $i) {
                    return $node->html();
                });

                $text = implode("<br>", $text);

                $flavor = $c->filter('p.flavor-text')->each(function(Crawler $node, $i) {
                    return $node->html();
                });

                $flavor = implode("<br>", $flavor);

                //if ($type == 'Boon') {
                //    $type = 'Attachment';
                //    $sphere = 'Boon';
                //}

                // OCTGN
                //$octgn = substr($cardCrawler->filter('img[title^="OCTGN"]')->attr('title'), -36);

                $card = $em->getRepository('AppBundle:Card')->findOneBy(['name' => $name, 'pack' => $pack]);
                if ($card && !$forceData) {
                    // shortcut: we already know this card
                    continue;
                }

                //if (!$dialog->askConfirmation($output, "<question>Shall I import the card =< $name >= from the set =< $setname >= ?</question> ", true)) {
                //	break;
                //}

                $objSphere = null;
                foreach ($allSpheres as $oneSphere) {
                    if ($sphere === $oneSphere->getName()) {
                        $objSphere = $oneSphere;
                    }
                }

                if (!$objSphere) {
                    $output->writeln("<error>Cannot find sphere [$sphere] for this card</error>");
                    die();
                }

                $objType = null;
                foreach ($allTypes as $oneType) {
                    if ($type === $oneType->getName()) {
                        $objType = $oneType;
                    }
                }

                if (!$objType) {
                    $output->writeln("<error>Cannot find type [$type] for this card</error>");
                    die();
                }

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

                if ($text && $showTexts) {
                    $output->writeln("Card text:");
                    dump($text);
                }

                $flavor = str_replace(['<br />', '<br>'], ["\n", "\n"], $flavor);
                $flavor = preg_replace('/([a-z])–/s', '\\1-', $flavor);
                $flavor = preg_replace('/–(.*)$/s', '<cite>\\1</cite>', $flavor);
                $flavor = preg_replace("/ +/", " ", $flavor);
                $flavor = preg_replace("/\n+/", "\n", $flavor);


                if ($flavor && $showTexts) {
                    $output->writeln("Card flavor:");
                    dump($flavor);
                }

                $question = new ConfirmationQuestion("Shall I import this card?");
                if (!$questionHelper->ask($input, $output, $question)) {
                    continue;
                }

                if (!$card) {
                    $card = new Card();
                }

                $card->setPosition($position);
                if ($pack->getCycle()->getIsSaga()) {
                    $card->setCode(sprintf("%02d%d%03d", $pack->getCycle()->getPosition(), $pack->getPosition(), $position));
                } else {
                    $card->setCode(sprintf("%02d%03d", $pack->getCycle()->getPosition(), $position));
                }


                $card->setType($objType);
                $card->setSphere($objSphere);
                $card->setPack($pack);

                $card->setName($name);
                $card->setTraits($traits);
                $card->setText($text);
                $card->setFlavor($flavor);
                $card->setIsUnique($isUnique);

                if ($type === 'Hero') {
                    $cost = null;
                } else {
                    $threat = null;
                }

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

                //$card->setIllustrator(trim($data['illustrator']));

                $em->persist($card);

                // trying to download image file
                $card_code = $card->getCode();
                $asseturl = $assets_helper->getUrl('bundles/cards/' . $card_code . '.png');
                $imagepath = $rootDir . '/../web' . preg_replace('/\?.*/', '', $asseturl);
                $dirname = dirname($imagepath);
                $outputfile = $dirname . DIRECTORY_SEPARATOR . $card_code . ".png";

                if (!file_exists($outputfile) || $forceImage) {
                    $u = dirname($imageurl) . '/' . urlencode(basename($imageurl, '.jpg')) . '.jpg';

                    $image = file_get_contents($u);

                    if (!$image) {
                        $output->writeln("<error>Cannot download image for this card</error>");
                        die();
                    }

                    file_put_contents($outputfile, $image);
                }
                $em->flush();
            }
        }

        $em->flush();
        $output->writeln("Done.");
    }
}
