<?php

namespace App\Controller;

use App\Entity\User;

/**
 * For the controllers of the pages that require a logged in user.
 */
trait CurrentUserTrait
{
    /**
     * The logged in user; an anonymous visitor is sent to the login page (access denied), as
     * access_control does for most of these routes.
     */
    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        return $user;
    }
}
