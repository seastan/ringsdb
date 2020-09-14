<?php

namespace OAuth2\Model;

class OAuth2Token implements IOAuth2Token
{
    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $token;

    /**
     * @var null|integer
     */
    private $expiresAt;

    /**
     * @var null|string
     */
    private $scope;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @param string       $clientId
     * @param string       $token
     * @param null|integer $expiresAt
     * @param null|string  $scope
     * @param mixed   $data
     */
    public function __construct($clientId, $token, $expiresAt = null, $scope = null, $data = null)
    {
        $this->setClientId($clientId);
        $this->setToken($token);
        $this->setExpiresAt($expiresAt);
        $this->setScope($scope);
        $this->setData($data);
    }

    /**
     * @param string $id
     */
    public function setClientId($id)
    {
        $this->clientId = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param null|integer $timestamp
     */
    public function setExpiresAt($timestamp)
    {
        $this->expiresAt = $timestamp;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpiresIn()
    {
        if ($this->expiresAt) {
            return $this->expiresAt - time();
        } else {
            return PHP_INT_MAX;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasExpired()
    {
        return time() > $this->expiresAt;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * {@inheritdoc}
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param null|string $scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * {@inheritdoc}
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param null|string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }
}
