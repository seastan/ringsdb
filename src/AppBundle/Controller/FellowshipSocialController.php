<?php
namespace AppBundle\Controller;

use AppBundle\Entity\Comment;
use AppBundle\Entity\Deck;
use AppBundle\Entity\Decklist;
use AppBundle\Entity\Fellowshipcomment;
use AppBundle\Entity\User;
use AppBundle\Form\DecklistType;
use AppBundle\Model\DecklistManager;
use AppBundle\Services\Pagination;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SocialFellowshipController extends Controller {
    /*
     * Checks to see if a fellowship can be published in its current saved state
     * If it is, displays the fellowship edit form for initial publication
     */
    public function publishFormAction($fellowship_id) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException("You must be logged in for this operation.");
        }

        /* @var $fellowship \AppBundle\Entity\Fellowship */
        $fellowship = $em->getRepository('AppBundle:Fellowship')->find($fellowship_id);
        if (!$fellowship || $fellowship->getUser()->getId() != $user->getId()) {
            throw new AccessDeniedHttpException("You don't have access to this fellowship.");
        }

        $problem = $this->get('fellowship_validation_helper')->findProblem($fellowship);
        if ($problem) {
            $this->get('session')->getFlashBag()->set('error', "This fellowship cannot be published because it is invalid.");

            return $this->redirect($this->generateUrl('fellowship_view', [ 'fellowship_id' => $fellowship->getId() ]));
        }

        /*
        $content = [
            'main' => $deck->getSlots()->getContent(),
            'side' => $deck->getSideslots()->getContent(),
        ];

        $new_content = json_encode($content);
        $new_signature = md5($new_content);
        $old_decklists = $this->getDoctrine()->getRepository('AppBundle:Decklist')->findBy([ 'signature' => $new_signature ]);

        foreach ($old_decklists as $decklist) {
            $deck_content = [
                'main' => $decklist->getSlots()->getContent(),
                'side' => $decklist->getSideslots()->getContent(),
            ];

            if (json_encode($deck_content) == $new_content) {
                $url = $this->generateUrl('decklist_detail', [
                    'decklist_id' => $decklist->getId(),
                    'decklist_name' => $decklist->getNameCanonical()
                ]);

                $this->get('session')->getFlashBag()->set('warning', "This deck <a href=\"$url\">has already been published</a> before. You are going to create a duplicate.");
            }
        }
        */
        // decklist for the form ; won't be persisted
        //$decklist = $this->get('decklist_factory')->createDecklistFromDeck($deck, $deck->getName(), $deck->getDescriptionMd());

        return $this->render('AppBundle:Fellowship:fellowship_publish_form.html.twig', [
            //'url' => $this->generateUrl('fellowship_publish'),
            //'deck' => $deck,
            //'decklist' => $decklist,
        ]);
    }


    /*
	 * adds a fellowship to a user's list of favorites
	 */
    public function favoriteAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $fellowship_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);

        /* @var $fellowship \AppBundle\Entity\Fellowship */
        $fellowship = $em->getRepository('AppBundle:Fellowship')->find($fellowship_id);
        if (!$fellowship) {
            throw new NotFoundHttpException('Wrong id');
        }

        $author = $fellowship->getUser();

        $dbh = $this->getDoctrine()->getConnection();
        $is_favorite = $dbh->executeQuery("SELECT
				count(*)
				FROM fellowship d
				JOIN fellowship_favorite f ON f.fellowship_id = d.id
				WHERE f.user_id = ?
				AND d.id = ?", [
            $user->getId(),
            $fellowship_id
        ])->fetch(\PDO::FETCH_NUM)[0];

        if ($is_favorite) {
            $fellowship->setNbfavorites($fellowship->getNbFavorites() - 1);
            $user->removeFavorite($fellowship);
            if ($author->getId() != $user->getId()) {
                $author->setReputation($author->getReputation() - 5);
            }
        } else {
            $fellowship->setNbfavorites($fellowship->getNbFavorites() + 1);
            $user->addFavorite($fellowship);
            $fellowship->setDateUpdate(new \DateTime());
            if ($author->getId() != $user->getId()) {
                $author->setReputation($author->getReputation() + 5);
            }
        }
        $this->getDoctrine()->getManager()->flush();

        return new Response($fellowship->getNbFavorites());
    }

    /*
	 * records a user's comment
	 */
    public function commentAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();


        /* @var $user User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $fellowship_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $fellowship = $em->getRepository('AppBundle:Fellowship')->find($fellowship_id);

        $comment_text = trim($request->get('comment'));
        if ($fellowship && !empty($comment_text)) {
            $comment_text = preg_replace('%(?<!\()\b(?:(?:https?|ftp)://)(?:((?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))(?::\d+)?)(?:[^\s]*)?%iu', '[$1]($0)', $comment_text);

            $mentionned_usernames = [];
            $matches = [];
            if (preg_match_all('/`@([\w_]+)`/', $comment_text, $matches, PREG_PATTERN_ORDER)) {
                $mentionned_usernames = array_unique($matches[1]);
            }

            $comment_html = $this->get('texts')->markdown($comment_text);

            $now = new DateTime();

            $comment = new Fellowshipcomment();
            $comment->setText($comment_html);
            $comment->setDateCreation($now);
            $comment->setUser($user);
            $comment->setFellowship($fellowship);
            $comment->setIsHidden(false);

            $em->persist($comment);

            $fellowship->setDateUpdate($now);
            $fellowship->setNbcomments($fellowship->getNbcomments() + 1);

            $em->flush();

            // send emails
            $spool = [];
            if ($fellowship->getUser()->getIsNotifAuthor()) {
                if (!isset($spool[$fellowship->getUser()->getEmail()])) {
                    $spool[$fellowship->getUser()->getEmail()] = 'AppBundle:Emails:newfellowshipcomment_author.html.twig';
                }
            }

            foreach ($fellowship->getComments() as $comment) {
                /* @var $comment Comment */
                $commenter = $comment->getUser();
                if ($commenter && $commenter->getIsNotifCommenter()) {
                    if (!isset($spool[$commenter->getEmail()])) {
                        $spool[$commenter->getEmail()] = 'AppBundle:Emails:newfellowshipcomment_commenter.html.twig';
                    }
                }
            }

            foreach ($mentionned_usernames as $mentionned_username) {
                /* @var $mentionned_user User */
                $mentionned_user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(['username' => $mentionned_username]);
                if ($mentionned_user && $mentionned_user->getIsNotifMention()) {
                    if (!isset($spool[$mentionned_user->getEmail()])) {
                        $spool[$mentionned_user->getEmail()] = 'AppBundle:Emails:newfellowshipcomment_mentionned.html.twig';
                    }
                }
            }
            unset($spool[$user->getEmail()]);

            $email_data = [
                'username' => $user->getUsername(),
                'fellowship_name' => $fellowship->getName(),
                'url' => $this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship->getId(), 'fellowship_name' => $fellowship->getNameCanonical()], UrlGeneratorInterface::ABSOLUTE_URL) . '#' . $comment->getId(),
                'comment' => $comment_html,
                'profile' => $this->generateUrl('user_profile_edit', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ];
            foreach ($spool as $email => $view) {
                $message = \Swift_Message::newInstance()->setSubject("[ringsdb] New comment")->setFrom(["sydtrack@ringsdb.com" => $user->getUsername()])->setTo($email)->setBody($this->renderView($view, $email_data), 'text/html');
                $this->get('mailer')->send($message);
            }
        }

        return $this->redirect($this->generateUrl('fellowship_view', [
            'fellowship_id' => $fellowship_id,
            'fellowship_name' => $fellowship->getNameCanonical()
        ]));
    }

    /*
     * hides a comment, or if $hidden is false, unhide a comment
     */
    public function hidecommentAction($comment_id, $hidden, Request $request) {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $comment = $em->getRepository('AppBundle:Comment')->find($comment_id);
        if (!$comment) {
            throw new BadRequestHttpException('Unable to find comment');
        }

        if ($comment->getFellowship()->getUser()->getId() !== $user->getId()) {
            return new Response(json_encode("You don't have permission to edit this comment."));
        }

        $comment->setIsHidden((boolean)$hidden);
        $em->flush();

        return new Response(json_encode(true));
    }

    /*
	 * records a user's vote
	 */
    public function voteAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $fellowship_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);

        $fellowship = $em->getRepository('AppBundle:Fellowship')->find($fellowship_id);

        if ($fellowship->getUser()->getId() != $user->getId()) {
            $query = $em->getRepository('AppBundle:Fellowship')
                ->createQueryBuilder('d')
                ->innerJoin('d.votes', 'u')
                ->where('d.id = :fellowship_id')
                ->andWhere('u.id = :user_id')
                ->setParameter('fellowship_id', $fellowship_id)
                ->setParameter('user_id', $user->getId())->getQuery();

            $result = $query->getResult();
            if (empty($result)) {
                $user->addVote($fellowship);
                $author = $fellowship->getUser();
                $author->setReputation($author->getReputation() + 1);
                $fellowship->setDateUpdate(new \DateTime());
                $fellowship->setNbVotes($fellowship->getNbVotes() + 1);
                $this->getDoctrine()->getManager()->flush();
            }
        }

        return new Response($fellowship->getNbVotes());
    }

    public function usercommentsAction($page, Request $request) {
        $response = new Response();
        $response->setPrivate();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();

        $limit = 100;
        if ($page < 1) {
            $page = 1;
        }
        $start = ($page - 1) * $limit;

        /* @var $dbh \Doctrine\DBAL\Driver\PDOConnection */
        $dbh = $this->getDoctrine()->getConnection();

        $comments = $dbh->executeQuery("SELECT SQL_CALC_FOUND_ROWS
				c.id,
				c.text,
				c.date_creation,
				d.id fellowship_id,
				d.name fellowship_name,
				d.name_canonical fellowship_name_canonical
				FROM fellowshipcomment c
				JOIN fellowship d ON c.fellowship_id = d.id
				WHERE c.user_id = ?
				ORDER BY date_creation DESC
				LIMIT $start, $limit", [
            $user->getId()
        ])->fetchAll(\PDO::FETCH_ASSOC);

        $maxcount = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(\PDO::FETCH_NUM)[0];

        // pagination : calcul de nbpages // currpage // prevpage // nextpage
        // à partir de $start, $limit, $count, $maxcount, $page

        $currpage = $page;
        $prevpage = max(1, $currpage - 1);
        $nbpages = min(10, ceil($maxcount / $limit));
        $nextpage = min($nbpages, $currpage + 1);

        $route = $request->get('_route');

        $pages = [];
        for ($page = 1; $page <= $nbpages; $page++) {
            $pages[] = [
                "numero" => $page,
                "url" => $this->generateUrl($route, [
                    "page" => $page
                ]),
                "current" => $page == $currpage
            ];
        }

        return $this->render('AppBundle:Default:usercomments.html.twig', [
            'user' => $user,
            'comments' => $comments,
            'url' => $request->getRequestUri(),
            'route' => $route,
            'pages' => $pages,
            'prevurl' => $currpage == 1 ? null : $this->generateUrl($route, [
                "page" => $prevpage
            ]),
            'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, [
                "page" => $nextpage
            ])
        ], $response);
    }

    public function commentsAction($page, Request $request) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        $limit = 100;
        if ($page < 1) {
            $page = 1;
        }
        $start = ($page - 1) * $limit;

        /* @var $dbh \Doctrine\DBAL\Driver\PDOConnection */
        $dbh = $this->getDoctrine()->getConnection();

        $comments = $dbh->executeQuery("SELECT SQL_CALC_FOUND_ROWS
				c.id,
				c.text,
				c.date_creation,
				d.id fellowship_id,
				d.name fellowship_name,
				d.name_canonical fellowship_name_canonical,
				u.id user_id,
				u.username author
				FROM fellowshipcomment c
				JOIN fellowship d on c.fellowship_id = d.id
				JOIN user u on c.user_id = u.id
				ORDER BY date_creation DESC
				LIMIT $start, $limit", [])->fetchAll(\PDO::FETCH_ASSOC);

        $maxcount = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(\PDO::FETCH_NUM)[0];

        // pagination : calcul de nbpages // currpage // prevpage // nextpage
        // à partir de $start, $limit, $count, $maxcount, $page

        $currpage = $page;
        $prevpage = max(1, $currpage - 1);
        $nbpages = min(10, ceil($maxcount / $limit));
        $nextpage = min($nbpages, $currpage + 1);

        $route = $request->get('_route');

        $pages = [];
        for ($page = 1; $page <= $nbpages; $page++) {
            $pages[] = [
                "numero" => $page,
                "url" => $this->generateUrl($route, [
                    "page" => $page
                ]),
                "current" => $page == $currpage
            ];
        }

        return $this->render('AppBundle:Default:allcomments.html.twig', [
            'comments' => $comments,
            'url' => $request->getRequestUri(),
            'route' => $route,
            'pages' => $pages,
            'prevurl' => $currpage == 1 ? null : $this->generateUrl($route, [
                "page" => $prevpage
            ]),
            'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, [
                "page" => $nextpage
            ])
        ], $response);
    }
}
