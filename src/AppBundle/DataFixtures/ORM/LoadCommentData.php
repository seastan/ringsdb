<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Comment;
use AppBundle\Entity\Decklist;
use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadCommentData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
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
            LoadDecklistData::class
        ];
    }

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