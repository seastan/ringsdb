<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * The registration confirmation and password reset emails. Their templates define a "subject"
 * block and a "body_text" block.
 */
class UserMailer
{
    public function __construct(private MailerInterface $mailer, private UrlGeneratorInterface $router, private Environment $twig, private string $senderAddress, private string $senderName)
    {
    }

    public function sendConfirmationEmailMessage(User $user): void
    {
        $url = $this->router->generate('fos_user_registration_confirm', ['token' => $user->getConfirmationToken()], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->sendMessage('Security/Registration/email.txt.twig', $user, $url);
    }

    public function sendResettingEmailMessage(User $user): void
    {
        $url = $this->router->generate('fos_user_resetting_reset', ['token' => $user->getConfirmationToken()], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->sendMessage('Security/Resetting/email.txt.twig', $user, $url);
    }

    private function sendMessage(string $templateName, User $user, string $confirmationUrl): void
    {
        $context = ['user' => $user, 'confirmationUrl' => $confirmationUrl];
        $template = $this->twig->load($templateName);

        $message = (new Email())
            ->subject($template->renderBlock('subject', $context))
            ->from(new Address($this->senderAddress, $this->senderName))
            ->to((string) $user->getEmail())
            ->text($template->renderBlock('body_text', $context));

        $this->mailer->send($message);
    }
}
