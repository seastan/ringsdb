<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Deck;
use AppBundle\Entity\User;
use AppBundle\Services\Decks;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadDeckData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
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
        ];
    }

    public function load(ObjectManager $manager)
    {
        /** @var Decks $deckService */
        $deckService = $this->container->get('decks');

        /** @var User $user */
        $user = $this->getReference('test-user');

        $deck1 = new Deck();
        $deckService->saveDeck(
            $user,
            $deck1,
            null,
            'Dwarf Lore/Leadership/Tactics',
            'Description',
            null,
            [
                "main" => [
                    "01004" => 1,
                    "01028" => 3,
                    "01041" => 3,
                    "01059" => 3,
                    "01061" => 3,
                    "02032" => 1,
                    "02116" => 1,
                    "03002" => 1,
                    "03003" => 3,
                    "03004" => 3,
                    "03006" => 3,
                    "03007" => 3,
                    "03008" => 3,
                    "03011" => 3,
                    "04030" => 3,
                    "04061" => 1,
                    "04079" => 3,
                    "04080" => 3,
                    "04102" => 3,
                    "04129" => 3,
                    "04130" => 1,
                    "06141" => 3,
                ],
                "side" => [],
            ],
            null
        );
        $manager->persist($deck1);
        $this->addReference('test-deck-1', $deck1);

        $deck2 = new Deck();
        $deckService->saveDeck(
            $user,
            $deck2,
            null,
            'Gondor/Dunedain Leadership/Spirit',
            'Description',
            null,
            [
                "main" => [
                    "01001" => 1,
                    "01008" => 1,
                    "01013" => 3,
                    "01014" => 1,
                    "01023" => 3,
                    "01026" => 3,
                    "01027" => 1,
                    "01048" => 3,
                    "01050" => 3,
                    "01073" => 3,
                    "02002" => 3,
                    "02003" => 3,
                    "02026" => 3,
                    "02118" => 1,
                    "04055" => 1,
                    "04103" => 1,
                    "05002" => 1,
                    "05003" => 3,
                    "05004" => 3,
                    "05018" => 3,
                    "06007" => 1,
                    "06057" => 1,
                    "06061" => 3,
                    "06090" => 1,
                    "06108" => 3,
                    "06135" => 3,
                    "06136" => 2,
                ],
                "side" => [],
            ],
            null
        );
        $manager->persist($deck2);
        $this->addReference('test-deck-2', $deck2);

        $deck3 = new Deck();
        $deckService->saveDeck(
            $user,
            $deck3,
            null,
            'Noldor/Rohan Lore/Spirit',
            'Description',
            null,
            [
                "main" => [
                    "01007" => 1,
                    "01057" => 2,
                    "01062" => 2,
                    "01066" => 3,
                    "01070" => 2,
                    "02030" => 2,
                    "02033" => 2,
                    "02057" => 2,
                    "02079" => 2,
                    "02100" => 2,
                    "02121" => 1,
                    "02123" => 2,
                    "03009" => 3,
                    "04034" => 2,
                    "04058" => 2,
                    "04059" => 3,
                    "04083" => 3,
                    "04101" => 1,
                    "04106" => 3,
                    "04107" => 3,
                    "04110" => 3,
                    "04128" => 1,
                    "04134" => 2,
                    "04137" => 1,
                    "05017" => 3,
                ],
                "side" => [],
            ],
            null
        );
        $manager->persist($deck3);
        $this->addReference('test-deck-3', $deck3);

        $deck4 = new Deck();
        $deckService->saveDeck(
            $user,
            $deck4,
            null,
            'Gondor/Rohan/Silvan Tactics',
            'Description',
            null,
            [
                "main" => [
                    "01005" => 1,
                    "01029" => 3,
                    "01030" => 3,
                    "01034" => 3,
                    "01040" => 1,
                    "01042" => 2,
                    "02004" => 3,
                    "02005" => 3,
                    "02098" => 3,
                    "02119" => 3,
                    "04031" => 3,
                    "04057" => 1,
                    "04131" => 3,
                    "05001" => 1,
                    "05007" => 3,
                    "05008" => 3,
                    "05009" => 1,
                    "06005" => 3,
                    "06084" => 3,
                    "06085" => 2,
                    "06110" => 2,
                    "06134" => 1,
                    "06137" => 3,
                ],
                "side" => [],
            ],
            null
        );
        $manager->persist($deck4);
        $this->addReference('test-deck-4', $deck4);

        $manager->flush();

    }
}