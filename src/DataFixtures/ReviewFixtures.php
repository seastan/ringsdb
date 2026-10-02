<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Card;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ReviewFixtures extends Fixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface|null
     */
    private $container;

    public function setContainer(?ContainerInterface $container = null): void
    {
        $this->container = $container;
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
        $user = $this->getReference('test-user');
        $card = $manager->getRepository(Card::class)->findOneBy(['code' => '01001']);
        if (!$card instanceof Card || null === $this->container) {
            throw new \LogicException('Card 01001 or the container is missing.');
        }

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
