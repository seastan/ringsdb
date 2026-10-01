<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Decklist;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CommentFixtures extends Fixture implements DependentFixtureInterface
{

    /**
     * @return array<int, class-string>
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
            DecklistFixtures::class
        ];
    }

    /**
     * @return void
     */
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $this->getReference('test-user');
        /** @var Decklist $decklist */
        $decklist = $this->getReference('test-decklist-1');

        $comment = new Comment();
        $comment->setText('Comment test');
        $comment->setDateCreation(new \DateTime('2015-08-16'));
        $comment->setUser($user);
        $comment->setDecklist($decklist);
        $comment->setIsHidden(false);

        $decklist->setDateUpdate(new \DateTime('2015-08-16'));
        $decklist->setDateLastComment(new \DateTime('2015-08-16'));
        $decklist->setNbcomments(1);

        $manager->persist($comment);
        $manager->flush();
    }
}