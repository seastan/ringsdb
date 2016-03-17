<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use AppBundle\Entity\Card;

function file_get_contents_retry($url, $attemptsRemaining = 3) {
    $content = @file_get_contents($url);
    $attemptsRemaining--;

    if (empty($content) && $attemptsRemaining > 0) {
        return file_get_contents_retry($url, $attemptsRemaining);
    }

    return $content;
}

class ScrapCardDataCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('app:cgdb:cards')
             ->setDescription('Download new card data from CGDB')
             ->addArgument(
                'filename',
        		    InputArgument::REQUIRED,
        		    'Name of the file to download (ex "lotrherojson-cgdb-mec47")'
             );
    }



    protected function execute(InputInterface $input, OutputInterface $output) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /* @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
        $dialog = $this->getHelper('dialog');

        $assets_helper = $this->getContainer()->get('templating.helper.assets');
        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        
        /* @var $allSpheres \AppBundle\Entity\Sphere[] */
        $allSpheres = $em->getRepository('AppBundle:Sphere')->findAll();
        
        /* @var $allTypes \AppBundle\Entity\Type[] */
        $allTypes = $em->getRepository('AppBundle:Type')->findAll();
        
        $filename = $input->getArgument('filename');
        
        $file = file_get_contents("http://www.cardgamedb.com/deckbuilders/thelordoftherings/database/$filename.jgz");
        if(!preg_match('/^cardsHero = (.*);$/', $file, $matches)) {
          $output->writeln("<error>Error while parsing js file</error>");
        }

        $json = $matches[1];
        $lookup = json_decode($json, TRUE);

        foreach($lookup as $data) {
          $name = $data['name'];
          $name = str_replace(['“', '”', '’'], ['"', '"', '\''], $name);
          
          $setname = html_entity_decode($data['setname'], ENT_QUOTES);
          $setname = str_replace(['“', '”', '’'], ['"', '"', '\''], $setname);

          /* @var $pack \AppBundle\Entity\Pack */
          $pack = $em->getRepository('AppBundle:Pack')->findOneBy(array('name' => $setname));

          if (!$pack) {
          	$output->writeln("<error>Cannot find pack [" . $setname . "]</error>");
          	break; //die();
          }

          if ($pack->getSize() === count($pack->getCards())) {
          	// shortcut: we already know all the cards of this pack
          	continue;
          }
          
          $card = $em->getRepository('AppBundle:Card')->findOneBy(array('name' => $name, 'pack' => $pack));
          if ($card) {
            // shortcut: we already know this card
            continue;
          }

          //if (!$dialog->askConfirmation($output, "<question>Shall I import the card =< $name >= from the set =< $setname >= ?</question> ", true)) {
          //	break;
          //}

          $sphere = null;
          foreach ($allSpheres as $oneSphere) {
          	if ($data['sphere'] === $oneSphere->getName()) {
          		$sphere = $oneSphere;
          	}
          }

          if (!$sphere) {
          	$output->writeln("<error>Cannot find sphere [" . $data['sphere'] . "] for this card</error>");
          	dump($data);
          	die();
          }
          
          $type = null;
          foreach ($allTypes as $oneType) {
          	if ($data['type'] === $oneType->getName()) {
          		$type = $oneType;
          	}
          }

          if (!$type) {
          	$output->writeln("<error>Cannot find type [" . $data['type'] . "] for this card</error>");
          	dump($data);
          	die();
          }

          $position = intval($data['num']);
          
          $text = $data['text'];
          $text = str_replace(['“', '”', '’', '&rsquo;'], ['"', '"', '\'', '\''], $text);
          $text = str_replace(['<br />'], ["\n"], $text);
          $text = preg_replace('/<SPAN  style="font-weight: bold" >([^<]+)<\/SPAN>/', '<b>\\1</b>', $text);
          $text = preg_replace('/<SPAN  style=\"font-weight: bold;font-style:italic\" >([^<]+)<\/SPAN>/', '<b><em>\\1</em></b>', $text);
          $text = preg_replace('/<SPAN STYLE="" >([^<]+)<\/SPAN>/', '\\1', $text);
          $text = str_replace("</b>: ", ":</b> ", $text);
          $text = preg_replace("/ +/", " ", $text);
          $text = preg_replace("/\n+/", "\n", $text);
          $text = trim($text);


          $card = new Card();
          $card->setPosition($position);
          $card->setCode(sprintf("%02d%03d", $pack->getCycle()->getPosition(), $position));

          $card->setType($type);
          $card->setSphere($sphere);
          $card->setPack($pack);

          $card->setName($name);
          $card->setTraits($data['trait']);
          $card->setText($text);
          $card->setFlavor($data['flavor']);
          $card->setIsUnique($data['unique'] === 'Yes');

          $card->setCost($data['cost'] !== '' ? $data['cost'] : null);
          $card->setThreat($data['th'] !== '' ? $data['th'] : null);
          $card->setWillpower($data['wt'] !== '' ? $data['wt'] : null);
          $card->setAttack($data['atk'] !== '' ? $data['atk'] : null);
          $card->setDefense($data['def'] !== '' ? $data['def'] : null);
          $card->setHealth($data['hp'] !== '' ? $data['hp'] : null);
          $card->setVictory($data['victory'] !== '' ? $data['victory'] : null);

          $card->setQuantity($data['packquantity']);
          $card->setDeckLimit($data['max']);

          $card->setIllustrator(trim($data['illustrator']));

          $em->persist($card);

          // trying to download image file
          $card_code = $card->getCode();
          $imageurl = $assets_helper->getUrl('bundles/cards/'. $card_code .'.png');
          $imagepath = $rootDir . '/../web' . preg_replace('/\?.*/', '', $imageurl);
          $dirname  = dirname($imagepath);
          $outputfile = $dirname . DIRECTORY_SEPARATOR . $card_code . ".png";

          $output->writeln($outputfile);

          $beorn_setname = str_replace([' '], ['-'], $card->getPack()->getName());
          $beorn_name = str_replace([' '], ['-'], $card->getName());

          // $cgdburl = "http://www.cardgamedb.com/forums/uploads/lotr/" . $data['img'];
          $beornurl = 'https://s3.amazonaws.com/hallofbeorn-resources/Images/Cards/' . $beorn_setname . '/' . $beorn_name . '.png';

          $output->writeln($beornurl);

          $image = file_get_contents_retry($beornurl);

          if (!$image) {
            $output->writeln("<error>Cannot download image for this card</error>");
           	die();
          }

          file_put_contents($outputfile, $image);
          $em->flush();
        }

        $em->flush();
        $output->writeln("Done.");
    }
}
