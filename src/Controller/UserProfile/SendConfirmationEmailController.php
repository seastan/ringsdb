<?php

declare(strict_types=1);

namespace App\Controller\UserProfile;

use App\Controller\Security\RegistrationCheckEmailController;
use App\Repository\UserRepository;
use App\Security\UserMailer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class SendConfirmationEmailController extends AbstractController
{
    public function __construct(private readonly UserMailer $mailer, private readonly UserRepository $userRepository)
    {
    }

    /**
     * @Route("/user/remind/{username}", name="remind_email")
     */
    public function __invoke(Request $request, string $username): Response
    {
        $user = $this->userRepository->findOneByUsername($username);
        if (!$user instanceof \App\Entity\User) {
            throw new NotFoundHttpException("Cannot find user from username [{$username}]");
        }

        if (!$user->getConfirmationToken()) {
            return $this->render('User/remind-no-token.html.twig');
        }

        $this->mailer->sendConfirmationEmailMessage($user);
        $request->getSession()->set(RegistrationCheckEmailController::SESSION_EMAIL, $user->getEmail());
        $url = $this->generateUrl('fos_user_registration_check_email');

        return $this->redirect($url);
    }
}
