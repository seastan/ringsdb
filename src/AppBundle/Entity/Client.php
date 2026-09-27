<?php

namespace AppBundle\Entity;

use FOS\OAuthServerBundle\Entity\Client as BaseClient;
use Doctrine\ORM\Mapping as ORM;

class Client extends BaseClient {
    protected $id;
    /**
     * @var string
     */
    protected $name;

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
    }
}
