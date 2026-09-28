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
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface|null
     */
    private $container;

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function load(ObjectManager $manager)
    {
        if ($this->container === null) {
            throw new \LogicException('The container is not set.');
        }
        /** @var DecklistFactory $decklistFactory */
        $decklistFactory = $this->container->get('decklist_factory');

        for($i = 1; $i < 5; $i++){
            /** @var Deck $deck */
            $deck = $this->getReference('test-deck-' . $i);

            $decklist = $decklistFactory->createDecklistFromDeck($deck, $deck->getName(), 'Hello World');
            $decklist->setDateCreation(new \DateTime('2015-08-16'));
            $decklist->setDateUpdate(new \DateTime('2015-08-16'));
            $decklist->setDateLastComment(new \DateTime('2015-08-16'));

            $deck->setDateUpdate(new \DateTime('2015-08-16'));

            $manager->persist($decklist);
            $this->addReference('test-decklist-' . $i, $decklist);
        }

        $manager->flush();
    }
}