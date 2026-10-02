<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Deck;
use App\Entity\Decklist;
use App\Entity\User;
use App\Helper\DeckValidationHelper;
use App\Model\DecklistFactory;
use App\Model\DecklistManager;
use App\Repository\CommentRepository;
use App\Repository\CycleRepository;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use App\Repository\UserRepository;
use App\Services\Texts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SocialController extends AbstractController
{
    use CurrentUserTrait;
    /**
     * @var DecklistFactory
     */
    private $decklistFactory;
    /**
     * @var Texts
     */
    private $texts;
    /**
     * @var int
     */
    private $cacheExpiration;
    /**
     * @var CycleRepository
     */
    private $cycleRepository;
    /**
     * @var DeckRepository
     */
    private $deckRepository;
    /**
     * @var DecklistRepository
     */
    private $decklistRepository;

    public function __construct(DecklistFactory $decklistFactory, Texts $texts, int $cacheExpiration, CycleRepository $cycleRepository, DeckRepository $deckRepository, DecklistRepository $decklistRepository)
    {
        $this->decklistFactory = $decklistFactory;
        $this->texts = $texts;
        $this->cacheExpiration = $cacheExpiration;
        $this->cycleRepository = $cycleRepository;
        $this->deckRepository = $deckRepository;
        $this->decklistRepository = $decklistRepository;
    }

    /**
     * Checks to see if a deck can be published in its current saved state
     * If it is, displays the decklist edit form for initial publication of a deck.
     *
     * @return Response
     *
     * @Route("/deck/publish/{deck_id}", name="deck_publish_form", methods={"GET"})
     */
    public function publishFormAction($deck_id, DeckValidationHelper $deckValidationHelper)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in for this operation.');
        }
        $deck = $this->deckRepository->find($deck_id);
        if (!$deck || $deck->getUser()->getId() != $user->getId()) {
            throw $this->createAccessDeniedException("You don't have access to this decklist.");
        }
        /*
        $yesterday = (new \DateTime())->modify('-24 hours');
        if ($user->getDateCreation() > $yesterday) {
            $this->get('session')->getFlashBag()->set('error', "To prevent spam, newly created accounts must wait 24 hours before being allowed to publish a decklist.");

            return $this->redirect($this->generateUrl('deck_view', ['deck_id' => $deck->getId()]));
        }

        $query = $em->createQuery("SELECT COUNT(d) FROM App:Decklist d WHERE d.dateCreation>:DATE AND d.user=:USER");
        $query->setParameter('DATE', $yesterday);
        $query->setParameter('USER', $user);
        $decklistsSinceYesterday = $query->getSingleScalarResult();

        if ($decklistsSinceYesterday > $user->getReputation()) {
            $this->get('session')->getFlashBag()->set('error', "To prevent spam, accounts cannot publish more decklists than their reputation per 24 hours.");

            return $this->redirect($this->generateUrl('deck_view', ['deck_id' => $deck->getId()]));
        }
        */
        /* @var $lastPack \App\Entity\Pack */
        /*
        $lastPack = $deck->getLastPack();
                if (!$lastPack->getDateRelease() || $lastPack->getDateRelease() > new \DateTime()) {
                    $this->get('session')->getFlashBag()->set('error', "You cannot publish this deck yet, because it has unreleased cards.");

                    return $this->redirect($this->generateUrl('deck_view', [ 'deck_id' => $deck->getId() ]));
                }
        */
        $problem = $deckValidationHelper->findProblem($deck, true);
        if ($problem) {
            $this->get('session')->getFlashBag()->set('error', 'This deck cannot be published because it is invalid.');

            return $this->redirect($this->generateUrl('deck_view', ['deck_id' => $deck->getId()]));
        }
        $content = ['main' => $deck->getSlots()->getContent(), 'side' => $deck->getSideslots()->getContent()];
        $new_content = (string) json_encode($content);
        $new_signature = md5($new_content);
        $old_decklists = $this->decklistRepository->findBy(['signature' => $new_signature]);
        /* @var $decklist \App\Entity\Decklist */
        foreach ($old_decklists as $decklist) {
            $deck_content = ['main' => $decklist->getSlots()->getContent(), 'side' => $decklist->getSideslots()->getContent()];
            if (json_encode($deck_content) == $new_content) {
                $url = $this->generateUrl('decklist_detail', ['decklist_id' => $decklist->getId(), 'decklist_name' => $decklist->getNameCanonical()]);
                $this->get('session')->getFlashBag()->set('warning', "This deck <a href=\"{$url}\">has already been published</a> before. You are going to create a duplicate.");
            }
        }
        // decklist for the form ; won't be persisted
        $decklist = $this->decklistFactory->createDecklistFromDeck($deck, $deck->getName(), $deck->getDescriptionMd());

        return $this->render('Decklist/decklist_edit.html.twig', ['url' => $this->generateUrl('decklist_create'), 'deck' => $deck, 'decklist' => $decklist]);
    }

    /**
     * creates a new decklist from a deck (publish action).
     *
     * @return Response
     *
     * @Route("/decklist/create", name="decklist_create", methods={"POST"})
     */
    public function createAction(Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->currentUser();
        /*
        $yesterday = (new \DateTime())->modify('-24 hours');
        if ($user->getDateCreation() > $yesterday) {
            return $this->render('Default/error.html.twig', [
                'pagetitle' => "Spam prevention",
                'error' => "To prevent spam, newly created accounts must wait 24 hours before being allowed to publish a decklist.",
            ]);
        }

        $query = $em->createQuery("SELECT COUNT(d) FROM App:Decklist d WHERE d.dateCreation>:DATE AND d.user=:USER");
        $query->setParameter('DATE', $yesterday);
        $query->setParameter('USER', $user);
        $decklistsSinceYesterday = $query->getSingleScalarResult();

        if ($decklistsSinceYesterday > $user->getReputation()) {
            return $this->render('Default/error.html.twig', [
                'pagetitle' => "Spam prevention",
                'error' => "To prevent spam, accounts cannot publish more decklists than their reputation per 24 hours.",
            ]);
        }
        */
        $deck_id = intval(filter_var($request->request->get('deck_id'), FILTER_SANITIZE_NUMBER_INT));
        /* @var $deck \App\Entity\Deck */
        $deck = $this->deckRepository->find($deck_id);
        if (!$deck) {
            throw new BadRequestHttpException('Invalid deck_id.');
        }
        if ($user->getId() !== $deck->getUser()->getId()) {
            throw $this->createAccessDeniedException('Access denied to this object.');
        }
        $name = filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $descriptionMd = trim($request->request->get('descriptionMd'));
        $precedent_id = trim($request->request->get('precedent'));
        if (!preg_match('/^\\d+$/', $precedent_id)) {
            // route decklist_detail hard-coded
            if (preg_match('/view\\/(\\d+)/', $precedent_id, $matches)) {
                $precedent_id = $matches[1];
            } else {
                $precedent_id = null;
            }
        }
        $precedent = $precedent_id ? $this->decklistRepository->find($precedent_id) : null;
        try {
            /* @var $decklist \App\Entity\Decklist */
            $decklist = $this->decklistFactory->createDecklistFromDeck($deck, $name, $descriptionMd);
        } catch (\Exception $e) {
            return $this->render('Default/error.html.twig', ['pagetitle' => 'Error', 'error' => $e]);
        }
        $decklist->setPrecedent($precedent);
        $em->persist($decklist);
        $em->flush();

        return $this->redirect($this->generateUrl('decklist_detail', ['decklist_id' => $decklist->getId(), 'decklist_name' => $decklist->getNameCanonical()]));
    }

    /**
     * Displays the decklist edit form.
     *
     * @return Response
     *
     * @Route("/decklist/edit/{decklist_id}", name="decklist_edit", requirements={"decklist_id"="\d+"})
     */
    public function editFormAction($decklist_id)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Anonymous access denied');
        }
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw $this->createNotFoundException('Decklist not found');
        }
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && $user->getId() !== $decklist->getUser()->getId()) {
            throw $this->createAccessDeniedException('Access denied');
        }

        return $this->render('Decklist/decklist_edit.html.twig', ['url' => $this->generateUrl('decklist_save', ['decklist_id' => $decklist->getId()]), 'deck' => null, 'decklist' => $decklist]);
    }

    /*
     * save the name and description of a decklist by its publisher
     */
    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route(
     *     "/decklist/save/{decklist_id}",
     *     name="decklist_save",
     *     methods={"POST"},
     *     requirements={"decklist_id"="\d+"}
     * )
     */
    public function saveAction($decklist_id, Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Anonymous access denied');
        }
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw $this->createNotFoundException('Decklist not found');
        }
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && $user->getId() !== $decklist->getUser()->getId()) {
            throw $this->createAccessDeniedException('Access denied');
        }
        $name = trim((string) filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $name = substr($name, 0, 60);
        if (empty($name)) {
            $name = 'Untitled';
        }
        $descriptionMd = trim($request->request->get('descriptionMd'));
        $descriptionHtml = $this->texts->markdown($descriptionMd);
        $precedent_id = trim($request->request->get('precedent'));
        if (!preg_match('/^\\d+$/', $precedent_id)) {
            // route decklist_detail hard-coded
            if (preg_match('/view\\/(\\d+)/', $precedent_id, $matches)) {
                $precedent_id = $matches[1];
            } else {
                $precedent_id = null;
            }
        }
        $precedent = $precedent_id && $precedent_id != $decklist_id ? $this->decklistRepository->find($precedent_id) : null;
        $decklist->setName($name);
        $decklist->setNameCanonical($this->texts->slugify($name).'-'.$decklist->getVersion());
        $decklist->setDescriptionMd($descriptionMd);
        $decklist->setDescriptionHtml($descriptionHtml);
        $decklist->setPrecedent($precedent);
        $decklist->setDateUpdate(new \DateTime());
        $em->flush();

        return $this->redirect($this->generateUrl('decklist_detail', ['decklist_id' => $decklist_id, 'decklist_name' => $decklist->getNameCanonical()]));
    }

    /**
     * deletes a decklist if it has no comment, no vote, no favorite.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route(
     *     "/decklist/delete/{decklist_id}",
     *     name="decklist_delete",
     *     methods={"POST"},
     *     requirements={"decklist_id"="\d+"}
     * )
     */
    public function deleteAction($decklist_id)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist || $decklist->getUser()->getId() != $user->getId()) {
            throw new AccessDeniedHttpException("You don't have access to this decklist.");
        }
        if ($decklist->getNbVotes() || $decklist->getNbfavorites() || $decklist->getNbcomments()) {
            throw new AccessDeniedHttpException('Cannot delete this decklist.');
        }
        $precedent = $decklist->getPrecedent();
        $children_decks = $decklist->getChildren();
        /* @var $children_deck Deck */
        foreach ($children_decks as $children_deck) {
            $children_deck->setParent($precedent);
        }
        $successor_decklists = $decklist->getSuccessors();
        /* @var $successor_decklist Decklist */
        foreach ($successor_decklists as $successor_decklist) {
            $successor_decklist->setPrecedent($precedent);
        }
        $em->remove($decklist);
        $em->flush();

        return $this->redirect($this->generateUrl('decklists_list', ['type' => 'mine']));
    }

    /**
     * @return string
     */
    private function searchForm(Request $request)
    {
        $dbh = $this->getDoctrine()->getConnection();
        $cards_code = $request->query->get('cards');
        $cards_to_exclude = $request->query->get('cards_to_exclude');
        $sphere_code = filter_var($request->query->get('sphere'), FILTER_SANITIZE_STRING);
        $author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);
        $decklist_name = filter_var($request->query->get('name'), FILTER_SANITIZE_STRING);
        $starting_threat = intval(filter_var($request->query->get('threat'), FILTER_SANITIZE_NUMBER_INT));
        $starting_threat_o = $request->query->get('threato');
        $author_reputation = intval(filter_var($request->query->get('reputation'), FILTER_SANITIZE_NUMBER_INT));
        $author_reputation_o = $request->query->get('reputationo');
        $numcores = $request->query->get('numcores');
        $require_description = $request->query->get('require_description');
        $sort = $request->query->get('sort');
        $packs = $request->query->get('packs');
        if (!is_array($packs)) {
            $packs = $dbh->executeQuery('SELECT id FROM pack')->fetchAll(\PDO::FETCH_COLUMN);
        }
        $categories = [];
        $on = 0;
        $off = 0;
        $categories[] = ['label' => 'Core / Deluxe', 'packs' => []];
        $list_cycles = $this->cycleRepository->findBy([], ['position' => 'ASC']);
        foreach ($list_cycles as $cycle) {
            /* @var $cycle \App\Entity\Cycle */
            $size = count($cycle->getPacks());
            $first_pack = $cycle->getPacks()->first();
            if (0 == $cycle->getPosition() || false === $first_pack) {
                continue;
            }
            if (1 === $size && $first_pack->getName() == $cycle->getName()) {
                $checked = count($packs) ? in_array($first_pack->getId(), $packs) : true;
                if ($checked) {
                    ++$on;
                } else {
                    ++$off;
                }
                $categories[0]['packs'][] = ['id' => $first_pack->getId(), 'label' => $first_pack->getName(), 'checked' => $checked, 'future' => null === $first_pack->getDateRelease()];
            } else {
                $category = ['label' => $cycle->getName(), 'packs' => []];
                foreach ($cycle->getPacks() as $pack) {
                    $checked = count($packs) ? in_array($pack->getId(), $packs) : true;
                    if ($checked) {
                        ++$on;
                    } else {
                        ++$off;
                    }
                    $category['packs'][] = ['id' => $pack->getId(), 'label' => $pack->getName(), 'checked' => $checked, 'future' => null === $pack->getDateRelease()];
                }
                $categories[] = $category;
            }
        }
        $params = ['allowed' => $categories, 'on' => $on, 'off' => $off, 'author' => $author_name, 'name' => $decklist_name, 'threat' => $starting_threat, 'threato' => $starting_threat_o, 'reputation' => $author_reputation, 'reputationo' => $author_reputation_o, 'numcores' => $numcores, 'require_description' => $require_description];
        $params['sort_'.$sort] = ' selected="selected"';
        $params['spheres'] = $dbh->executeQuery("SELECT\n                s.name,\n                s.code\n                FROM sphere s\n                ORDER BY s.name ASC")->fetchAll();
        $params['sphere_selected'] = $sphere_code;
        if (!empty($cards_code) && is_array($cards_code)) {
            $cards = $dbh->executeQuery("SELECT\n    \t\t\t\tc.name,\n    \t\t\t\tc.code,\n                    s.code AS sphere_code,\n                    t.name AS type_name\n    \t\t\t\tFROM card c\n                    INNER JOIN sphere s ON s.id = c.sphere_id\n                    INNER JOIN type t ON t.id = c.type_id\n                    INNER JOIN card_printing cpr ON cpr.id = (SELECT cp2.id FROM card_printing cp2 JOIN pack pp ON pp.id = cp2.pack_id WHERE cp2.card_id = c.id ORDER BY (pp.date_release IS NULL), pp.date_release, cp2.position, cp2.id LIMIT 1)\n                    INNER JOIN pack p ON p.id = cpr.pack_id\n                    WHERE c.code IN (?)\n    \t\t\t\tORDER BY c.code DESC", [$cards_code], [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY])->fetchAll();
            $params['cards'] = '';
            foreach ($cards as $card) {
                $params['cards'] .= $this->renderView('Search/card.html.twig', $card);
            }
        }
        if (!empty($cards_to_exclude) && is_array($cards_to_exclude)) {
            $cards_to_exclude = $dbh->executeQuery("SELECT\n    \t\t\t\tk.name,\n    \t\t\t\tk.code,\n                    s.code AS sphere_code,\n                    t.name AS type_name\n    \t\t\t\tFROM card k\n                    INNER JOIN sphere s ON s.id = k.sphere_id\n                    INNER JOIN type t ON t.id = k.type_id\n                    INNER JOIN card_printing kpr ON kpr.id = (SELECT cp2.id FROM card_printing cp2 JOIN pack pp ON pp.id = cp2.pack_id WHERE cp2.card_id = k.id ORDER BY (pp.date_release IS NULL), pp.date_release, cp2.position, cp2.id LIMIT 1)\n                    INNER JOIN pack p ON p.id = kpr.pack_id\n                    WHERE k.code IN (?)\n    \t\t\t\tORDER BY k.code DESC", [$cards_to_exclude], [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY])->fetchAll();
            $params['cards_to_exclude'] = '';
            foreach ($cards_to_exclude as $card_to_exclude) {
                $params['cards_to_exclude'] .= $this->renderView('Search/card-to-exclude.html.twig', $card_to_exclude);
            }
        }

        return $this->renderView('Search/form.html.twig', $params);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/d/{username}", name="decklist_byauthor", methods={"GET"})
     */
    public function byauthorAction($username)
    {
        return $this->redirect($this->generateUrl('decklists_list', ['type' => 'find', 'author' => $username]));
    }

    /*
     * displays the content of a decklist along with comments, siblings, similar, etc.
     */
    /**
     * @return Response
     *
     * @Route(
     *     "/decklist/view/{decklist_id}/{decklist_name}",
     *     name="decklist_detail",
     *     methods={"GET"},
     *     requirements={"decklist_id"="\d+"},
     *     defaults={"decklist_name"=null}
     * )
     */
    public function viewAction($decklist_id)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw $this->createNotFoundException('Decklist not found.');
        }
        $duplicate = $this->decklistRepository->findOneBy(['signature' => $decklist->getSignature()]);
        if (!$duplicate || $duplicate->getDateCreation() >= $decklist->getDateCreation() || $duplicate->getId() === $decklist->getId()) {
            $duplicate = null;
        }
        $commenters = array_map(function ($comment) {
            /* @var $comment \App\Entity\Comment */
            return $comment->getUser()->getUsername();
        }, $decklist->getComments()->getValues());
        $commenters[] = $decklist->getUser()->getUsername();
        $versions = $this->decklistRepository->findBy(['parent' => $decklist->getParent()], ['version' => 'DESC', 'id' => 'DESC']);

        return $this->render('Decklist/decklist.html.twig', ['pagetitle' => $decklist->getName(), 'decklist' => $decklist, 'duplicate' => $duplicate, 'commenters' => $commenters, 'versions' => $versions], $response);
    }

    /*
     * adds a decklist to a user's list of favorites
     */
    /**
     * @return Response
     *
     * @Route("/user/favorite", name="decklist_favorite", methods={"POST"})
     */
    public function favoriteAction(Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }
        $decklist_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $decklist \App\Entity\Decklist */
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw new NotFoundHttpException('Wrong id');
        }
        $author = $decklist->getUser();
        $dbh = $this->getDoctrine()->getConnection();
        $is_favorite = $dbh->executeQuery("SELECT\n\t\t\t\tcount(*)\n\t\t\t\tFROM decklist d\n\t\t\t\tJOIN favorite f ON f.decklist_id = d.id\n\t\t\t\tWHERE f.user_id = ?\n\t\t\t\tAND d.id = ?", [$user->getId(), $decklist_id])->fetch(\PDO::FETCH_NUM)[0];
        if ($is_favorite) {
            $decklist->setNbfavorites($decklist->getNbFavorites() - 1);
            $user->removeFavorite($decklist);
            if ($author->getId() != $user->getId()) {
                $author->setReputation($author->getReputation() - 5);
            }
        } else {
            $decklist->setNbfavorites($decklist->getNbFavorites() + 1);
            $user->addFavorite($decklist);
            $decklist->setDateUpdate(new \DateTime());
            if ($author->getId() != $user->getId()) {
                $author->setReputation($author->getReputation() + 5);
            }
        }
        $this->getDoctrine()->getManager()->flush();

        return new Response((string) $decklist->getNbFavorites());
    }

    /*
     * records a user's comment
     */
    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/user/comment", name="decklist_comment", methods={"POST"})
     */
    public function commentAction(Request $request, MailerInterface $mailer, UserRepository $userRepository)
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }
        $decklist_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist instanceof Decklist) {
            throw new BadRequestHttpException('Wrong decklist id');
        }
        $comment_text = trim($request->get('comment'));
        if (!empty($comment_text)) {
            $comment_text = (string) preg_replace('%(?<!\\()\\b(?:(?:https?|ftp)://)(?:((?:(?:[a-z\\d\\x{00a1}-\\x{ffff}]+-?)*[a-z\\d\\x{00a1}-\\x{ffff}]+)(?:\\.(?:[a-z\\d\\x{00a1}-\\x{ffff}]+-?)*[a-z\\d\\x{00a1}-\\x{ffff}]+)*(?:\\.[a-z\\x{00a1}-\\x{ffff}]{2,6}))(?::\\d+)?)(?:[^\\s]*)?%iu', '[$1]($0)', $comment_text);
            $mentionned_usernames = [];
            $matches = [];
            if (preg_match_all('/`@([\\w_]+)`/', $comment_text, $matches, PREG_PATTERN_ORDER)) {
                $mentionned_usernames = array_unique($matches[1]);
            }
            $comment_html = $this->texts->markdown($comment_text);
            $now = new \DateTime();
            $comment = new Comment();
            $comment->setText($comment_html);
            $comment->setDateCreation($now);
            $comment->setUser($user);
            $comment->setDecklist($decklist);
            $comment->setIsHidden(false);
            $this->getDoctrine()->getManager()->persist($comment);
            $decklist->setDateUpdate($now);
            $decklist->setDateLastComment($comment->getDateCreation());
            $decklist->setNbcomments($decklist->getNbcomments() + 1);
            $this->getDoctrine()->getManager()->flush();
            // send emails
            $spool = [];
            if ($decklist->getUser()->getIsNotifAuthor()) {
                $spool[$decklist->getUser()->getEmail()] = 'Emails/newcomment_author.html.twig';
            }
            foreach ($decklist->getComments() as $comment) {
                /* @var $comment Comment */
                $commenter = $comment->getUser();
                if ($commenter->getIsNotifCommenter()) {
                    if (!isset($spool[$commenter->getEmail()])) {
                        $spool[$commenter->getEmail()] = 'Emails/newcomment_commenter.html.twig';
                    }
                }
            }
            foreach ($mentionned_usernames as $mentionned_username) {
                /* @var $mentionned_user User */
                $mentionned_user = $userRepository->findOneBy(['username' => $mentionned_username]);
                if ($mentionned_user && $mentionned_user->getIsNotifMention()) {
                    if (!isset($spool[$mentionned_user->getEmail()])) {
                        $spool[$mentionned_user->getEmail()] = 'Emails/newcomment_mentionned.html.twig';
                    }
                }
            }
            unset($spool[$user->getEmail()]);
            $email_data = ['username' => $user->getUsername(), 'decklist_name' => $decklist->getName(), 'url' => $this->generateUrl('decklist_detail', ['decklist_id' => $decklist->getId(), 'decklist_name' => $decklist->getNameCanonical()], UrlGeneratorInterface::ABSOLUTE_URL).'#'.$comment->getId(), 'comment' => $comment_html, 'profile' => $this->generateUrl('user_profile_edit', [], UrlGeneratorInterface::ABSOLUTE_URL)];
            foreach ($spool as $email => $view) {
                $message = (new Email())->subject('[ringsdb] New comment')->from(new Address('seastan@ringsdb.com', 'Seastan'))->to(new Address($email, $user->getUsername()))->html($this->renderView($view, $email_data));
                $mailer->send($message);
            }
        }

        return $this->redirect($this->generateUrl('decklist_detail', ['decklist_id' => $decklist_id, 'decklist_name' => $decklist->getNameCanonical()]));
    }

    /*
     * hides a comment, or if $hidden is false, unhide a comment
     */
    /**
     * @return Response
     *
     * @Route("/user/hidecomment/{comment_id}/{hidden}", name="decklist_comment_hide", methods={"POST"})
     */
    public function hidecommentAction($comment_id, $hidden, CommentRepository $commentRepository)
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        $comment = $commentRepository->find($comment_id);
        if (!$comment) {
            throw new BadRequestHttpException('Unable to find comment');
        }
        if ($comment->getDecklist()->getUser()->getId() !== $user->getId()) {
            return new Response(json_encode("You don't have permission to edit this comment."));
        }
        $comment->setIsHidden((bool) $hidden);
        $em->flush();

        return new Response(json_encode(true));
    }

    /*
     * records a user's vote
     */
    /**
     * @return Response
     *
     * @Route("/user/like", name="decklist_like", methods={"POST"})
     */
    public function voteAction(Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }
        $decklist_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $decklist \App\Entity\Decklist */
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist instanceof Decklist) {
            throw new BadRequestHttpException('Unable to find deck');
        }
        if ($decklist->getUser()->getId() != $user->getId()) {
            $query = $this->decklistRepository->createQueryBuilder('d')->innerJoin('d.votes', 'u')->where('d.id = :decklist_id')->andWhere('u.id = :user_id')->setParameter('decklist_id', $decklist_id)->setParameter('user_id', $user->getId())->getQuery();
            $result = $query->getResult();
            if (empty($result)) {
                $author = $decklist->getUser();
                $author->setReputation($author->getReputation() + 1);
                $user->addVote($decklist);
                $decklist->setDateUpdate(new \DateTime());
                $decklist->setNbVotes($decklist->getNbVotes() + 1);
                $em->flush();
            }
        }

        return new Response((string) $decklist->getNbVotes());
    }

    /*
     * returns a text file with the content of a decklist
     */
    /**
     * @return Response
     *
     * @Route(
     *     "/decklist/export/text/{decklist_id}",
     *     name="decklist_export_text",
     *     methods={"GET"},
     *     requirements={"decklist_id"="\d+"}
     * )
     */
    public function textexportAction($decklist_id)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $decklist \App\Entity\Decklist */
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw new NotFoundHttpException('Unable to find decklist.');
        }
        $content = $this->renderView('Export/plain.txt.twig', ['deck' => $decklist->getTextExport()]);
        $content = str_replace("\n", "\r\n", $content);
        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $decklist->getNameCanonical().'.txt'));
        $response->setContent($content);

        return $response;
    }

    /*
     * returns a octgn file with the content of a decklist
     */
    /**
     * @return Response
     *
     * @Route(
     *     "/decklist/export/octgn/{decklist_id}",
     *     name="decklist_export_octgn",
     *     methods={"GET"},
     *     requirements={"decklist_id"="\d+"}
     * )
     */
    public function octgnexportAction($decklist_id)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $decklist \App\Entity\Decklist */
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw new NotFoundHttpException('Unable to find decklist.');
        }
        $content = $this->renderView('Export/octgn.xml.twig', ['deck' => $decklist->getTextExport()]);
        $response = new Response();
        $response->headers->set('Content-Type', 'application/octgn');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $decklist->getNameCanonical().'.o8d'));
        $response->setContent($content);

        return $response;
    }

    /**
     * @return Response
     *
     * @Route("/decklists/search", name="decklists_searchform", methods={"GET"})
     */
    public function searchAction(Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $dbh = $this->getDoctrine()->getConnection();
        $spheres = $dbh->executeQuery('SELECT s.name, s.code FROM sphere s ORDER BY s.name ASC')->fetchAll();
        $owned_packs = '';
        if ($this->getUser()) {
            $owned_packs = $this->getUser()->getOwnedPacks();
        }
        if ($owned_packs) {
            // owned_packs is a per-pack count map ("id" / "id:count", legacy "id-2");
            // keep the ids whose count is > 0.
            $packs = [];
            foreach (explode(',', $owned_packs) as $token) {
                if (preg_match('/^(\\d+)(?:[:-](\\d+))?$/', trim($token), $m)) {
                    if (!isset($m[2]) || (int) $m[2] > 0) {
                        $packs[] = (int) $m[1];
                    }
                }
            }
        } else {
            $packs = $dbh->executeQuery('SELECT id FROM pack WHERE date_release IS NOT NULL')->fetchAll(\PDO::FETCH_COLUMN);
        }
        $categories = [];
        $on = 0;
        $off = 0;
        $categories[] = ['label' => 'Core / Deluxe', 'packs' => []];
        $list_cycles = $this->cycleRepository->findBy([], ['position' => 'ASC']);
        foreach ($list_cycles as $cycle) {
            /* @var $cycle \App\Entity\Cycle */
            $size = count($cycle->getPacks());
            $first_pack = $cycle->getPacks()->first();
            if (0 == $cycle->getPosition() || false === $first_pack) {
                continue;
            }
            if (1 === $size && $first_pack->getName() == $cycle->getName()) {
                $checked = count($packs) ? in_array($first_pack->getId(), $packs) : true;
                if ($checked) {
                    ++$on;
                } else {
                    ++$off;
                }
                $categories[0]['packs'][] = ['id' => $first_pack->getId(), 'label' => $first_pack->getName(), 'checked' => $checked, 'future' => null === $first_pack->getDateRelease()];
            } else {
                $category = ['label' => $cycle->getName(), 'packs' => []];
                foreach ($cycle->getPacks() as $pack) {
                    $checked = count($packs) ? in_array($pack->getId(), $packs) : true;
                    if ($checked) {
                        ++$on;
                    } else {
                        ++$off;
                    }
                    $category['packs'][] = ['id' => $pack->getId(), 'label' => $pack->getName(), 'checked' => $checked, 'future' => null === $pack->getDateRelease()];
                }
                $categories[] = $category;
            }
        }
        $searchForm = $this->renderView('Search/form.html.twig', ['spheres' => $spheres, 'allowed' => $categories, 'on' => $on, 'off' => $off, 'author' => '', 'name' => '', 'threat' => '', 'threato' => '', 'reputation' => '', 'reputationo' => '>', 'numcores' => '3', 'require_description' => 0]);

        return $this->render('Decklist/decklists.html.twig', ['pagetitle' => 'Decklist Search', 'decklists' => null, 'url' => $request->getRequestUri(), 'header' => $searchForm, 'type' => 'find', 'pages' => null, 'prevurl' => null, 'nexturl' => null], $response);
    }

    /**
     * @return Response
     *
     * @Route("/patrons", name="patrons", methods={"GET"})
     */
    public function patronsAction()
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $dbh = $this->getDoctrine()->getConnection();
        $users = $dbh->executeQuery('SELECT * FROM user WHERE donation > 0 ORDER BY donation DESC, username', [])->fetchAll(\PDO::FETCH_ASSOC);

        return $this->render('Default/patrons.html.twig', ['pagetitle' => 'The Gracious Patrons', 'patrons' => $users], $response);
    }

    /**
     * displays the lists of decklists.
     *
     * @param int $page
     *
     * @return Response
     *
     * @Route(
     *     "/decklists/{type}/{page}",
     *     name="decklists_list",
     *     methods={"GET"},
     *     requirements={"page"="\d+"},
     *     defaults={"type"="popular", "page"=1}
     * )
     */
    public function listAction($type, $page = 1, Request $request, DecklistManager $decklistManager)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        /**
         * @var DecklistManager $decklist_manager
         */
        $decklist_manager = $decklistManager;
        $decklist_manager->setLimit(30);
        $decklist_manager->setPage($page);
        $header = '';
        switch ($type) {
            case 'find':
                $pagetitle = 'Decklist search results';
                $header = $this->searchForm($request);
                $decklist_manager->setUser($this->getUser());
                $paginator = $decklist_manager->findDecklistsWithComplexSearch();
                break;
            case 'favorites':
                $response->setPrivate();
                $user = $this->getUser();
                if ($user) {
                    $paginator = $decklist_manager->findDecklistsByFavorite($user);
                } else {
                    $paginator = $decklist_manager->getEmptyList();
                }
                $pagetitle = 'Favorite Decklists';
                break;
            case 'mine':
                $response->setPrivate();
                $user = $this->getUser();
                if ($user) {
                    $paginator = $decklist_manager->findDecklistsByAuthor($user);
                } else {
                    $paginator = $decklist_manager->getEmptyList();
                }
                $pagetitle = 'My Decklists';
                break;
            case 'recent':
                $paginator = $decklist_manager->findDecklistsByAge();
                $pagetitle = 'Recent Decklists';
                break;
            case 'halloffame':
                $paginator = $decklist_manager->findDecklistsInHallOfFame();
                $pagetitle = 'Hall of Fame';
                break;
            case 'hottopics':
                $paginator = $decklist_manager->findDecklistsInHotTopic();
                $pagetitle = 'Hot Topics';
                break;
            case 'popular':
            default:
                $paginator = $decklist_manager->findDecklistsByPopularity();
                $pagetitle = 'Popular Decklists';
                break;
        }

        return $this->render('Decklist/decklists.html.twig', ['pagetitle' => $pagetitle, 'pagedescription' => 'Browse the collection of thousands of premade decks.', 'decklists' => $paginator, 'url' => $request->getRequestUri(), 'header' => $header, 'type' => $type, 'pages' => $decklist_manager->getClosePages(), 'prevurl' => $decklist_manager->getPreviousUrl(), 'nexturl' => $decklist_manager->getNextUrl()], $response);
    }
}
