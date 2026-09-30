<?php

namespace App\DataFixtures\ORM;

use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface|null
     */
    private $container;

    /**
     * @return void
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return void
     */
    public function load(ObjectManager $manager)
    {
        if ($this->container === null) {
            throw new \LogicException('The container is not set.');
        }
        $userManager = $this->container->get('fos_user.user_manager');

        /** @var User $user */
        $user = $userManager->createUser();
        $user->setUsername('test');
        $user->setEmail('test@example.com');
        $user->setPlainPassword('test');
        $user->setEnabled(true);
        $user->setDateCreation(new \DateTime('2015-08-16'));
        $user->setDateUpdate(new \DateTime('2015-08-16'));

        $userManager->updateUser($user);

        $this->addReference('test-user', $user);

        /** @var User $admin */
        $admin = $userManager->createUser();
        $admin->setUsername('admin');
        $admin->setEmail('admin@example.com');
        $admin->setPlainPassword('admin');
        $admin->setEnabled(true);
        $admin->addRole('ROLE_ADMIN');
        $admin->setDateCreation(new \DateTime('2015-08-16'));
        $admin->setDateUpdate(new \DateTime('2015-08-16'));

        $userManager->updateUser($admin);

        $this->addReference('admin-user', $admin);
    }
}