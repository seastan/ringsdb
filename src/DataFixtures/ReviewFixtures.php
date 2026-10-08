<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Card;
use App\Entity\Review;
use App\Entity\User;
use App\Services\Texts;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ReviewFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly Texts $texts
    ) {
    }

    /**
     * @return array<int, class-string>
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference('test-user', User::class);
        $card = $manager->getRepository(Card::class)->findOneBy(['code' => '01001']);
        if (!$card instanceof Card) {
            throw new \LogicException('Card 01001 is missing.');
        }

        $textMd = "Aragorn is a **great** leader.\n\nHe readies after committing to the quest.";
        $textHtml = $this->texts->markdown($textMd);

        $review = new Review($user, $card, $textMd, $textHtml);
        $review->setDateCreation(new \DateTime('2015-08-16'));
        $review->setDateUpdate(new \DateTime('2015-08-16'));
        $review->setDateLastComment(new \DateTime('2015-08-16'));

        $manager->persist($review);
        $manager->flush();

        $this->addReference('test-review', $review);
    }
}
