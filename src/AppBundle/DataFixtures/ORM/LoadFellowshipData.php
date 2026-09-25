<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Deck;
use AppBundle\Entity\Fellowship;
use AppBundle\Entity\FellowshipDeck;
use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadFellowshipData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getDependencies()
    {
        return [
            LoadDeckData::class
        ];
    }

    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $this->getReference('test-user');

        $fellowship = new Fellowship();
        $fellowship->setIsPublic(true);
        $fellowship->setNbVotes(0);
        $fellowship->setNbComments(0);
        $fellowship->setNbFavorites(0);
        $fellowship->setNbDecks(4);
        $fellowship->setUser($user);
        $fellowship->setName("Heirs to Numeror Cycle");
        $fellowship->setNameCanonical("heirs-to-numeror-cycle");

        for($i = 1; $i < 5; $i++) {
            /** @var Deck $deck */
            $deck = $this->getReference('test-deck-' . $i);
            $fellowship_deck = new FellowshipDeck();
            $fellowship_deck->setDeck($deck);
            $fellowship_deck->setDeckNumber($i);
            $fellowship_deck->setFellowship($fellowship);
            $fellowship->addDeck($fellowship_deck);
        }

        $manager->persist($fellowship);
        $manager->flush();
    }

}