<?php

declare(strict_types=1);

namespace App\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * The login form. The firewall (security.yaml) handles its submission (/login_check) and the
 * logout (/logout), see config/routes/security.yaml.
 */
class LoginController extends AbstractController
{
    /**
     * @Route("/login", name="fos_user_security_login", methods={"GET", "POST"})
     */
    public function __invoke(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('Security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }
}
