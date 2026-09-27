<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\User;
use AppBundle\Entity\UserCustomPack;
use AppBundle\Entity\UserCustomPackCard;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadCustomPackData extends AbstractFixture implements DependentFixtureInterface
{
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
        $cardRepo = $manager->getRepository('AppBundle:Card');

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
            $entry->setCard($cardRepo->findOneBy(['code' => $code]));
            $entry->setQuantity($quantity);
            $pack->addCard($entry);
        }

        $manager->persist($pack);
        $manager->flush();

        $this->addReference('test-custom-pack', $pack);
    }
}
