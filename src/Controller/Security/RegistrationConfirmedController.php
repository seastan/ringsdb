<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\CurrentUserTrait;
use App\Security\LoginManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationConfirmedController extends AbstractController
{
    use CurrentUserTrait;

    /**
     * @Route("/register/confirmed", name="fos_user_registration_confirmed", methods={"GET"})
     */
    public function __invoke(LoginManager $loginManager): Response
    {
        return $this->render('Security/Registration/confirmed.html.twig', [
            'user' => $this->currentUser(),
            'targetUrl' => $loginManager->getTargetPath(),
        ]);
    }
}
