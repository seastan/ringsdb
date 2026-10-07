<?php

declare(strict_types=1);

namespace App\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The password reset form: asks for the username or the email.
 */
class ResettingRequestController extends AbstractController
{
    #[Route(path: '/resetting/request', name: 'fos_user_resetting_request', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('Security/Resetting/request.html.twig');
    }
}
