<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Fellowship;
use App\Entity\FellowshipComment;
use App\Entity\FellowshipDeck;
use App\Entity\FellowshipDecklist;
use App\Helper\FellowshipValidationHelper;
use App\Model\DecklistFactory;
use App\Model\FellowshipManager;
use App\Repository\CycleRepository;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use App\Repository\FellowshipCommentRepository;
use App\Repository\FellowshipRepository;
use App\Repository\UserRepository;
use App\Services\Decks;
use App\Services\Texts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FellowshipController extends AbstractController
{
    use CurrentUserTrait;
    /**
     * @var FellowshipValidationHelper
     */
    private $fellowshipValidationHelper;
    /**
     * @var Texts
     */
    private $texts;
    /**
     * @var int
     */
    private $cacheExpiration;
    /**
     * @var string
     */
    private $cacheDir;
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
    /**
     * @var FellowshipRepository
     */
    private $fellowshipRepository;

    public function __construct(FellowshipValidationHelper $fellowshipValidationHelper, Texts $texts, int $cacheExpiration, string $cacheDir, CycleRepository $cycleRepository, DeckRepository $deckRepository, DecklistRepository $decklistRepository, FellowshipRepository $fellowshipRepository)
    {
        $this->fellowshipValidationHelper = $fellowshipValidationHelper;
        $this->texts = $texts;
        $this->cacheExpiration = $cacheExpiration;
        $this->cacheDir = $cacheDir;
        $this->cycleRepository = $cycleRepository;
        $this->deckRepository = $deckRepository;
        $this->decklistRepository = $decklistRepository;
        $this->fellowshipRepository = $fellowshipRepository;
    }

    /**
     * @return Response
     *
     * @Route("/myfellowships", name="myfellowships_list", methods={"GET"})
     */
    public function mylistAction()
    {
        /* @var $user \App\Entity\User */
        $user = $this->currentUser();
        /* @var $fellowships \App\Entity\Fellowship[] */
        $fellowships = $user->getFellowships();
        if (count($fellowships)) {
            return $this->render('Fellowship/my-fellowships.html.twig', ['pagetitle' => 'My Fellowships', 'pagedescription' => 'Create fellowships, a link between decks that work well together or are meant to be played together.', 'fellowships' => $fellowships]);
        }

        return $this->render('Fellowship/no-fellowships.html.twig', ['pagetitle' => 'My Fellowships', 'pagedescription' => 'Create fellowships, a link between decks that work well together or are meant to be played together.']);
    }

    /**
     * @return Response
     *
     * @Route(
     *     "/fellowship/new/{deck1_id}/{deck2_id}/{deck3_id}/{deck4_id}",
     *     name="fellowship_new",
     *     methods={"GET"},
     *     requirements={"deck1_id"="\d+", "deck2_id"="\d+", "deck3_id"="\d+", "deck4_id"="\d+"},
     *     defaults={"deck1_id"=null, "deck2_id"=null, "deck3_id"=null, "deck4_id"=null}
     * )
     */
    public function newAction($deck1_id, $deck2_id, $deck3_id, $deck4_id)
    {
        $response = new Response();
        $decks = [];
        $deck_ids = func_get_args();
        for ($i = 0; $i < 4; ++$i) {
            $decks[$i] = null;
            if ($deck_ids[$i]) {
                /* @var $decks \App\Entity\Deck[] */
                $decks[$i] = $this->deckRepository->find($deck_ids[$i]);
                if ($decks[$i]) {
                    /* @var $user \App\Entity\User */
                    $user = $decks[$i]->getUser();
                    if (!$user->getIsShareDecks() && $user->getId() != $this->currentUser()->getId()) {
                        $decks[$i] = null;
                    }
                }
            }
        }

        return $this->render('Fellowship/edit.html.twig', ['pagetitle' => 'Create a Fellowship', 'deck1' => $decks[0], 'deck2' => $decks[1], 'deck3' => $decks[2], 'deck4' => $decks[3], 'is_public' => false], $response);
    }

    /**
     * @return Response
     *
     * @Route(
     *     "/fellowship/edit/{fellowship_id}",
     *     name="fellowship_edit",
     *     methods={"GET"},
     *     requirements={"fellowship_id"="\d+"}
     * )
     */
    public function editAction($fellowship_id)
    {
        $response = new Response();
        /* @var $user \App\Entity\User */
        $user = $this->currentUser();
        /* @var $fellowship \App\Entity\Fellowship */
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship) {
            throw new NotFoundHttpException('This fellowship does not exists.');
        }
        if ($user->getId() !== $fellowship->getUser()->getId()) {
            throw new AccessDeniedHttpException('Access denied to this object.');
        }
        $data = ['pagetitle' => 'Edit Fellowship', 'deck1' => null, 'deck2' => null, 'deck3' => null, 'deck4' => null, 'fellowship' => $fellowship, 'is_public' => $fellowship->getIsPublic()];
        /* @var $fellowship_decks \App\Entity\FellowshipDeck[] */
        $fellowship_decks = $fellowship->getDecks();
        foreach ($fellowship_decks as $fellowship_deck) {
            $data['deck'.$fellowship_deck->getDeckNumber()] = $fellowship_deck->getDeck();
        }
        /* @var $fellowship_decks \App\Entity\FellowshipDecklist[] */
        $fellowship_decklists = $fellowship->getDecklists();
        foreach ($fellowship_decklists as $fellowship_decklist) {
            $data['deck'.$fellowship_decklist->getDeckNumber()] = $fellowship_decklist->getDecklist();
        }

        return $this->render('Fellowship/edit.html.twig', $data, $response);
    }

    /**
     * @return Response
     *
     * @Route(
     *     "/fellowship/view/{fellowship_id}/{fellowship_name}",
     *     name="fellowship_view",
     *     methods={"GET"},
     *     requirements={"fellowship_id"="\d+"},
     *     defaults={"fellowship_name"=null}
     * )
     */
    public function viewAction($fellowship_id)
    {
        /* @var $fellowship \App\Entity\Fellowship */
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship) {
            throw new NotFoundHttpException('This fellowship does not exists.');
        }
        $is_owner = $this->getUser() && $this->getUser()->getId() == $fellowship->getUser()->getId();
        $is_public = $fellowship->getIsPublic();
        if (!$fellowship->getUser()->getIsShareDecks() && !$is_owner && !$is_public) {
            throw new AccessDeniedHttpException('You are not allowed to view this fellowship. To get access, you can ask it\'s owner to enable "Share my decks" on their account.');
        }
        if ($is_public) {
            $commenters = array_map(function ($comment) {
                /* @var $comment \App\Entity\FellowshipComment */
                return $comment->getUser()->getUsername();
            }, $fellowship->getComments()->getValues());
            $commenters[] = $fellowship->getUser()->getUsername();
        } else {
            $commenters = [];
        }
        $data = ['pagetitle' => 'Fellowship', 'deck1' => null, 'deck2' => null, 'deck3' => null, 'deck4' => null, 'fellowship' => $fellowship, 'is_owner' => $is_owner, 'is_public' => $is_public, 'commenters' => $commenters];
        /* @var $fellowship_decks \App\Entity\FellowshipDeck[] */
        $fellowship_decks = $fellowship->getDecks();
        foreach ($fellowship_decks as $fellowship_deck) {
            $data['deck'.$fellowship_deck->getDeckNumber()] = $fellowship_deck->getDeck();
        }
        /* @var $fellowship_decks \App\Entity\FellowshipDecklist[] */
        $fellowship_decklists = $fellowship->getDecklists();
        foreach ($fellowship_decklists as $fellowship_decklist) {
            $data['deck'.$fellowship_decklist->getDeckNumber()] = $fellowship_decklist->getDecklist();
        }

        return $this->render('Fellowship/view.html.twig', $data);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/fellowship/save", name="fellowship_save", methods={"POST"})
     */
    public function saveAction(Request $request, Decks $decks)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->currentUser();
        $fellowship_id = intval(filter_var($request->request->get('fellowship_id'), FILTER_SANITIZE_NUMBER_INT));
        if ($fellowship_id) {
            /* @var $fellowship \App\Entity\Fellowship */
            $fellowship = $this->fellowshipRepository->find($fellowship_id);
            if (!$fellowship) {
                throw new NotFoundHttpException('This fellowship does not exists.');
            }
            if ($user->getId() !== $fellowship->getUser()->getId()) {
                throw new AccessDeniedHttpException('Access denied to this object.');
            }
        } else {
            $fellowship = new Fellowship();
            $fellowship->setIsPublic(false);
            $fellowship->setNbVotes(0);
            $fellowship->setNbComments(0);
            $fellowship->setNbFavorites(0);
            $fellowship->setNbDecks(0);
        }
        $name = trim((string) filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $name = substr($name, 0, 60);
        if (empty($name)) {
            $name = 'Untitled Fellowship';
        }
        $auto_publish = boolval(filter_var($request->request->get('auto_publish'), FILTER_SANITIZE_NUMBER_INT));
        $descriptionMd = trim($request->request->get('descriptionMd'));
        $descriptionHtml = $this->texts->markdown($descriptionMd);
        $fellowship->setUser($user);
        $fellowship->setName($name);
        $fellowship->setNameCanonical($this->texts->slugify($name));
        $fellowship->setDescriptionMd($descriptionMd);
        $fellowship->setDescriptionHtml($descriptionHtml);
        $is_public = $fellowship->getIsPublic();
        if (!$is_public) {
            // Allow deck changing
            foreach ($fellowship->getDecks() as $deck) {
                $fellowship->removeDeck($deck);
                $em->remove($deck);
            }
            foreach ($fellowship->getDecklists() as $deck) {
                $fellowship->removeDecklist($deck);
                $em->remove($deck);
            }
            $nb_decks = 0;
            $skip = 0;
            for ($i = 1; $i <= 4; ++$i) {
                $deck_id = intval(filter_var($request->request->get('deck'.$i.'_id'), FILTER_SANITIZE_NUMBER_INT));
                $is_decklist = 'true' == filter_var($request->get('deck'.$i.'_is_decklist'), FILTER_SANITIZE_STRING);
                if ($deck_id) {
                    if (!$is_decklist) {
                        /* @var $deck \App\Entity\Deck */
                        $deck = $this->deckRepository->find($deck_id);
                        if (!$deck) {
                            throw new NotFoundHttpException('One of the selected decks does not exists.');
                        }
                        $deck_user = $deck->getUser();
                        $is_owner = $user->getId() == $deck_user->getId();
                        if (!$is_owner && !$deck_user->getIsShareDecks()) {
                            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
                        }
                        if (!$is_owner) {
                            $deck = $decks->cloneDeck($deck, $user);
                        }
                        $fellowship_deck = new FellowshipDeck();
                        $fellowship_deck->setDeck($deck);
                        $fellowship_deck->setDeckNumber($i - $skip);
                        $fellowship_deck->setFellowship($fellowship);
                        $fellowship->addDeck($fellowship_deck);
                    } else {
                        /* @var $decklist \App\Entity\Decklist */
                        $decklist = $this->decklistRepository->find($deck_id);
                        if (!$decklist) {
                            throw new NotFoundHttpException('One of the selected decks does not exists.');
                        }
                        $fellowship_decklist = new FellowshipDecklist();
                        $fellowship_decklist->setDecklist($decklist);
                        $fellowship_decklist->setDeckNumber($i - $skip);
                        $fellowship_decklist->setFellowship($fellowship);
                        $fellowship->addDecklist($fellowship_decklist);
                    }
                    ++$nb_decks;
                } else {
                    ++$skip;
                }
            }
            if (0 == $nb_decks) {
                throw new UnprocessableEntityHttpException("You can't save an empty fellowship.");
            }
            $fellowship->setNbDecks($nb_decks);
        }
        // BUG, pinned by the tests (see MIGRATION.md, "Fellowships"): a collection is never empty()
        if ($auto_publish && empty($fellowship->getDecks())) {
            $fellowship->setIsPublic(true);
            $fellowship->setDatePublish(new \DateTime());
        }
        $em->persist($fellowship);
        $em->flush();

        return $this->redirect($this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship->getId()]));
    }

    /**
     * @return Response
     *
     * @Route(
     *     "/fellowship/publish/{fellowship_id}",
     *     name="fellowship_publish_form",
     *     methods={"GET"},
     *     requirements={"fellowship_id"="\d+"}
     * )
     */
    public function publishFormAction($fellowship_id)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }
        /* @var $fellowship \App\Entity\Fellowship */
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship || $fellowship->getUser()->getId() != $user->getId()) {
            throw new AccessDeniedHttpException("You don't have access to this fellowship.");
        }
        $problem = $this->fellowshipValidationHelper->findProblem($fellowship);
        if ($problem) {
            $this->get('session')->getFlashBag()->set('error', 'This fellowship cannot be published because it is invalid.');

            return $this->redirect($this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship->getId()]));
        }
        if ($fellowship->getIsPublic()) {
            $this->get('session')->getFlashBag()->set('error', 'This fellowship is already published.');

            return $this->redirect($this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship->getId()]));
        }
        /** @var array<string, mixed> $data */
        $data = ['pagetitle' => 'Publish Fellowship', 'deck1' => null, 'deck2' => null, 'deck3' => null, 'deck4' => null, 'deck1_duplicates' => [], 'deck2_duplicates' => [], 'deck3_duplicates' => [], 'deck4_duplicates' => [], 'deck1_match' => null, 'deck2_match' => null, 'deck3_match' => null, 'deck4_match' => null, 'deck1_signature' => null, 'deck2_signature' => null, 'deck3_signature' => null, 'deck4_signature' => null, 'fellowship' => $fellowship];
        /* @var $fellowship_decks \App\Entity\FellowshipDeck[] */
        $fellowship_decks = $fellowship->getDecks();
        foreach ($fellowship_decks as &$fellowship_deck) {
            $deck = $fellowship_deck->getDeck();
            if ($deck->getMajorVersion() > 0 && 1 == $deck->getMinorVersion()) {
                // There may be a perfect copy published
                /* @var $pub \App\Entity\Decklist */
                $pub = $deck->getChildren()->first();
                if ($pub) {
                    $data['deck'.$fellowship_deck->getDeckNumber().'_match'] = $pub->getId();
                }
            }
            // Finding duplicates
            $content = ['main' => $deck->getSlots()->getContent(), 'side' => $deck->getSideslots()->getContent()];
            $this_content = json_encode($content);
            $this_signature = md5((string) $this_content);
            $old_decklists = $this->decklistRepository->findBy(['signature' => $this_signature]);
            foreach ($old_decklists as $decklist) {
                /* @var $decklist \App\Entity\Decklist */
                if ($decklist->getParent() && $decklist->getParent()->getId() == $deck->getId()) {
                    continue;
                }
                $deck_content = ['main' => $decklist->getSlots()->getContent(), 'side' => $decklist->getSideslots()->getContent()];
                if (json_encode($deck_content) == $this_content) {
                    $data['deck'.$fellowship_deck->getDeckNumber().'_duplicates'][] = $decklist;
                }
            }
            $data['deck'.$fellowship_deck->getDeckNumber()] = $fellowship_deck->getDeck();
            $data['deck'.$fellowship_deck->getDeckNumber().'_signature'] = $this_signature;
        }
        /* @var $fellowship_decks \App\Entity\FellowshipDecklist[] */
        $fellowship_decklists = $fellowship->getDecklists();
        foreach ($fellowship_decklists as &$fellowship_decklist) {
            $data['deck'.$fellowship_decklist->getDeckNumber()] = $fellowship_decklist->getDecklist();
        }

        return $this->render('Fellowship/publish.html.twig', $data);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/fellowship/publish", name="fellowship_publish", methods={"POST"})
     */
    public function publishAction(Request $request, DecklistFactory $decklistFactory)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }
        $fellowship_id = intval(filter_var($request->request->get('fellowship_id'), FILTER_SANITIZE_NUMBER_INT));
        /* @var $fellowship \App\Entity\Fellowship */
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship || $fellowship->getUser()->getId() != $user->getId()) {
            throw new AccessDeniedHttpException("You don't have access to this fellowship.");
        }
        if ($fellowship->getIsPublic()) {
            $this->get('session')->getFlashBag()->set('error', 'This fellowship is already published.');

            return $this->redirect($this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship->getId()]));
        }
        $name = trim((string) filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $name = substr($name, 0, 60);
        if (empty($name)) {
            $name = 'Untitled Fellowship';
        }
        $descriptionMd = trim($request->request->get('descriptionMd') ?? '');
        $descriptionHtml = $this->texts->markdown($descriptionMd);
        $fellowship->setName($name);
        $fellowship->setNameCanonical($this->texts->slugify($name));
        $fellowship->setDescriptionMd($descriptionMd);
        $fellowship->setDescriptionHtml($descriptionHtml);
        $fellowship->setDateUpdate(new \DateTime());
        $fellowship->setIsPublic(true);
        $fellowship->setDatePublish(new \DateTime());
        foreach ($fellowship->getDecks() as &$fellowship_deck) {
            /* @var $fellowship_deck \App\Entity\FellowshipDeck */
            $new_id = intval(filter_var($request->request->get('deck_selection_'.$fellowship_deck->getDeckNumber()), FILTER_SANITIZE_NUMBER_INT));
            if ($new_id) {
                $decklist = $this->decklistRepository->find($new_id);
                if (!$decklist) {
                    throw new NotFoundHttpException('One of the selected decks does not exists.');
                }
            } else {
                $deck = $fellowship_deck->getDeck();
                $decklist = $decklistFactory->createDecklistFromDeck($deck, $deck->getName(), $deck->getDescriptionMd());
                $em->persist($decklist);
            }
            $fellowship_decklist = new FellowshipDecklist();
            $fellowship_decklist->setDecklist($decklist);
            $fellowship_decklist->setDeckNumber($fellowship_deck->getDeckNumber());
            $fellowship_decklist->setFellowship($fellowship);
            $em->remove($fellowship_deck);
            $fellowship->removeDeck($fellowship_deck);
            $fellowship->addDecklist($fellowship_decklist);
        }
        // Validate fellowship
        $problem = $this->fellowshipValidationHelper->findProblem($fellowship);
        if ($problem) {
            $this->get('session')->getFlashBag()->set('error', 'This fellowship cannot be published because it is invalid.');

            return $this->redirect($this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship->getId()]));
        }
        $em->persist($fellowship);
        $em->flush();

        return $this->redirect($this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship->getId(), 'fellowship_name' => $fellowship->getNameCanonical()]));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/fellowship/delete", name="fellowship_delete", methods={"POST"})
     */
    public function deleteAction(Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }
        $fellowship_id = filter_var($request->get('fellowship_id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $fellowship \App\Entity\Fellowship */
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship) {
            return $this->redirect($this->generateUrl('myfellowships_list'));
        }
        if ($fellowship->getUser()->getId() != $user->getId()) {
            throw new AccessDeniedHttpException("You don't have access to this fellowship.");
        }
        if ($fellowship->getNbVotes() || $fellowship->getNbfavorites() || $fellowship->getNbcomments()) {
            $this->get('session')->getFlashBag()->set('error', "You can't delete a published fellowship.");
        } else {
            /* @var $decks \App\Entity\FellowshipDeck[] */
            $decks = $fellowship->getDecks();
            foreach ($decks as $deck) {
                $em->remove($deck);
            }
            /* @var $decks \App\Entity\FellowshipDecklist[] */
            $decklists = $fellowship->getDecklists();
            foreach ($decklists as $decklist) {
                $em->remove($decklist);
            }
            $em->remove($fellowship);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('myfellowships_list'));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/fellowship/delete_list", name="fellowship_delete_list", methods={"POST"})
     */
    public function deleteListAction(Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }
        $list_id = explode('-', $request->get('ids'));
        $message = null;
        foreach ($list_id as $id) {
            /* @var $fellowship \App\Entity\Fellowship */
            $fellowship = $this->fellowshipRepository->find($id);
            if (!$fellowship) {
                continue;
            }
            if ($user->getId() != $fellowship->getUser()->getId()) {
                continue;
            }
            if ($fellowship->getNbVotes() || $fellowship->getNbfavorites() || $fellowship->getNbcomments()) {
                $message = "You can't delete a published fellowship. Unpublished selected fellowships were deleted.";
            } else {
                /* @var $decks \App\Entity\FellowshipDeck[] */
                $decks = $fellowship->getDecks();
                foreach ($decks as $deck) {
                    $em->remove($deck);
                }
                /* @var $decks \App\Entity\FellowshipDecklist[] */
                $decklists = $fellowship->getDecklists();
                foreach ($decklists as $decklist) {
                    $em->remove($decklist);
                }
                $em->remove($fellowship);
            }
        }
        $em->flush();
        $this->get('session')->getFlashBag()->set('notice', $message ?: 'Fellowships deleted.');

        return $this->redirect($this->generateUrl('myfellowships_list'));
    }

    /**
     * @return string
     */
    private function searchForm(Request $request)
    {
        $dbh = $this->getDoctrine()->getConnection();
        $cards_code = $request->query->get('cards');
        $author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);
        $fellowship_name = filter_var($request->query->get('name'), FILTER_SANITIZE_STRING);
        $nb_decks = intval(filter_var($request->query->get('nb_decks'), FILTER_SANITIZE_NUMBER_INT));
        $numcores = $request->query->get('numcores');
        $numplaysets = $request->query->get('numplaysets');
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
        $params = ['allowed' => $categories, 'on' => $on, 'off' => $off, 'author' => $author_name, 'name' => $fellowship_name, 'numcores' => $numcores, 'numplaysets' => $numplaysets];
        $params['sort_'.$sort] = ' selected="selected"';
        $params['nb_decks_selected'] = $nb_decks;
        if (!empty($cards_code) && is_array($cards_code)) {
            $cards = $dbh->executeQuery("SELECT\n    \t\t\t\tc.name,\n    \t\t\t\tc.code,\n                    s.code AS sphere_code,\n                    t.name AS type_name\n    \t\t\t\tFROM card c\n                    INNER JOIN sphere s ON s.id = c.sphere_id\n                    INNER JOIN type t ON t.id = c.type_id\n                    INNER JOIN card_printing cpr ON cpr.id = (SELECT cp2.id FROM card_printing cp2 WHERE cp2.card_id = c.id ORDER BY cp2.position ASC, cp2.id ASC LIMIT 1)\n                    INNER JOIN pack p ON p.id = cpr.pack_id\n                    WHERE c.code IN (?)\n    \t\t\t\tORDER BY c.code DESC", [$cards_code], [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY])->fetchAll();
            $params['cards'] = '';
            foreach ($cards as $card) {
                $params['cards'] .= $this->renderView('Search/card.html.twig', $card);
            }
        }

        return $this->renderView('Fellowship/form.html.twig', $params);
    }

    /**
     * @return Response
     *
     * @Route("/fellowships/search", name="fellowships_searchform", methods={"GET"})
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
        $searchForm = $this->renderView('Fellowship/form.html.twig', ['spheres' => $spheres, 'allowed' => $categories, 'on' => $on, 'off' => $off, 'author' => '', 'name' => '', 'numcores' => '12', 'numplaysets' => '4']);

        return $this->render('Fellowship/public-fellowships.html.twig', ['pagetitle' => 'Fellowship Search', 'fellowships' => null, 'url' => $request->getRequestUri(), 'header' => $searchForm, 'type' => 'find', 'pages' => null, 'prevurl' => null, 'nexturl' => null], $response);
    }

    /**
     * @return Response
     *
     * @Route(
     *     "/fellowship/export/octgn/{fellowship_id}",
     *     name="fellowship_export_octgn",
     *     methods={"GET"},
     *     requirements={"fellowship_id"="\d+"}
     * )
     */
    public function octgnexportAction($fellowship_id)
    {
        return $this->downloadFromSelection($fellowship_id, true);
    }

    /**
     * @return Response
     *
     * @Route(
     *     "/fellowship/export/text/{fellowship_id}",
     *     name="fellowship_export_text",
     *     methods={"GET"},
     *     requirements={"fellowship_id"="\d+"}
     * )
     */
    public function textexportAction($fellowship_id)
    {
        return $this->downloadFromSelection($fellowship_id, false);
    }

    /**
     * @return Response
     */
    public function downloadFromSelection($fellowship_id, $octgn)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }
        /* @var $fellowship \App\Entity\Fellowship */
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship) {
            throw new AccessDeniedHttpException("You don't have access to this fellowship.");
        }
        $fellowship_user = $fellowship->getUser();
        $is_public = $fellowship->getIsPublic();
        if ($fellowship_user->getId() != $user->getId() && !$fellowship_user->getIsShareDecks() && !$is_public) {
            throw new AccessDeniedHttpException("You don't have access to this fellowship.");
        }
        $tmpDir = $this->cacheDir;
        $file = tempnam($tmpDir, 'zip');
        if (false === $file) {
            throw new \RuntimeException("Cannot create a temporary file in {$tmpDir}");
        }
        $zip = new \ZipArchive();
        $res = $zip->open($file, \ZipArchive::OVERWRITE);
        if (true === $res) {
            $decks = [];
            /* @var $fellowship_decks \App\Entity\FellowshipDeck[] */
            $fellowship_decks = $fellowship->getDecks();
            foreach ($fellowship_decks as $fellowship_deck) {
                $decks[] = $fellowship_deck->getDeck();
            }
            /* @var $fellowship_decks \App\Entity\FellowshipDecklist[] */
            $fellowship_decklists = $fellowship->getDecklists();
            foreach ($fellowship_decklists as $fellowship_decklist) {
                $decks[] = $fellowship_decklist->getDecklist();
            }
            foreach ($decks as $deck) {
                /* @var $deck \App\Entity\Deck */
                if ($octgn) {
                    $extension = 'o8d';
                    $content = $this->renderView('Export/octgn.xml.twig', ['deck' => $deck->getTextExport()]);
                } else {
                    $extension = 'txt';
                    $content = $this->renderView('Export/plain.txt.twig', ['deck' => $deck->getTextExport()]);
                }
                $filename = $this->texts->slugify($deck->getName()).' '.$deck->getVersion().'.'.$extension;
                $zip->addFromString($filename, $content);
            }
            $zip->close();
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Length', (string) filesize($file));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->texts->slugify('RingsDB - Fellowship '.$fellowship_id).'.zip'));
        $response->setContent(file_get_contents($file));
        unlink($file);

        return $response;
    }

    /**
     * @return Response
     *
     * @Route("/user/fellowship_favorite", name="fellowship_favorite", methods={"POST"})
     */
    public function favoriteAction(Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }
        $fellowship_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $fellowship \App\Entity\Fellowship */
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship) {
            throw new NotFoundHttpException('Wrong id');
        }
        /* @var $author \App\Entity\User */
        $author = $fellowship->getUser();
        $dbh = $this->getDoctrine()->getConnection();
        $is_favorite = $dbh->executeQuery("SELECT\n\t\t\t\tcount(*)\n\t\t\t\tFROM fellowship d\n\t\t\t\tJOIN fellowship_favorite f ON f.fellowship_id = d.id\n\t\t\t\tWHERE f.user_id = ?\n\t\t\t\tAND d.id = ?", [$user->getId(), $fellowship_id])->fetch(\PDO::FETCH_NUM)[0];
        if ($is_favorite) {
            $fellowship->setNbfavorites($fellowship->getNbFavorites() - 1);
            $fellowship->removeFavorite($user);
            $fellowship->setDateUpdate(new \DateTime());
            if ($author->getId() != $user->getId()) {
                $author->setReputation($author->getReputation() - 5);
            }
        } else {
            $fellowship->setNbfavorites($fellowship->getNbFavorites() + 1);
            $fellowship->addFavorite($user);
            $fellowship->setDateUpdate(new \DateTime());
            if ($author->getId() != $user->getId()) {
                $author->setReputation($author->getReputation() + 5);
            }
        }
        $em->flush();

        return new Response($fellowship->getNbFavorites());
    }

    /*
     * records a user's comment
     */
    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/user/fellowship_comment", name="fellowship_comment", methods={"POST"})
     */
    public function commentAction(Request $request, MailerInterface $mailer, UserRepository $userRepository)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }
        $fellowship_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship) {
            throw new BadRequestHttpException('Wrong fellowship id');
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
            $comment = new FellowshipComment();
            $comment->setText($comment_html);
            $comment->setDateCreation($now);
            $comment->setUser($user);
            $comment->setFellowship($fellowship);
            $comment->setIsHidden(false);
            $em->persist($comment);
            $fellowship->setDateUpdate($now);
            $fellowship->setDateLastComment($comment->getDateCreation());
            $fellowship->setNbcomments($fellowship->getNbcomments() + 1);
            $em->flush();
            // send emails
            $spool = [];
            if ($fellowship->getUser()->getIsNotifAuthor()) {
                $spool[$fellowship->getUser()->getEmail()] = 'Emails/newfellowshipcomment_author.html.twig';
            }
            foreach ($fellowship->getComments() as $comment) {
                /* @var $comment \App\Entity\FellowshipComment */
                $commenter = $comment->getUser();
                if ($commenter->getIsNotifCommenter()) {
                    if (!isset($spool[$commenter->getEmail()])) {
                        $spool[$commenter->getEmail()] = 'Emails/newfellowshipcomment_commenter.html.twig';
                    }
                }
            }
            foreach ($mentionned_usernames as $mentionned_username) {
                /* @var $mentionned_user \App\Entity\User */
                $mentionned_user = $userRepository->findOneBy(['username' => $mentionned_username]);
                if ($mentionned_user && $mentionned_user->getIsNotifMention()) {
                    if (!isset($spool[$mentionned_user->getEmail()])) {
                        $spool[$mentionned_user->getEmail()] = 'Emails/newfellowshipcomment_mentionned.html.twig';
                    }
                }
            }
            unset($spool[$user->getEmail()]);
            $email_data = ['username' => $user->getUsername(), 'fellowship_name' => $fellowship->getName(), 'url' => $this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship->getId(), 'fellowship_name' => $fellowship->getNameCanonical()], UrlGeneratorInterface::ABSOLUTE_URL).'#'.$comment->getId(), 'comment' => $comment_html, 'profile' => $this->generateUrl('user_profile_edit', [], UrlGeneratorInterface::ABSOLUTE_URL)];
            foreach ($spool as $email => $view) {
                $message = (new Email())->subject('[ringsdb] New comment')->from(new Address('seastan@ringsdb.com', 'Seastan'))->to(new Address($email, $user->getUsername()))->html($this->renderView($view, $email_data));
                $mailer->send($message);
            }
        }

        return $this->redirect($this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship_id, 'fellowship_name' => $fellowship->getNameCanonical()]));
    }

    /*
     * hides a comment, or if $hidden is false, unhide a comment
     */
    /**
     * @return Response
     *
     * @Route(
     *     "/user/fellowship_hidecomment/{comment_id}/{hidden}",
     *     name="fellowship_comment_hide",
     *     methods={"POST"}
     * )
     */
    public function hidecommentAction($comment_id, $hidden, FellowshipCommentRepository $fellowshipCommentRepository)
    {
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        $comment = $fellowshipCommentRepository->find($comment_id);
        if (!$comment) {
            throw new BadRequestHttpException('Unable to find comment');
        }
        if ($comment->getFellowship()->getUser()->getId() !== $user->getId()) {
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
     * @Route("/user/fellowship_like", name="fellowship_like", methods={"POST"})
     */
    public function voteAction(Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }
        $fellowship_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $fellowship \App\Entity\Fellowship */
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship) {
            throw new BadRequestHttpException('Unable to find fellowship');
        }
        if ($fellowship->getUser()->getId() != $user->getId()) {
            $query = $this->fellowshipRepository->createQueryBuilder('d')->innerJoin('d.votes', 'u')->where('d.id = :fellowship_id')->andWhere('u.id = :user_id')->setParameter('fellowship_id', $fellowship_id)->setParameter('user_id', $user->getId())->getQuery();
            $result = $query->getResult();
            if (empty($result)) {
                /* @var $author \App\Entity\User */
                $author = $fellowship->getUser();
                $author->setReputation($author->getReputation() + 1);
                $fellowship->addVote($user);
                $fellowship->setDateUpdate(new \DateTime());
                $fellowship->setNbVotes($fellowship->getNbVotes() + 1);
                $this->getDoctrine()->getManager()->flush();
            }
        }

        return new Response($fellowship->getNbVotes());
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/f/{username}", name="fellowship_byauthor", methods={"GET"})
     */
    public function byauthorAction($username)
    {
        return $this->redirect($this->generateUrl('fellowships_list', ['type' => 'find', 'author' => $username]));
    }

    /**
     * @param int $page
     *
     * @return Response
     *
     * @Route(
     *     "/fellowships/{type}/{page}",
     *     name="fellowships_list",
     *     methods={"GET"},
     *     requirements={"page"="\d+"},
     *     defaults={"type"="popular", "page"=1}
     * )
     */
    public function listAction($type, $page = 1, Request $request, FellowshipManager $fellowshipManager)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        /**
         * @var FellowshipManager $fellowship_manager
         */
        $fellowship_manager = $fellowshipManager;
        $fellowship_manager->setLimit(30);
        $fellowship_manager->setPage($page);
        $header = '';
        switch ($type) {
            case 'find':
                $pagetitle = 'Fellowship search results';
                $header = $this->searchForm($request);
                $fellowship_manager->setUser($this->getUser());
                $paginator = $fellowship_manager->findFellowshipsWithComplexSearch();
                break;
            case 'favorites':
                $response->setPrivate();
                $user = $this->getUser();
                if ($user) {
                    $paginator = $fellowship_manager->findFellowshipsByFavorite($user);
                } else {
                    $paginator = $fellowship_manager->getEmptyList();
                }
                $pagetitle = 'Favorite Fellowships';
                break;
            case 'mine':
                $response->setPrivate();
                $user = $this->getUser();
                if ($user) {
                    $paginator = $fellowship_manager->findFellowshipsByAuthor($user);
                } else {
                    $paginator = $fellowship_manager->getEmptyList();
                }
                $pagetitle = 'My Public Fellowships';
                break;
            case 'recent':
                $paginator = $fellowship_manager->findFellowshipsByAge();
                $pagetitle = 'Recent Fellowships';
                break;
            case 'halloffame':
                $paginator = $fellowship_manager->findFellowshipsInHallOfFame();
                $pagetitle = 'Hall of Fame';
                break;
            case 'hottopics':
                $paginator = $fellowship_manager->findFellowshipsInHotTopic();
                $pagetitle = 'Hot Topics';
                break;
            case 'popular':
            default:
                $paginator = $fellowship_manager->findFellowshipsByPopularity();
                $pagetitle = 'Popular Fellowships';
                break;
        }

        return $this->render('Fellowship/public-fellowships.html.twig', ['pagetitle' => $pagetitle, 'pagedescription' => 'Browse the collection of thousands of premade decks.', 'fellowships' => $paginator, 'url' => $request->getRequestUri(), 'header' => $header, 'type' => $type, 'pages' => $fellowship_manager->getClosePages(), 'prevurl' => $fellowship_manager->getPreviousUrl(), 'nexturl' => $fellowship_manager->getNextUrl()], $response);
    }
}
