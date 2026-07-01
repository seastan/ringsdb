<?php

namespace OAuth2\Model;

interface IOAuth2Token
{
    /**
     * @return string
     */
    public function getClientId();

    /**
     * @return integer
     */
    public function getExpiresIn();

    /**
     * @return boolean
     */
    public function hasExpired();

    /**
     * @return string
     */
    public function getToken();

    /**
     * @return null|string
     */
    public function getScope();

    /**
     * @return mixed
     */
    public function getData();
}
