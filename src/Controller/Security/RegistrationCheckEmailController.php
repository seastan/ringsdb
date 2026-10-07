<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * "An email has been sent", after the registration or a new confirmation email
 * (SendConfirmationEmailController).
 */
class RegistrationCheckEmailController extends AbstractController
{
    /**
     * The session key of the email the confirmation was sent to.
     */
    public const SESSION_EMAIL = 'registration_check_email';

    #[Route(path: '/register/check-email', name: 'fos_user_registration_check_email', methods: ['GET'])]
    public function __invoke(Request $request, UserRepository $userRepository): Response
    {
        $session = $request->getSession();
        $email = (string) $session->get(self::SESSION_EMAIL);
        if ('' === $email) {
            return $this->redirectToRoute('fos_user_registration_register');
        }

        $session->remove(self::SESSION_EMAIL);
        $user = $userRepository->findOneByEmail($email);
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        return $this->render('Security/Registration/check_email.html.twig', [
            'user' => $user,
        ]);
    }
}
