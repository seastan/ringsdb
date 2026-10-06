<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Repository\UserRepository;
use App\Security\LoginManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The link of the confirmation email: enables the account and logs the user in. The token can
 * only be used once.
 */
class RegistrationConfirmController extends AbstractController
{
    /**
     * @Route("/register/confirm/{token}", name="fos_user_registration_confirm", methods={"GET"})
     */
    public function __invoke(
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        LoginManager $loginManager
    ): Response {
        $user = $userRepository->findOneByConfirmationToken($token);
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);

        $entityManager->flush();
        $loginManager->logInUser($user);

        return $this->redirectToRoute('fos_user_registration_confirmed');
    }
}
