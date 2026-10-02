<?php

declare(strict_types=1);

namespace App\Controller\UserProfile;

use App\Controller\CurrentUserTrait;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SaveProfileController extends AbstractController
{
    use CurrentUserTrait;

    /**
     * @var UserRepository
     */
    private $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/user/profile_save", name="user_profile_save", methods={"POST"})
     */
    public function __invoke(Request $request): RedirectResponse
    {
        /* @var $user \App\Entity\User */
        $user = $this->currentUser();
        $username = (string) filter_var($request->get('username'), FILTER_SANITIZE_STRING);
        if ($username !== $user->getUsername()) {
            $user_existing = $this->userRepository->findOneBy(['username' => $username]);
            if ($user_existing) {
                $this->get('session')->getFlashBag()->set('error', "Username {$username} is already taken.");

                return $this->redirect($this->generateUrl('user_profile_edit'));
            }
            $user->setUsername($username);
        }
        $email = (string) filter_var($request->get('email'), FILTER_SANITIZE_STRING);
        if ($email !== $user->getEmail()) {
            $user->setEmail($email);
        }
        $resume = (string) filter_var($request->get('resume'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $sphere_code = (string) filter_var($request->get('user_sphere_code'), FILTER_SANITIZE_STRING);
        $notifAuthor = $request->get('notif_author') ? true : false;
        $notifCommenter = $request->get('notif_commenter') ? true : false;
        $notifMention = $request->get('notif_mention') ? true : false;
        $shareDecks = $request->get('share_decks') ? true : false;
        $darkMode = $request->get('dark_mode') ? true : false;
        $user->setColor($sphere_code);
        $user->setResume($resume);
        $user->setIsNotifAuthor($notifAuthor);
        $user->setIsNotifCommenter($notifCommenter);
        $user->setIsNotifMention($notifMention);
        $user->setIsShareDecks($shareDecks);
        $user->setDarkMode($darkMode);
        $this->entityManager->flush();
        $this->get('session')->getFlashBag()->set('notice', 'Successfully saved your profile.');
        $response = $this->redirect($this->generateUrl('user_profile_edit'));
        // Persist the preference in a long-lived cookie so the theme can be applied
        // immediately (without a flash of the wrong theme) on this device, even though
        // pages are publicly cached and the canonical preference lives in the database.
        $cookie = Cookie::create('dark_mode', $darkMode ? '1' : '0', strtotime('+1 year'), '/', null, false, false);
        $response->headers->setCookie($cookie);

        return $response;
    }
}
