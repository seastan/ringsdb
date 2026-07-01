<?php

namespace OAuth2\Model;

class OAuth2Client implements IOAuth2Client
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $redirectUris;

    /**
     * @var null|string
     */
    private $secret;

    /**
     * @param string $id
     * @param null   $secret
     * @param array  $redirectUris
     */
    public function __construct($id, $secret = null, array $redirectUris = array())
    {
        $this->setPublicId($id);
        $this->setSecret($secret);
        $this->setRedirectUris($redirectUris);
    }

    /**
     * @param string $id
     */
    public function setPublicId($id)
    {
        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicId()
    {
        return $this->id;
    }

    /**
     * @param string $secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    /**
     * @param mixed $secret
     *
     * @return boolean
     */
    public function checkSecret($secret)
    {
        return $this->secret === null || $secret === $this->secret;
    }

    /**
     * @param array $redirectUris
     */
    public function setRedirectUris(array $redirectUris)
    {
        $this->redirectUris = $redirectUris;
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectUris()
    {
        return $this->redirectUris;
    }
}
