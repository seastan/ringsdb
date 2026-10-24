<?php

declare(strict_types=1);

namespace App\Controller\UserProfile;

use App\Controller\CurrentUserTrait;
use App\Helper\StringSanitizer;
use App\Model\SaveProfileDto;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class SaveProfileController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository
    ) {
    }

    #[Route(path: '/user/profile_save', name: 'user_profile_save', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] SaveProfileDto $payload = new SaveProfileDto()): RedirectResponse
    {
        /* @var $user \App\Entity\User */
        $user = $this->currentUser();
        $username = StringSanitizer::sanitize($payload->username);
        if ($username !== $user->getUsername()) {
            $user_existing = $this->userRepository->findOneBy(['username' => $username]);
            if ($user_existing instanceof \App\Entity\User) {
                $this->addFlash('error', "Username {$username} is already taken.");

                return $this->redirect($this->generateUrl('user_profile_edit'));
            }

            $user->setUsername($username);
        }

        $email = StringSanitizer::sanitize($payload->email);
        if ($email !== $user->getEmail()) {
            $user->setEmail($email);
        }

        $resume = StringSanitizer::sanitize($payload->resume, false);
        $sphere_code = StringSanitizer::sanitize($payload->userSphereCode);
        $notifAuthor = $payload->notifAuthor ?? false;
        $notifCommenter = $payload->notifCommenter ?? false;
        $notifMention = $payload->notifMention ?? false;
        $shareDecks = $payload->shareDecks ?? false;
        $darkMode = $payload->darkMode ?? false;
        $user->setColor($sphere_code);
        $user->setResume($resume);
        $user->setIsNotifAuthor($notifAuthor);
        $user->setIsNotifCommenter($notifCommenter);
        $user->setIsNotifMention($notifMention);
        $user->setIsShareDecks($shareDecks);
        $user->setDarkMode($darkMode);

        $this->entityManager->flush();
        $this->addFlash('notice', 'Successfully saved your profile.');
        $response = $this->redirect($this->generateUrl('user_profile_edit'));
        // Persist the preference in a long-lived cookie so the theme can be applied
        // immediately (without a flash of the wrong theme) on this device, even though
        // pages are publicly cached and the canonical preference lives in the database.
        $cookie = Cookie::create('dark_mode', $darkMode ? '1' : '0', strtotime('+1 year'), '/', null, false, false);
        $response->headers->setCookie($cookie);

        return $response;
    }
}
