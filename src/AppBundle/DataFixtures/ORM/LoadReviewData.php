<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Review;
use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadReviewData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
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
        /** @var User $user */
        $user = $this->getReference('test-user');
        $card = $manager->getRepository('AppBundle:Card')->findOneBy(['code' => '01001']);

        $textMd = "Aragorn is a **great** leader.\n\nHe readies after committing to the quest.";

        $review = new Review();
        $review->setCard($card);
        $review->setUser($user);
        $review->setTextMd($textMd);
        $review->setTextHtml($this->container->get('texts')->markdown($textMd));
        $review->setNbVotes(0);
        $review->setDateCreation(new \DateTime('2015-08-16'));
        $review->setDateUpdate(new \DateTime('2015-08-16'));
        $review->setDateLastComment(new \DateTime('2015-08-16'));

        $manager->persist($review);
        $manager->flush();

        $this->addReference('test-review', $review);
    }
}
