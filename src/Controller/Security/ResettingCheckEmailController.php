<?php

declare(strict_types=1);

namespace App\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ResettingCheckEmailController extends AbstractController
{
    /**
     * @Route("/resetting/check-email", name="fos_user_resetting_check_email", methods={"GET"})
     */
    public function __invoke(Request $request): Response
    {
        if ('' === (string) $request->query->get('username')) {
            // the user does not come from ResettingSendEmailController
            return $this->redirectToRoute('fos_user_resetting_request');
        }

        return $this->render('Security/Resetting/check_email.html.twig', [
            'tokenLifetime' => ceil(ResettingSendEmailController::RETRY_TTL / 3600),
        ]);
    }
}
