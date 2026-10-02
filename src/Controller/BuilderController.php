<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Deck;
use App\Entity\Deckchange;
use App\Entity\Decklist;
use App\Entity\Pack;
use App\Entity\User;
use App\Repository\CardRepository;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use App\Repository\PackRepository;
use App\Services\Decks;
use App\Services\Diff;
use App\Services\Texts;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;

class BuilderController extends AbstractController
{
    use CurrentUserTrait;
    /**
     * @var Decks
     */
    private $decks;
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
     * @var CardRepository
     */
    private $cardRepository;
    /**
     * @var DeckRepository
     */
    private $deckRepository;
    /**
     * @var PackRepository
     */
    private $packRepository;

    public function __construct(Decks $decks, Texts $texts, int $cacheExpiration, string $cacheDir, CardRepository $cardRepository, DeckRepository $deckRepository, PackRepository $packRepository)
    {
        $this->decks = $decks;
        $this->texts = $texts;
        $this->cacheExpiration = $cacheExpiration;
        $this->cacheDir = $cacheDir;
        $this->cardRepository = $cardRepository;
        $this->deckRepository = $deckRepository;
        $this->packRepository = $packRepository;
    }

    /**
     * @Route("/deck/new", name="deck_buildform", methods={"GET"})
     */
    public function newAction(): RedirectResponse
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $deck \App\Entity\Deck */
        $deck = new Deck();
        $deck->setName('New Deck');
        $deck->setDescriptionMd('');
        $deck->setLastPack(null);
        $deck->setProblem('too_few_heroes');
        $deck->setTags('');
        $deck->setUser($this->currentUser());
        $em->persist($deck);
        $em->flush();

