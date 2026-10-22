<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Repository\UserRepository;
use App\Security\TokenGenerator;
use App\Security\UserMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Sends the email with the password reset link. The answer is the same whether the user exists
 * or not.
 */
class ResettingSendEmailController extends AbstractController
{
    /**
     * A new request is ignored (no email) until the previous one is that old, in seconds.
     */
    public const RETRY_TTL = 7200;

    /**
     * @Route("/resetting/send-email", name="fos_user_resetting_send_email", methods={"POST"})
     */
    public function __invoke(
        Request $request,
        UserRepository $userRepository,
        TokenGenerator $tokenGenerator,
        UserMailer $mailer,
        EntityManagerInterface $entityManager
    ): Response {
        $username = (string) $request->request->get('username');
        $user = $userRepository->findOneByUsernameOrEmail($username);

        if ($user instanceof \App\Entity\User && !$user->isPasswordRequestNonExpired(self::RETRY_TTL)) {
            if (null === $user->getConfirmationToken()) {
                $user->setConfirmationToken($tokenGenerator->generateToken());
            }

            $mailer->sendResettingEmailMessage($user);
            $user->setPasswordRequestedAt(new \DateTime());
            $entityManager->flush();
        }

        return $this->redirectToRoute('fos_user_resetting_check_email', ['username' => $username]);
    }
}
