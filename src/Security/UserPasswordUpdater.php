<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\LegacyPasswordHasherInterface;

/**
 * Hashes the plain password of a user (registration, password change and reset, fixtures).
 */
class UserPasswordUpdater
{
    public function __construct(private readonly PasswordHasherFactoryInterface $passwordHasherFactory)
    {
    }

    public function hashPassword(User $user): void
    {
        $plainPassword = $user->getPlainPassword();
        if (null === $plainPassword || '' === $plainPassword) {
            return;
        }

        $hasher = $this->passwordHasherFactory->getPasswordHasher($user);
        if ($hasher instanceof LegacyPasswordHasherInterface) {
            // the legacy sha512 hasher (security.yaml) needs a per-user salt, generated as
            // FOSUserBundle did
            $salt = rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '=');
            $user->setSalt($salt);
            $user->setPassword($hasher->hash($plainPassword, $salt));
        } else {
            $user->setSalt(null);
            $user->setPassword($hasher->hash($plainPassword));
        }

        $user->eraseCredentials();
    }
}
