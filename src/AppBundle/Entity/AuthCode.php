<?php

namespace AppBundle\Entity;

use FOS\OAuthServerBundle\Entity\AuthCode as BaseAuthCode;
use Doctrine\ORM\Mapping as ORM;

class AuthCode extends BaseAuthCode {
    protected $id;
    /**
     * @var \FOS\OAuthServerBundle\Model\ClientInterface
     */
    protected $client;

    /**
     * @var \Symfony\Component\Security\Core\User\UserInterface
     */
    protected $user;
}
