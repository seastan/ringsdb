<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\CurrentUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The account page (username and email). The site's own profile is /user/profile_edit.
 */
class ProfileShowController extends AbstractController
{
    use CurrentUserTrait;

    #[Route(path: '/profile/', name: 'fos_user_profile_show', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('Security/Profile/show.html.twig', [
            'user' => $this->currentUser(),
        ]);
    }
}
