<?php

declare(strict_types=1);

namespace App\Controller\UserProfile;

use App\Entity\User;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class SendConfirmationEmailController extends AbstractController
{
    private MailerInterface $mailer;
    private UserManagerInterface $userManager;

    public function __construct(
        MailerInterface $mailer,
        UserManagerInterface $userManager
    ) {
        $this->mailer = $mailer;
        $this->userManager = $userManager;
    }

    /**
     * @Route("/user/remind/{username}", name="remind_email")
     */
    public function __invoke($username): Response
    {
        /** @var User|null $user */
        $user = $this->userManager->findUserByUsername($username);
        if (!$user) {
            throw new NotFoundHttpException("Cannot find user from username [{$username}]");
        }
        if (!$user->getConfirmationToken()) {
            return $this->render('User/remind-no-token.html.twig');
        }
        $this->mailer->sendConfirmationEmailMessage($user);
        $this->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());
        $url = $this->generateUrl('fos_user_registration_check_email');

        return $this->redirect($url);
    }
}
