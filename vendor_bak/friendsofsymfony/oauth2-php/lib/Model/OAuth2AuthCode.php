<?php

namespace OAuth2\Model;

class OAuth2AuthCode extends OAuth2Token implements IOAuth2AuthCode
{
    /**
     * @var null|string
     */
    private $redirectUri;

    /**
     * @param string       $clientId
     * @param string       $token
     * @param null|integer $expiresAt
     * @param null|string  $scope
     * @param mixed        $data
     * @param null|string  $redirectUri
     */
    public function __construct($clientId, $token, $expiresAt = null, $scope = null, $data = null, $redirectUri = null)
    {
        parent::__construct($clientId, $token, $expiresAt, $scope, $data);
        $this->setRedirectUri($redirectUri);
    }

    /**
     * @param null|string $uri
     */
    public function setRedirectUri($uri)
    {
        $this->redirectUri = $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }
}
