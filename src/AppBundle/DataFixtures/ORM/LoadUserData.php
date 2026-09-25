<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
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
    }
}