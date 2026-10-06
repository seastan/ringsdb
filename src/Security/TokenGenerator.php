<?php

declare(strict_types=1);

namespace App\Security;

/**
 * The tokens of the registration confirmation and password reset links.
 */
class TokenGenerator
{
    public function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
