<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Deck;
use AppBundle\Model\DecklistFactory;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadDecklistData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
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
        /** @var DecklistFactory $decklistFactory */
        $decklistFactory = $this->container->get('decklist_factory');

        for($i = 1; $i < 5; $i++){
            /** @var Deck $deck */
            $deck = $this->getReference('test-deck-' . $i);

            $decklist = $decklistFactory->createDecklistFromDeck($deck, $deck->getName(), 'Hello World');

            $manager->persist($decklist);
            $this->addReference('test-decklist-' . $i, $decklist);
        }

        $manager->flush();
    }
}