<?php

namespace AppBundle\Entity;

use FOS\OAuthServerBundle\Entity\RefreshToken as BaseRefreshToken;
use Doctrine\ORM\Mapping as ORM;

class RefreshToken extends BaseRefreshToken {
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
