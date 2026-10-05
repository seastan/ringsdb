<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Deck;
use App\Entity\Fellowship;
use App\Entity\FellowshipDeck;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class FellowshipFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * @return array<int, class-string>
     */
    public function getDependencies(): array
    {
        return [
            DeckFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference('test-user');

        $fellowship = new Fellowship($user);
        $fellowship->setIsPublic(true);
        $fellowship->setNbVotes(0);
        $fellowship->setNbComments(0);
        $fellowship->setNbFavorites(0);
        $fellowship->setNbDecks(4);
        $fellowship->setName('Heirs to Numeror Cycle');
        $fellowship->setNameCanonical('heirs-to-numeror-cycle');
        $fellowship->setDateCreation(new \DateTime('2015-08-16'));
        $fellowship->setDateUpdate(new \DateTime('2015-08-16'));
        $fellowship->setDateLastComment(new \DateTime('2015-08-16'));
        $fellowship->setDatePublish(new \DateTime('2015-08-16'));

        for ($i = 1; $i < 5; ++$i) {
            /** @var Deck $deck */
            $deck = $this->getReference('test-deck-'.$i);
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
