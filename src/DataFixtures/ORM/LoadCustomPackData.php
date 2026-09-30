<?php

namespace App\DataFixtures\ORM;

use App\Entity\Card;
use App\Entity\User;
use App\Entity\UserCustomPack;
use App\Entity\UserCustomPackCard;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\DataFixtures\ORM\LoadUserData;

class LoadCustomPackData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @return array<int, class-string>
     */
    public function getDependencies()
    {
        return [
            LoadUserData::class,
        ];
    }

    /**
     * @return void
     */
    public function load(ObjectManager $manager)
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
            $entry = new UserCustomPackCard();
            $entry->setCustomPack($pack);
            $card = $cardRepo->findOneBy(['code' => $code]);
            if (!$card instanceof Card) {
                throw new \LogicException("Card $code is missing.");
            }
            $entry->setCard($card);
            $entry->setQuantity($quantity);
            $pack->addCard($entry);
        }

        $manager->persist($pack);
        $manager->flush();

        $this->addReference('test-custom-pack', $pack);
    }
}
