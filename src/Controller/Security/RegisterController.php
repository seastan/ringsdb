<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Entity\User;
use App\Form\Security\RegistrationFormType;
use App\Security\TokenGenerator;
use App\Security\UserMailer;
use App\Security\UserPasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The account is created disabled, and enabled by the link of the confirmation email
 * (RegistrationConfirmController).
 */
class RegisterController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager, private UserPasswordUpdater $passwordUpdater, private TokenGenerator $tokenGenerator, private UserMailer $mailer)
    {
    }

    /**
     * @Route("/register/", name="fos_user_registration_register", methods={"GET", "POST"})
     */
    public function __invoke(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setEnabled(false);
            $user->setConfirmationToken($this->tokenGenerator->generateToken());
            $this->passwordUpdater->hashPassword($user);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->mailer->sendConfirmationEmailMessage($user);
            $request->getSession()->set(RegistrationCheckEmailController::SESSION_EMAIL, $user->getEmail());
            $this->addFlash('success', 'The user has been created successfully.');

            return $this->redirectToRoute('fos_user_registration_check_email');
        }

        return $this->render('Security/Registration/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