        return $this->redirect($this->generateUrl('deck_edit', ['deck_id' => $deck->getId()]));
    }

    /**
     * @Route("/deck/edit/{deck_id}", name="deck_edit", methods={"GET"}, requirements={"deck_id"="\d+"})
     */
    public function editAction($deck_id): Response
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $deck \App\Entity\Deck */
        $deck = $this->deckRepository->find($deck_id);
        if (!$deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }
        if ($this->currentUser()->getId() != $deck->getUser()->getId()) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck.');
        }

        return $this->render('Builder/deckedit.html.twig', ['pagetitle' => 'Deckbuilder', 'deck' => $deck]);
    }

    /**
     * @Route(
     *     "/deck/view/{deck_id}",
     *     name="deck_view",
     *     methods={"GET"},
     *     requirements={"deck_id"="\d+"},
     *     defaults={"deck_id"=0}
     * )
     */
    public function viewAction($deck_id): Response
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $deck \App\Entity\Deck */
        $deck = $this->deckRepository->find($deck_id);
        if (!$deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }
        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck->getUser()->getId();
        if (!$deck->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
        }

        return $this->render('Builder/deckview.html.twig', ['pagetitle' => 'Deckbuilder', 'deck' => $deck, 'deck_id' => $deck_id, 'is_owner' => $is_owner]);
    }

    /**
     * @Route("/deck/import", name="deck_import", methods={"GET"})
     */
    public function importAction(): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);

        return $this->render('Builder/directimport.html.twig', ['pagetitle' => 'Import a deck'], $response);
    }

    /**
     * @Route("/deck/fileimport", name="deck_fileimport", methods={"POST"})
     */
    public function fileimportAction(Request $request): Response
    {
        $filetype = filter_var($request->get('type'), FILTER_SANITIZE_STRING);
        $uploadedFile = $request->files->get('upfile');
        if (!isset($uploadedFile)) {
            throw new UnprocessableEntityHttpException('No file uploaded');
        }
        $origname = $uploadedFile->getClientOriginalName();
        $origext = $uploadedFile->getClientOriginalExtension();
        $filename = $uploadedFile->getPathname();
        if (function_exists('finfo_open')) {
            // return mime type ala mimetype extension
            $finfo = finfo_open(FILEINFO_MIME);
            $mime = false !== $finfo ? (string) finfo_file($finfo, $filename) : '';
            // check to see if the mime-type starts with 'text'
            $is_text = 'text' == substr($mime, 0, 4) || 'application/xml' == substr($mime, 0, 15);
            if (!$is_text) {
                throw new UnprocessableEntityHttpException('Bad file');
            }
        }
        if ('octgn' == $filetype || 'auto' == $filetype && 'o8d' == $origext) {
            $parse = $this->parseOctgnImport(file_get_contents($filename));
        } else {
            $parse = $this->parseTextImport(file_get_contents($filename));
        }

        return $this->forward('App\\Controller\\BuilderController::saveAction', ['name' => str_replace(".{$origext}", '', $origname), 'content' => json_encode($parse['content']), 'description' => $parse['description']]);
    }

    /**
     * @return array{content: array{main: array<int|string, int>, side: array<int|string, int>}, description: string}
     */
    public function parseTextImport($text): array
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        $content = ['main' => [], 'side' => []];
        $addToSideboard = false;
        $text = str_replace(['“', '”', '’', '&rsquo;'], ['"', '"', '\'', '\''], $text);
        $lines = explode("\n", $text);
        $identity = null;
        foreach ($lines as $line) {
            $matches = [];
            $pack_name = null;
            $name = null;
            $quantity = 1;
            if ('Sideboard' == trim($line)) {
                $addToSideboard = true;
                continue;
            }
            if (preg_match('/(x\\d+|\\d+x)/u', $line, $matches)) {
                $quantity = intval(str_replace('x', '', $matches[1]));
                $line = str_replace($matches[1], '', $line);
            }
            if (preg_match('/^\\s*([\\pLl\\pLu\\pN\\-\\.\'\\!\\: ]+)\\(?([^\\)]*)\\)?/u', $line, $matches)) {
                $name = trim($matches[1]);
                // the pack name, empty when absent
                $pack_name = trim($matches[2]);
            }
            $card = null;
            $pack = null;
            if ($pack_name) {
                /* @var $pack Pack */
                $pack = $this->packRepository->findOneBy(['name' => $pack_name]);
                if (!$pack) {
                    $pack = $this->packRepository->findOneBy(['code' => $pack_name]);
                }
            }
            if ($pack) {
                // a card belongs to its packs through its printings
                /* @var $card \App\Entity\Card */
                $card = $em->createQuery('SELECT c FROM App:Card c JOIN c.printings p WHERE c.name = :name AND p.pack = :pack ORDER BY c.code')->setParameter('name', $name)->setParameter('pack', $pack)->setMaxResults(1)->getOneOrNullResult();
            } else {
                /* @var $pack \App\Entity\Card */
                $card = $this->cardRepository->findOneBy(['name' => $name]);
            }
            if ($card) {
                if ($addToSideboard) {
                    $content['side'][$card->getCode()] = $quantity;
                } else {
                    $content['main'][$card->getCode()] = $quantity;
                }
            }
        }

        return ['content' => $content, 'description' => ''];
    }

    /**
     * The card of a printing, by its octgnid. The Messenger of the King version of a hero has the
     * octgnid of the hero: the original card (the lowest id) is chosen, as before the printings
     * refactor.
     *
     * @param string $octgnid
     */
    private function findCardByOctgnid(EntityManager $em, $octgnid): ?Card
    {
        $printing = $em->createQueryBuilder()->select('cp')->from('App:CardPrinting', 'cp')->join('cp.card', 'c')->where('cp.octgnid = :octgnid')->setParameter('octgnid', $octgnid)->orderBy('c.id', 'ASC')->setMaxResults(1)->getQuery()->getOneOrNullResult();

        return $printing ? $printing->getCard() : null;
    }

    /**
     * @return array{content: array{main: array<int|string, int>, side: array<int|string, int>}, description: string}
     */
    public function parseOctgnImport($octgn): array
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        $crawler = new Crawler();
        $crawler->addXmlContent($octgn);
        // read octgnid
        $octgnids = [];
        $sideoctgnids = [];
        $cardcrawler = $crawler->filter('deck > section[name!="Sideboard"] > card');
        /** @var \DOMElement $domElement */
        foreach ($cardcrawler as $domElement) {
            $octgnids[$domElement->getAttribute('id')] = intval($domElement->getAttribute('qty'));
        }
        $cardcrawler = $crawler->filter('deck > section[name="Sideboard"] > card');
        /** @var \DOMElement $domElement */
        foreach ($cardcrawler as $domElement) {
            $sideoctgnids[$domElement->getAttribute('id')] = intval($domElement->getAttribute('qty'));
        }
        // read desc
        $desccrawler = $crawler->filter('deck > notes');
        $descriptions = [];
        /** @var \DOMElement $domElement */
        foreach ($desccrawler as $domElement) {
            $descriptions[] = $domElement->nodeValue;
        }
        $content = [];
        foreach ($octgnids as $octgnid => $qty) {
            $card = $this->findCardByOctgnid($em, $octgnid);
            if ($card) {
                // several printings of a card can have their own octgnid
                $content[$card->getCode()] = ($content[$card->getCode()] ?? 0) + $qty;
            }
        }
        $sidecontent = [];
        foreach ($sideoctgnids as $octgnid => $qty) {
            $card = $this->findCardByOctgnid($em, $octgnid);
            if ($card) {
                $sidecontent[$card->getCode()] = ($sidecontent[$card->getCode()] ?? 0) + $qty;
            }
        }
        $description = implode("\n", $descriptions);

        return ['content' => ['main' => $content, 'side' => $sidecontent], 'description' => $description];
    }

    /**
     * @Route(
     *     "/deck/export/text/{deck_id}",
     *     name="deck_export_text",
     *     methods={"GET"},
     *     requirements={"deck_id"="\d+"}
     * )
     */
    public function textexportAction($deck_id): Response
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $deck \App\Entity\Deck */
        $deck = $this->deckRepository->find($deck_id);
        if (!$deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }
        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck->getUser()->getId();
        if (!$deck->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
        }
        $content = $this->renderView('Export/plain.txt.twig', ['deck' => $deck->getTextExport()]);
        $content = str_replace("\n", "\r\n", $content);
        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->texts->slugify($deck->getName()).'.txt'));
        $response->setContent($content);

        return $response;
    }

    /**
     * @Route(
     *     "/deck/export/octgn/{deck_id}",
     *     name="deck_export_octgn",
     *     methods={"GET"},
     *     requirements={"deck_id"="\d+"}
     * )
     */
    public function octgnexportAction($deck_id): Response
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $deck \App\Entity\Deck */
        $deck = $this->deckRepository->find($deck_id);
        if (!$deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }
        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck->getUser()->getId();
        if (!$deck->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
        }
        $content = $this->renderView('Export/octgn.xml.twig', ['deck' => $deck->getTextExport()]);
        $response = new Response();
        $response->headers->set('Content-Type', 'application/octgn');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->texts->slugify($deck->getName()).'.o8d'));
        $response->setContent($content);

        return $response;
    }

    /**
     * @Route("/deck/clone/{deck_id}", name="deck_clone", methods={"GET"}, requirements={"deck_id"="\d+"})
     */
    public function cloneAction($deck_id): Response
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $deck \App\Entity\Deck */
        $deck = $this->deckRepository->find($deck_id);
        if (!$deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }
        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck->getUser()->getId();
        if (!$deck->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
        }
        $content = ['main' => [], 'side' => []];
        foreach ($deck->getSlots() as $slot) {
            $content['main'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }
        foreach ($deck->getSideslots() as $slot) {
            $content['side'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        return $this->forward('App\\Controller\\BuilderController::saveAction', ['name' => $deck->getName().' (clone)', 'content' => json_encode($content), 'decklist_id' => $deck->getParent() ? $deck->getParent()->getId() : null]);
    }

    /**
     * @Route("/deck/save", name="deck_save", methods={"POST"})
     */
    public function saveAction(Request $request): Response
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user User */
        $user = $this->currentUser();
        if (count($user->getDecks()) > $user->getMaxNbDecks()) {
            throw new UnprocessableEntityHttpException('You have reached the maximum number of decks allowed. Delete some decks or increase your reputation.');
        }
        $id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $deck = null;
        $source_deck = null;
        if ($id) {
            /* @var $deck \App\Entity\Deck */
            $deck = $this->deckRepository->find($id);
            if (!$deck || $user->getId() != $deck->getUser()->getId()) {
                throw new AccessDeniedHttpException("You don't have access to this deck.");
            }
            $source_deck = $deck;
        }
        $cancel_edits = (bool) filter_var($request->get('cancel_edits'), FILTER_SANITIZE_NUMBER_INT);
        if ($cancel_edits) {
            if ($deck) {
                $this->decks->revertDeck($deck);
            }

            return $this->redirect($this->generateUrl('decks_list'));
        }
        $is_copy = (bool) filter_var($request->get('copy'), FILTER_SANITIZE_NUMBER_INT);
        if ($is_copy || !$id) {
            /* @var $deck \App\Entity\Deck */
            $deck = new Deck();
        }
        $content = (array) json_decode($request->get('content'));
        if (!isset($content['main']) || empty($content['main'])) {
            return new Response('Cannot import an empty deck');
        }
        $name = filter_var($request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        if (empty($name)) {
            $name = 'Untitled Deck';
        }
        $decklist_id = filter_var($request->get('decklist_id'), FILTER_SANITIZE_NUMBER_INT);
        $description = trim($request->get('description') ?? '');
        $tags = filter_var($request->get('tags'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $this->decks->saveDeck($this->getUser(), $deck, $decklist_id, $name, $description, $tags, $content, $source_deck ?: null);
        $em->flush();

        return $this->redirect($this->generateUrl('decks_list'));
    }

    /**
     * @Route("/deck/save-ajax", name="deck_save_ajax", methods={"POST"})
     */
    public function saveAjaxAction(Request $request): JsonResponse
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user User */
        $user = $this->currentUser();
        if (count($user->getDecks()) > $user->getMaxNbDecks()) {
            return new JsonResponse(['success' => false, 'error' => 'You have reached the maximum number of decks allowed.'], 422);
        }
        $id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $deck = null;
        $source_deck = null;
        if ($id) {
            /* @var $deck \App\Entity\Deck */
            $deck = $this->deckRepository->find($id);
            if (!$deck || $user->getId() != $deck->getUser()->getId()) {
                return new JsonResponse(['success' => false, 'error' => "You don't have access to this deck."], 403);
            }
            $source_deck = $deck;
        } else {
            $deck = new Deck();
        }
        $content = (array) json_decode($request->get('content'));
        if (!isset($content['main']) || empty($content['main'])) {
            return new JsonResponse(['success' => false, 'error' => 'Cannot save an empty deck.'], 422);
        }
        $name = filter_var($request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        if (empty($name)) {
            $name = 'Untitled Deck';
        }
        $decklist_id = filter_var($request->get('decklist_id'), FILTER_SANITIZE_NUMBER_INT);
        $description = trim($request->get('description') ?? '');
        $tags = filter_var($request->get('tags'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $this->decks->saveDeck($user, $deck, $decklist_id, $name, $description, $tags, $content, $source_deck ?: null);
        $em->flush();

        return new JsonResponse(['success' => true, 'id' => $deck->getId()]);
    }

    /**
     * @Route("/deck/delete", name="deck_delete", methods={"POST"})
     */
    public function deleteAction(Request $request): RedirectResponse
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        $deck_id = filter_var($request->get('deck_id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $deck \App\Entity\Deck */
        $deck = $this->deckRepository->find($deck_id);
        if (!$deck) {
            return $this->redirect($this->generateUrl('decks_list'));
        }
        if ($this->currentUser()->getId() != $deck->getUser()->getId()) {
            throw new AccessDeniedHttpException("You don't have access to this deck.");
        }
        if (count($deck->getFellowships())) {
            $this->get('session')->getFlashBag()->set('error', "You can't delete a deck that is member of a fellowship.");
        } else {
            foreach ($deck->getChildren() as $decklist) {
                $decklist->setParent(null);
            }
            $em->remove($deck);
            $em->flush();
            $this->get('session')->getFlashBag()->set('notice', 'Deck deleted.');
        }

        return $this->redirect($this->generateUrl('decks_list'));
    }

    /**
     * @Route("/deck/delete_list", name="deck_delete_list", methods={"POST"})
     */
    public function deleteListAction(Request $request): RedirectResponse
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        $list_id = explode('-', $request->get('ids'));
        foreach ($list_id as $id) {
            /* @var $deck \App\Entity\Deck */
            $deck = $this->deckRepository->find($id);
            if (!$deck) {
                continue;
            }
            if ($this->currentUser()->getId() != $deck->getUser()->getId()) {
                continue;
            }
            foreach ($deck->getChildren() as $decklist) {
                $decklist->setParent(null);
            }
            $em->remove($deck);
        }
        $em->flush();
        $this->get('session')->getFlashBag()->set('notice', 'Decks deleted.');

        return $this->redirect($this->generateUrl('decks_list'));
    }

    /**
     * @Route(
     *     "/deck/compare/{deck1_id}/{deck2_id}",
     *     name="decks_diff",
     *     methods={"GET"},
     *     requirements={"deck1_id"="\d+", "deck2_id"="\d+"}
     * )
     */
    public function compareAction($deck1_id, $deck2_id, Diff $diffService): Response
    {
        /* @var $deck1 \App\Entity\Deck */
        $deck1 = $this->deckRepository->find($deck1_id);
        /* @var $deck2 \App\Entity\Deck */
        $deck2 = $this->deckRepository->find($deck2_id);
        if (!$deck1 || !$deck2) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }
        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck1->getUser()->getId();
        if (!$deck1->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
        }
        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck2->getUser()->getId();
        if (!$deck2->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
        }

        return $this->render('Compare/deck_compare.html.twig', ['deck1' => $deck1, 'deck2' => $deck2, 'hero_deck' => $diffService->compareSlots([$deck1->getSlots()->getHeroDeck(), $deck2->getSlots()->getHeroDeck()]), 'draw_deck' => $diffService->compareSlots([$deck1->getSlots()->getDrawDeck(), $deck2->getSlots()->getDrawDeck()]), 'sideboard' => $diffService->compareSlots([$deck1->getSideSlots(), $deck2->getSideSlots()])]);
    }

    /**
     * @Route("/decks", name="decks_list", methods={"GET"})
     */
    public function listAction(Request $request): Response
    {
        /* @var $user User */
        $user = $this->currentUser();
        $decksService = $this->decks;
        $showAll = (bool) $request->query->get('all', false);
        $limit = $showAll ? null : 10;
        $totalDecks = $decksService->countDecksForUser($user);
        if (0 === $totalDecks) {
            return $this->render('Builder/no-decks.html.twig', ['pagetitle' => 'My Decks', 'pagedescription' => 'Create custom decks with the help of a powerful deckbuilder.', 'nbmax' => $user->getMaxNbDecks()]);
        }
        // The service returns lightweight per-deck arrays (id/name/version/problem/tags/
        // last_pack/slots/heroes) already shaped for the template — no entity hydration.
        $decks = $decksService->getDecksWithSlotsForUser($user, $limit);
        $tags = [];
        foreach ($decks as $deck) {
            $tags[] = $deck['tags'];
        }
        $tags = array_unique($tags);

        return $this->render('Builder/decks.html.twig', ['pagetitle' => 'My Decks', 'pagedescription' => 'Create custom decks with the help of a powerful deckbuilder.', 'decks' => $decks, 'tags' => $tags, 'nbmax' => $user->getMaxNbDecks(), 'nbdecks' => $totalDecks, 'nbloaded' => count($decks), 'cannotcreate' => $user->getMaxNbDecks() <= $totalDecks, 'show_all' => $showAll]);
    }

    /**
     * @Route("/deck/copy/{decklist_id}", name="deck_copy", requirements={"decklist_id"="\d+"})
     */
    public function copyAction($decklist_id, DecklistRepository $decklistRepository): Response
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $decklist Decklist */
        $decklist = $decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }
        $content = ['main' => [], 'side' => []];
        foreach ($decklist->getSlots() as $slot) {
            $content['main'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }
        foreach ($decklist->getSideslots() as $slot) {
            $content['side'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        return $this->forward('App\\Controller\\BuilderController::saveAction', ['name' => $decklist->getName(), 'content' => json_encode($content), 'decklist_id' => $decklist_id]);
    }

    /**
     * @Route("/deck/export/octgn/list", name="deck_export_octgn_list", methods={"GET"})
     */
    public function octgnexportListAction(Request $request): Response
    {
        $list_id = $request->get('ids');

        return $this->downloadFromSelection($list_id, true);
    }

    /**
     * @Route("/deck/export/text/list", name="deck_export_text_list", methods={"GET"})
     */
    public function textexportListAction(Request $request): Response
    {
        $list_id = $request->get('ids');

        return $this->downloadFromSelection($list_id, false);
    }

    public function downloadFromSelection($list_id, $octgn): Response
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        $tmpDir = $this->cacheDir;
        $file = tempnam($tmpDir, 'zip');
        if (false === $file) {
            throw new \RuntimeException("Cannot create a temporary file in {$tmpDir}");
        }
        $zip = new \ZipArchive();
        $res = $zip->open($file, \ZipArchive::OVERWRITE);
        if (true === $res) {
            foreach ($list_id as $id) {
                /* @var $deck \App\Entity\Deck */
                $deck = $this->deckRepository->find($id);
                if (!$deck) {
                    continue;
                }
                if ($this->currentUser()->getId() != $deck->getUser()->getId()) {
                    continue;
                }
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
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->texts->slugify('ringsdb').'.zip'));
        $response->setContent(file_get_contents($file));
        unlink($file);

        return $response;
    }

    /**
     * @Route("/deck/import/all", name="decks_upload_all", methods={"POST"})
     */
    public function uploadallAction(Request $request): RedirectResponse
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        // time-consuming task
        ini_set('max_execution_time', '300');
        $uploadedFile = $request->files->get('uparchive');
        if (!isset($uploadedFile)) {
            throw new UnprocessableEntityHttpException('No file uploaded');
        }
        $filename = $uploadedFile->getPathname();
        if (function_exists('finfo_open')) {
            // return mime type ala mimetype extension
            $finfo = finfo_open(FILEINFO_MIME);
            $mime = false !== $finfo ? (string) finfo_file($finfo, $filename) : '';
            // check to see if the mime-type is 'zip'
            if ('application/zip' !== substr($mime, 0, 15)) {
                throw new UnprocessableEntityHttpException('Bad file');
            }
        }
        $zip = new \ZipArchive();
        $res = $zip->open($filename);
        if (true === $res) {
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $name = (string) $zip->getNameIndex($i);
                if ('o8d' == pathinfo($name, PATHINFO_EXTENSION)) {
                    $parse = $this->parseOctgnImport($zip->getFromIndex($i));
                } else {
                    $parse = $this->parseTextImport($zip->getFromIndex($i));
                }
                $deckname = pathinfo($name, PATHINFO_FILENAME);
                // one deck per file, even without any card (an empty deck)
                /* @var $deck \App\Entity\Deck */
                $deck = new Deck();
                $em->persist($deck);
                $this->decks->saveDeck($this->getUser(), $deck, null, $deckname, '', '', $parse['content'], null);
            }
        }
        $zip->close();
        $em->flush();
        $this->get('session')->getFlashBag()->set('notice', 'Decks imported.');

        return $this->redirect($this->generateUrl('decks_list'));
    }

    /**
     * @Route("/deck/autosave", name="deck_autosave", methods={"POST"})
     */
    public function autosaveAction(Request $request, LoggerInterface $logger): Response
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user User */
        $user = $this->currentUser();
        $deck_id = $request->get('deck_id');
        /* @var $deck \App\Entity\Deck */
        $deck = $this->deckRepository->find($deck_id);
        if (!$deck) {
            throw new UnprocessableEntityHttpException('Cannot find deck '.$deck_id);
        }
        if ($user->getId() != $deck->getUser()->getId()) {
            throw new AccessDeniedHttpException("You don't have access to this deck.");
        }
        // decoded as arrays: count() of an object is a warning since PHP 7.2
        $diff = json_decode((string) $request->get('diff'), true);
        if (!is_array($diff) || 4 != count($diff) && 2 != count($diff)) {
            $logger->error('cannot use diff', (array) $diff);
            throw new UnprocessableEntityHttpException('Wrong content '.json_encode($diff));
        }
        // [main added, main removed, side added, side removed], the side parts may be missing
        $parts = array_map(fn ($part) => is_array($part) ? count($part) : 0, $diff);
        if (array_sum($parts) > 0) {
            /* @var $change \App\Entity\Deckchange */
            $change = new Deckchange();
            $change->setDeck($deck);
            $change->setVariation((string) json_encode($diff));
            $change->setIsSaved(false);
            $em->persist($change);
            $em->flush();

            return new Response($change->getDatecreation()->format('c'));
        }

        return new Response();
    }
}
