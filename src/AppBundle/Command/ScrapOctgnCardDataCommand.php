<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

class ScrapOctgnCardDataCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('app:cards:octgn')
             ->setDescription('Load Card Data from OCTGN sets')
             ->addArgument(
                 'path',
                 InputArgument::OPTIONAL,
                 'Path for the OCTGN lotr directory',
                 '../OCTGN - The Lord of the Rings'
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();

        $path = $input->getArgument('path') . '/Sets';
        if (!is_dir($path)) {
            die("Invalid directory $path");
        }
        dump("Loading Sets from $path");

        $fixedNames = [
            'The Hobbit - On the Doorstep' => 'On the Doorstep',
            'Voice of Isengard' => 'The Voice of Isengard',
            'The Hobbit - Over Hill and Under Hill' => 'Over Hill and Under Hill',
            'The Stewards Fear' => 'The Steward\'s Fear'
        ];

        $setPaths = scandir($path);
        foreach ($setPaths as $setPath) {
            if (strlen($setPath) != 36) {
                $output->writeln("Skipping $setPath");
                continue;
            }

            $xmlFile = $path . "/" . $setPath . "/set.xml";
            if (!file_exists($xmlFile)) {
                //$output->writeln("set.xml within $setPath not found, skipping...");
                continue;
            }
            $xml = file_get_contents($xmlFile);

            $crawler = new Crawler();
            $crawler->addXmlContent($xml);

            $packName = $crawler->filter('set')->first()->attr('name');
            if (isset($fixedNames[$packName])) {
                $packName = $fixedNames[$packName];
            }

            $pack = $em->getRepository('AppBundle:Pack')->findOneBy(['name' => $packName]);
            if (!$pack) {
                //$output->writeln("Unknown pack $packName, skipping...");
                continue;
            }

            $cardcrawler = $crawler->filter('set > cards > card');
            foreach ($cardcrawler as $domElement) {
                $octgnid = $domElement->getAttribute('id');
                $name = $domElement->getAttribute('name');

                /* @var $card \AppBundle\Entity\Card */
                $card = $em->getRepository('AppBundle:Card')->findOneBy(['name' => $name, 'pack' => $pack]);
                if ($card) {
                    $card->setOctgnid($octgnid);


                    //$domCrawler = new Crawler();
                    //$domCrawler->add($domElement);
                    //
                    //$traits = '';
                    //$traitsCrawler = $domCrawler->filter('property[name="Victory Points"]');
                    //if ($traitsCrawler->count()) {
                    //    $traits = $traitsCrawler->first()->attr('value');
                    //}
                    //
                    //if ($traits != $card->getVictory()) {
                    //    dump($card->getCode() . " " . $traits);
                    //    //echo $traits;
                    //    //dump($card->getTraits());
                    //}
                }
            }

            //dump("Loading cards from $packName");

            /*
            // read octgnid
            $cardcrawler = $crawler->filter('deck > section > card');
            $octgnids = [];
            foreach ($cardcrawler as $domElement) {
                $octgnids[$domElement->getAttribute('id')] = intval($domElement->getAttribute('qty'));
            }

            // read desc
            $desccrawler = $crawler->filter('deck > notes');
            $descriptions = [];
            foreach ($desccrawler as $domElement) {
                $descriptions[] = $domElement->nodeValue;
            }

            $content = [];
            foreach ($octgnids as $octgnid => $qty) {
                $card = $em->getRepository('AppBundle:Card')->findOneBy([
                    'octgnid' => $octgnid
                ]);

                if ($card) {
                    $content[$card->getCode()] = $qty;
                }
            }

            $description = implode("\n", $descriptions);

            echo $set;*/
        }

        $em->flush();
        $output->writeln("Done.");
    }
}
