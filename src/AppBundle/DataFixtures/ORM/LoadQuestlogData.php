<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Decklist;
use AppBundle\Entity\Questlog;
use AppBundle\Entity\QuestlogDeck;
use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadQuestlogData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getDependencies()
    {
        return [
            LoadUserData::class,
            LoadDecklistData::class
        ];
    }

    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $this->getReference('test-user');

        $questlog = new Questlog();
        $questlog->setSuccess(true);
        $questlog->setNbVotes(0);
        $questlog->setNbComments(0);
        $questlog->setNbFavorites(0);
        $questlog->setNbDecks(4);
        $questlog->setIsPublic(true);
        $questlog->setDatePublish(new \DateTime('2015-08-16'));

        $scenario = $manager->getRepository('AppBundle:Scenario')->find(1);

        $questlog->setUser($user);
        $questlog->setName("Untitled Questlog");
        $questlog->setNameCanonical("untitled-questlog");
        $questlog->setDescriptionMd("Hello world");
        $questlog->setDescriptionHtml("Hello world");
        $questlog->setScenario($scenario);
        $questlog->setDatePlayed(new \DateTime('2015-08-16'));
        $questlog->setQuestMode('normal');
        $questlog->setSuccess(true);
        $questlog->setScore(25);
        $questlog->setDateCreation(new \DateTime('2015-08-16'));
        $questlog->setDateUpdate(new \DateTime('2015-08-16'));

        for($i=1; $i<5; $i++){
            /** @var Decklist $decklist */
            $decklist = $this->getReference('test-decklist-' . $i);

            $questlog_decklist = new QuestlogDeck();
            $questlog_decklist->setDecklist($decklist);
            $questlog_decklist->setDeck($decklist->getParent());
            $questlog_decklist->setContent(json_encode($decklist->getContent()));
            $questlog_decklist->setDeckNumber($i);
            $questlog_decklist->setQuestlog($questlog);
            $questlog_decklist->setPlayer('Player ' . $i);

            $questlog->addDeck($questlog_decklist);
        }

        $manager->persist($questlog);
        $manager->flush();
    }
}