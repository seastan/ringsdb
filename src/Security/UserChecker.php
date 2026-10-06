<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Refuses the accounts whose registration is not confirmed yet. With hide_user_not_found
 * (security.yaml), the login page shows "Invalid credentials." for them too.
 *
 * The "locked" flag set by the admin's "Block" button is not checked: blocking has had no effect
 * since FOSUserBundle 2 (see MIGRATION.md, "Admin area").
 */
class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof User && !$user->isEnabled()) {
            $exception = new DisabledException('User account is disabled.');
            $exception->setUser($user);

            throw $exception;
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
