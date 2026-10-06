<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The account status checks of Symfony 3.4's UserChecker (on AdvancedUserInterface, removed in
 * Symfony 5): refuses the accounts blocked by the admin, not confirmed yet, or expired. With
 * hide_user_not_found (security.yaml), the login page shows "Invalid credentials." for them.
 */
class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isAccountNonLocked()) {
            $this->fail(new LockedException('User account is locked.'), $user);
        }

        if (!$user->isEnabled()) {
            $this->fail(new DisabledException('User account is disabled.'), $user);
        }

        if (!$user->isAccountNonExpired()) {
            $this->fail(new AccountExpiredException('User account has expired.'), $user);
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if ($user instanceof User && !$user->isCredentialsNonExpired()) {
            $this->fail(new CredentialsExpiredException('User credentials have expired.'), $user);
        }
    }

    /**
     * @return never
     */
    private function fail(AccountStatusException $exception, User $user): void
    {
        $exception->setUser($user);

        throw $exception;
    }
}
