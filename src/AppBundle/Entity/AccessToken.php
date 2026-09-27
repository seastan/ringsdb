<?php

namespace AppBundle\Entity;

use FOS\OAuthServerBundle\Entity\AccessToken as BaseAccessToken;
use Doctrine\ORM\Mapping as ORM;

class AccessToken extends BaseAccessToken {
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
