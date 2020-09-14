<?php

namespace OAuth2\Model;

interface IOAuth2AuthCode extends IOAuth2Token
{
    /**
     * @return string
     */
    public function getRedirectUri();
}
