<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Card;
use App\Entity\User;
use App\Entity\UserCustomPack;
use App\Entity\UserCustomPackCard;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CustomPackFixtures extends Fixture implements DependentFixtureInterface
{
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
        $cardRepo = $manager->getRepository(Card::class);

        $pack = new UserCustomPack();
        $pack->setUser($user);
        $pack->setName('Test Custom Pack');
        $pack->setCode('custom_test');
        $pack->setIsEnabled(true);
        $pack->setIsPublished(true);
        $pack->setCreatedAt(new \DateTime('2015-08-16'));
        $pack->setUpdatedAt(new \DateTime('2015-08-16'));

        foreach (['01001' => 1, '01016' => 3] as $code => $quantity) {
            $card = $cardRepo->findOneBy(['code' => $code]);
            if (!$card instanceof Card) {
                throw new \LogicException("Card $code is missing.");
            }
            $entry = new UserCustomPackCard($pack, $card, $quantity);
            $pack->addCard($entry);
        }

        $manager->persist($pack);
        $manager->flush();

        $this->addReference('test-custom-pack', $pack);
    }
}
