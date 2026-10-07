<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Form\Security\ResettingFormType;
use App\Repository\UserRepository;
use App\Security\LoginManager;
use App\Security\UserPasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The link of the password reset email: the new password form. The token can only be used once;
 * the user is logged in afterwards.
 */
class ResettingResetController extends AbstractController
{
    /**
     * How long the link of the email is valid, in seconds.
     */
    private const TOKEN_TTL = 86400;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordUpdater $passwordUpdater,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoginManager $loginManager
    ) {
    }

    /**
     * @Route("/resetting/reset/{token}", name="fos_user_resetting_reset", methods={"GET", "POST"})
     */
    public function __invoke(Request $request, string $token): Response
    {
        $user = $this->userRepository->findOneByConfirmationToken($token);
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        if (!$user->isPasswordRequestNonExpired(self::TOKEN_TTL)) {
            return $this->redirectToRoute('fos_user_resetting_request');
        }

        $form = $this->createForm(ResettingFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setConfirmationToken(null);
            $user->setPasswordRequestedAt(null);
            $user->setEnabled(true);
            $this->passwordUpdater->hashPassword($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'The password has been reset successfully.');
            $this->loginManager->logInUser($user);

            return $this->redirectToRoute('fos_user_profile_show');
        }

        return $this->render('Security/Resetting/reset.html.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }
}
