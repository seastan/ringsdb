<?php

namespace App\DataFixtures\ORM;

use App\Entity\Deck;
use App\Entity\Fellowship;
use App\Entity\FellowshipDeck;
use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\DataFixtures\ORM\LoadDeckData;

class LoadFellowshipData extends AbstractFixture implements DependentFixtureInterface
{

    /**
     * @return array<int, class-string>
     */
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
        $fellowship->setDateCreation(new \DateTime('2015-08-16'));
        $fellowship->setDateUpdate(new \DateTime('2015-08-16'));
        $fellowship->setDateLastComment(new \DateTime('2015-08-16'));
        $fellowship->setDatePublish(new \DateTime('2015-08-16'));

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