<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use App\Security\UserPasswordUpdater;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    private UserPasswordUpdater $passwordUpdater;

    public function __construct(UserPasswordUpdater $passwordUpdater)
    {
        $this->passwordUpdater = $passwordUpdater;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setUsername('test');
        $user->setEmail('test@example.com');
        $user->setPlainPassword('test');
        $user->setEnabled(true);
        $user->setDateCreation(new \DateTime('2015-08-16'));
        $user->setDateUpdate(new \DateTime('2015-08-16'));

        $this->passwordUpdater->hashPassword($user);
        $manager->persist($user);

        $this->addReference('test-user', $user);

        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@example.com');
        $admin->setPlainPassword('admin');
        $admin->setEnabled(true);
        $admin->addRole('ROLE_ADMIN');
        $admin->setDateCreation(new \DateTime('2015-08-16'));
        $admin->setDateUpdate(new \DateTime('2015-08-16'));

        $this->passwordUpdater->hashPassword($admin);
        $manager->persist($admin);

        $manager->flush();

        $this->addReference('admin-user', $admin);
    }
}
