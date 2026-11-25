<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Model\ResettingDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

class ResettingCheckEmailController extends AbstractController
{
    #[Route(path: '/resetting/check-email', name: 'fos_user_resetting_check_email', methods: ['GET'])]
    public function __invoke(#[MapQueryString] ResettingDto $query = new ResettingDto()): Response
    {
        if ('' === (string) $query->username) {
            // the user does not come from ResettingSendEmailController
            return $this->redirectToRoute('fos_user_resetting_request');
        }

        return $this->render('Security/Resetting/check_email.html.twig', [
            'tokenLifetime' => ceil(ResettingSendEmailController::RETRY_TTL / 3600),
        ]);
    }
}
