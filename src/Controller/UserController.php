<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\CardRepository;
use App\Repository\DecklistRepository;
use App\Repository\FellowshipRepository;
use App\Repository\QuestlogRepository;
use App\Repository\SphereRepository;
use App\Repository\UserRepository;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    use CurrentUserTrait;
    /**
     * @var int
     */
    private $cacheExpiration;
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(int $cacheExpiration, UserRepository $userRepository)
    {
        $this->cacheExpiration = $cacheExpiration;
        $this->userRepository = $userRepository;
    }

    /*
     * displays details about a user and the list of decklists he published
     */
    /**
     * @return Response
     *
     * @Route(
     *     "/user/profile/{user_id}/{user_name}/{page}",
     *     name="user_profile_public",
     *     methods={"GET"},
     *     requirements={"user_id"="\d+"},
     *     defaults={"page"=1}
     * )
     */
    public function publicProfileAction($user_id, $user_name, $page, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->userRepository->find($user_id);
        if (!$user) {
            throw new NotFoundHttpException('No such user.');
        }

        return $this->render('User/profile_public.html.twig', ['user' => $user]);
    }

    /**
     * @return Response
     *
     * @Route("/user/profile_edit", name="user_profile_edit", methods={"GET"})
     */
    public function editProfileAction(SphereRepository $sphereRepository)
    {
        $user = $this->getUser();
        $spheres = $sphereRepository->findAll();

        return $this->render('User/profile_edit.html.twig', ['user' => $user, 'spheres' => $spheres]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/user/profile_save", name="user_profile_save", methods={"POST"})
     */
    public function saveProfileAction(Request $request)
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
        $this->getDoctrine()->getManager()->flush();
        $this->get('session')->getFlashBag()->set('notice', 'Successfully saved your profile.');
        $response = $this->redirect($this->generateUrl('user_profile_edit'));
        // Persist the preference in a long-lived cookie so the theme can be applied
        // immediately (without a flash of the wrong theme) on this device, even though
        // pages are publicly cached and the canonical preference lives in the database.
        $cookie = Cookie::create('dark_mode', $darkMode ? '1' : '0', strtotime('+1 year'), '/', null, false, false);
        $response->headers->setCookie($cookie);

        return $response;
    }

    /**
     * @return Response
     *
     * @Route("/api/private/user/info", name="api_private_user_info")
     */
    public function infoAction(Request $request, CardRepository $cardRepository, DecklistRepository $decklistRepository, FellowshipRepository $fellowshipRepository, QuestlogRepository $questlogRepository)
    {
        $jsonp = $request->query->get('jsonp');
        $decklist_id = $request->query->get('decklist_id');
        $fellowship_id = $request->query->get('fellowship_id');
        $questlog_id = $request->query->get('questlog_id');
        $card_id = $request->query->get('card_id');
        $content = null;
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $user = $this->currentUser();
            $user_id = $user->getId();
            $public_profile_url = $this->generateUrl('user_profile_public', ['user_id' => $user_id, 'user_name' => urlencode($user->getUsername())]);
            $content = ['public_profile_url' => $public_profile_url, 'id' => $user_id, 'name' => $user->getUsername(), 'sphere' => $user->getColor(), 'donation' => $user->getDonation(), 'owned_packs' => $user->getOwnedPacks(), 'dark_mode' => $user->getDarkMode(), 'art_preferences' => $user->getArtPreferences()];
            if (isset($decklist_id)) {
                /* @var $em \Doctrine\ORM\EntityManager */
                $em = $this->getDoctrine()->getManager();
                /* @var $decklist \App\Entity\Decklist */
                $decklist = $decklistRepository->find($decklist_id);
                if ($decklist) {
                    $decklist_id = $decklist->getId();
                    $dbh = $this->getDoctrine()->getConnection();
                    $content['is_liked'] = (bool) $dbh->executeQuery("SELECT\n        \t\t\t\tcount(*)\n        \t\t\t\tFROM decklist d\n        \t\t\t\tJOIN vote v ON v.decklist_id = d.id\n        \t\t\t\tWHERE v.user_id = ?\n        \t\t\t\tAND d.id = ?", [$user_id, $decklist_id])->fetch(\PDO::FETCH_NUM)[0];
                    $content['is_favorite'] = (bool) $dbh->executeQuery("SELECT\n        \t\t\t\tcount(*)\n        \t\t\t\tFROM decklist d\n        \t\t\t\tJOIN favorite f ON f.decklist_id = d.id\n        \t\t\t\tWHERE f.user_id = ?\n        \t\t\t\tAND d.id = ?", [$user_id, $decklist_id])->fetch(\PDO::FETCH_NUM)[0];
                    $content['is_author'] = $user_id == $decklist->getUser()->getId();
                    $content['can_delete'] = 0 == $decklist->getNbcomments() && 0 == $decklist->getNbfavorites() && 0 == $decklist->getNbVotes();
                }
            }
            if (isset($fellowship_id)) {
                /* @var $em \Doctrine\ORM\EntityManager */
                $em = $this->getDoctrine()->getManager();
                /* @var $fellowship \App\Entity\Fellowship */
                $fellowship = $fellowshipRepository->find($fellowship_id);
                if ($fellowship) {
                    $fellowship_id = $fellowship->getId();
                    $dbh = $this->getDoctrine()->getConnection();
                    $content['is_liked'] = (bool) $dbh->executeQuery("SELECT\n        \t\t\t\tcount(*)\n        \t\t\t\tFROM fellowship d\n        \t\t\t\tJOIN fellowship_vote v ON v.fellowship_id = d.id\n        \t\t\t\tWHERE v.user_id = ?\n        \t\t\t\tAND d.id = ?", [$user_id, $fellowship_id])->fetch(\PDO::FETCH_NUM)[0];
                    $content['is_favorite'] = (bool) $dbh->executeQuery("SELECT\n        \t\t\t\tcount(*)\n        \t\t\t\tFROM fellowship d\n        \t\t\t\tJOIN fellowship_favorite f ON f.fellowship_id = d.id\n        \t\t\t\tWHERE f.user_id = ?\n        \t\t\t\tAND d.id = ?", [$user_id, $fellowship_id])->fetch(\PDO::FETCH_NUM)[0];
                    $content['is_author'] = $user_id == $fellowship->getUser()->getId();
                    $content['can_delete'] = 0 == $fellowship->getNbcomments() && 0 == $fellowship->getNbfavorites() && 0 == $fellowship->getNbVotes();
                }
            }
            if (isset($questlog_id)) {
                /* @var $em \Doctrine\ORM\EntityManager */
                $em = $this->getDoctrine()->getManager();
                /* @var $questlog \App\Entity\Questlog */
                $questlog = $questlogRepository->find($questlog_id);
                if ($questlog) {
                    $questlog_id = $questlog->getId();
                    $dbh = $this->getDoctrine()->getConnection();
                    $content['is_liked'] = (bool) $dbh->executeQuery("SELECT\n        \t\t\t\tcount(*)\n        \t\t\t\tFROM questlog d\n        \t\t\t\tJOIN questlog_vote v ON v.questlog_id = d.id\n        \t\t\t\tWHERE v.user_id = ?\n        \t\t\t\tAND d.id = ?", [$user_id, $questlog_id])->fetch(\PDO::FETCH_NUM)[0];
                    $content['is_favorite'] = (bool) $dbh->executeQuery("SELECT\n        \t\t\t\tcount(*)\n        \t\t\t\tFROM questlog d\n        \t\t\t\tJOIN questlog_favorite f ON f.questlog_id = d.id\n        \t\t\t\tWHERE f.user_id = ?\n        \t\t\t\tAND d.id = ?", [$user_id, $questlog_id])->fetch(\PDO::FETCH_NUM)[0];
                    $content['is_author'] = $user_id == $questlog->getUser()->getId();
                    $content['can_delete'] = 0 == $questlog->getNbcomments() && 0 == $questlog->getNbfavorites() && 0 == $questlog->getNbVotes();
                }
            }
            if (isset($card_id)) {
                /* @var $em \Doctrine\ORM\EntityManager */
                $em = $this->getDoctrine()->getManager();
                /* @var $card \App\Entity\Card */
                $card = $cardRepository->find($card_id);
                if ($card) {
                    $reviews = $card->getReviews();
                    /* @var $review \App\Entity\Review */
                    foreach ($reviews as $review) {
                        if ($review->getUser()->getId() === $user->getId()) {
                            $content['review_id'] = $review->getId();
                            $content['review_text'] = $review->getTextMd();
                        }
                    }
                }
            }
        }
        $content = json_encode($content);
        $response = new Response();
        $response->setPrivate();
        if (isset($jsonp)) {
            $content = "{$jsonp}({$content})";
            $response->headers->set('Content-Type', 'application/javascript');
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }
        $response->setContent($content);

        return $response;
    }

    /**
     * @return Response
     *
     * @Route("/user/remind/{username}", name="remind_email")
     */
    public function remindAction($username, MailerInterface $userMailer, UserManagerInterface $userManager)
    {
        /** @var User|null $user */
        $user = $userManager->findUserByUsername($username);
        if (!$user) {
            throw new NotFoundHttpException("Cannot find user from username [{$username}]");
        }
        if (!$user->getConfirmationToken()) {
            return $this->render('User/remind-no-token.html.twig');
        }
        $userMailer->sendConfirmationEmailMessage($user);
        $this->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());
        $url = $this->generateUrl('fos_user_registration_check_email');

        return $this->redirect($url);
    }
}
