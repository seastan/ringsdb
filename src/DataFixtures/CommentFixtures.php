<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Decklist;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CommentFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * @return array<int, class-string>
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            DecklistFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference('test-user');
        /** @var Decklist $decklist */
        $decklist = $this->getReference('test-decklist-1');

        $comment = new Comment($user, $decklist, 'Comment test');
        $comment->setDateCreation(new \DateTime('2015-08-16'));

        $decklist->setDateUpdate(new \DateTime('2015-08-16'));
        $decklist->setDateLastComment(new \DateTime('2015-08-16'));
        $decklist->setNbcomments(1);

        $manager->persist($comment);
        $manager->flush();
    }
}
