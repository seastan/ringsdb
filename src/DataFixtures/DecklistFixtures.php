<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Deck;
use App\Model\DecklistFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DecklistFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly DecklistFactory $decklistFactory,
    ) {
    }

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
        for ($i = 1; $i < 5; ++$i) {
            /** @var Deck $deck */
            $deck = $this->getReference('test-deck-'.$i, Deck::class);

            $decklist = $this->decklistFactory->createDecklistFromDeck($deck, $deck->getName(), 'Hello World');
            $decklist->setDateCreation(new \DateTime('2015-08-16'));
            $decklist->setDateUpdate(new \DateTime('2015-08-16'));
            $decklist->setDateLastComment(new \DateTime('2015-08-16'));

            $deck->setDateUpdate(new \DateTime('2015-08-16'));

            $manager->persist($decklist);
            $this->addReference('test-decklist-'.$i, $decklist);
        }

        $manager->flush();
    }
}
