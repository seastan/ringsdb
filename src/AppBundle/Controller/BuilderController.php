<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use AppBundle\Entity\Deck;
use AppBundle\Entity\Deckslot;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Deckchange;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BuilderController extends Controller {
    public function buildformAction(Request $request) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        $em = $this->getDoctrine()->getManager();

        $heroType = $em->getRepository('AppBundle:Type')->findOneBy(['code' => 'hero']);
        $cards = $em->getRepository('AppBundle:Card')->findBy(['type' => $heroType], ['sphere' => 'ASC', 'name' => 'ASC', 'pack' => 'ASC']);

        $heroes = array_map(function($card) {
            return $this->get('cards_data')->getCardInfo($card, false);
        }, $cards);

        return $this->render('AppBundle:Builder:initbuild.html.twig', [
            'pagetitle' => "New deck",
            'heroes' => $heroes
        ], $response);
    }

    public function initbuildAction(Request $request) {
        $em = $this->getDoctrine()->getManager();

        $hero1Code = $request->request->get('hero1');
        $hero2Code = $request->request->get('hero2');
        $hero3Code = $request->request->get('hero3');

        if (!$hero1Code && !$hero2Code && !$hero3Code) {
            $this->get('session')->getFlashBag()->set('error', "At least one hero is required.");

            return $this->redirect($this->generateUrl('deck_buildform'));
        }

        $heroType = $em->getRepository('AppBundle:Type')->findOneBy(['code' => 'hero']);
        $tags = [];
        $names = [];
        $lastPack = null;

        $hero1 = null;
        if ($hero1Code) {
            $hero1 = $em->getRepository('AppBundle:Card')->findOneBy(['type' => $heroType, 'code' => $hero1Code]);

            if (!$hero1) {
                $this->get('session')->getFlashBag()->set('error', "Invalid selection for hero number 1.");

                return $this->redirect($this->generateUrl('deck_buildform'));
            }

            if (!$lastPack || $hero1->getPack()->getCode() > $lastPack->getCode()) {
                $lastPack = $hero1->getPack();
            }

            $tags[] = $hero1->getSphere()->getCode();
            $names[] = $hero1->getName();
        }

        $hero2 = null;
        if ($hero2Code) {
            $hero2 = $em->getRepository('AppBundle:Card')->findOneBy(['type' => $heroType, 'code' => $hero2Code]);

            if (!$hero2) {
                $this->get('session')->getFlashBag()->set('error', "Invalid selection for hero number 2.");

                return $this->redirect($this->generateUrl('deck_buildform'));
            }

            if (!$lastPack || $hero2->getPack()->getCode() > $lastPack->getCode()) {
                $lastPack = $hero2->getPack();
            }

            $tags[] = $hero2->getSphere()->getCode();
            $names[] = $hero2->getName();
        }

        $hero3 = null;
        if ($hero3Code) {
            $hero3 = $em->getRepository('AppBundle:Card')->findOneBy(['type' => $heroType, 'code' => $hero3Code]);

            if (!$hero3) {
                $this->get('session')->getFlashBag()->set('error', "Invalid selection for hero number 3.");

                return $this->redirect($this->generateUrl('deck_buildform'));
            }

            if (!$lastPack || $hero3->getPack()->getCode() > $lastPack->getCode()) {
                $lastPack = $hero3->getPack();
            }

            $tags[] = $hero3->getSphere()->getCode();
            $names[] = $hero3->getName();
        }

        $name = sprintf("New deck: %s", implode(', ', $names));

        $deck = new Deck();
        $deck->setName($name);
        $deck->setDescriptionMd('');
        $deck->setLastPack($lastPack);
        $deck->setProblem('too_few_cards');
        $deck->setTags(join(' ', array_unique($tags)));
        $deck->setUser($this->getUser());

        if ($hero1) {
            $slot = new Deckslot();
            $slot->setCard($hero1);
            $slot->setQuantity(1);
            $slot->setDeck($deck);
            $deck->addSlot($slot);
        }

        if ($hero2) {
            $slot = new Deckslot();
            $slot->setCard($hero2);
            $slot->setQuantity(1);
            $slot->setDeck($deck);
            $deck->addSlot($slot);
        }

        if ($hero3) {
            $slot = new Deckslot();
            $slot->setCard($hero3);
            $slot->setQuantity(1);
            $slot->setDeck($deck);
            $deck->addSlot($slot);
        }

        $em->persist($deck);
        $em->flush();

        return $this->redirect($this->get('router')->generate('deck_edit', ['deck_id' => $deck->getId()]));
    }

    public function importAction() {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        return $this->render('AppBundle:Builder:directimport.html.twig', [
            'pagetitle' => "Import a deck",
        ], $response);
    }

    public function fileimportAction(Request $request) {
        $filetype = filter_var($request->get('type'), FILTER_SANITIZE_STRING);
        $uploadedFile = $request->files->get('upfile');

        if (!isset($uploadedFile)) {
            return new Response('No file');
        }

        $origname = $uploadedFile->getClientOriginalName();
        $origext = $uploadedFile->getClientOriginalExtension();
        $filename = $uploadedFile->getPathname();

        if (function_exists("finfo_open")) {
            // return mime type ala mimetype extension
            $finfo = finfo_open(FILEINFO_MIME);

            // check to see if the mime-type starts with 'text'
            $is_text = substr(finfo_file($finfo, $filename), 0, 4) == 'text' || substr(finfo_file($finfo, $filename), 0, 15) == "application/xml";
            if (!$is_text) {
                return new Response('Bad file');
            }
        }

        if ($filetype == "octgn" || ($filetype == "auto" && $origext == "o8d")) {
            $parse = $this->parseOctgnImport(file_get_contents($filename));
        } else {
            $parse = $this->parseTextImport(file_get_contents($filename));
        }

        $properties = [
            'name' => str_replace(".$origext", '', $origname),
            'content' => json_encode($parse['content']),
            'description' => $parse['description']
        ];

        return $this->forward('AppBundle:Builder:save', $properties);
    }

    public function parseTextImport($text) {
        $em = $this->getDoctrine()->getManager();

        $content = [];
        $text = str_replace(['“', '”', '’', '&rsquo;'], ['"', '"', '\'', '\''], $text);

        $lines = explode("\n", $text);
        $identity = null;

        foreach ($lines as $line) {
            $matches = [];
            $pack_name = null;
            $name = null;
            $quantity = 1;

            if (preg_match('/(x\d+|\d+x)/u', $line, $matches)) {
                $quantity = intval(str_replace('x', '', $matches[1]));
                $line = str_replace($matches[1], '', $line);
            }

            if (preg_match('/^\s*([\pLl\pLu\pN\-\.\'\!\: ]+)\(?([^\)]*)\)?/u', $line, $matches)) {
                $name = trim($matches[1]);

                if (isset($matches[2])) {
                    $pack_name = trim($matches[2]);
                }
            }

            $card = null;
            $pack = null;

            if ($pack_name) {
                $pack = $em->getRepository('AppBundle:Pack')->findOneBy(['name' => $pack_name]);

                if (!$pack) {
                    $pack = $em->getRepository('AppBundle:Pack')->findOneBy(['code' => $pack_name]);
                }
            }

            if ($pack) {
                $card = $em->getRepository('AppBundle:Card')->findOneBy([
                    'name' => $name,
                    'pack' => $pack
                ]);
            } else {
                $card = $em->getRepository('AppBundle:Card')->findOneBy([
                    'name' => $name,
                ]);
            }

            if ($card) {
                $content[$card->getCode()] = $quantity;
            }
        }

        return [
            "content" => $content,
            "description" => ""
        ];
    }

    public function parseOctgnImport($octgn) {
        $em = $this->getDoctrine()->getManager();

        $crawler = new Crawler();
        $crawler->addXmlContent($octgn);

        // read octgnid
        $cardcrawler = $crawler->filter('deck > section > card');
        $octgnids = [];
        foreach ($cardcrawler as $domElement) {
            $octgnids[$domElement->getAttribute('id')] = intval($domElement->getAttribute('qty'));
        }

        // read desc
        $desccrawler = $crawler->filter('deck > notes');
        $descriptions = [];
        foreach ($desccrawler as $domElement) {
            $descriptions[] = $domElement->nodeValue;
        }

        $content = [];
        foreach ($octgnids as $octgnid => $qty) {
            $card = $em->getRepository('AppBundle:Card')->findOneBy([
                'octgnid' => $octgnid
            ]);

            if ($card) {
                $content[$card->getCode()] = $qty;
            }
        }

        $description = implode("\n", $descriptions);

        return [
            "content" => $content,
            "description" => $description
        ];
    }

    public function textexportAction($deck_id) {
        $em = $this->getDoctrine()->getManager();

        $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);

        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck->getUser()->getId();

        if (!$deck->getUser()->getIsShareDecks() && !$is_owner) {
            return $this->render('AppBundle:Default:error.html.twig', [
                'pagetitle' => "Error",
                'error' => 'You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share your decks" on their account.'
            ]);
        }

        $content = $this->renderView('AppBundle:Export:plain.txt.twig', [
            "deck" => $deck->getTextExport()
        ]);

        $content = str_replace("\n", "\r\n", $content);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->get('texts')->slugify($deck->getName()) . '.txt'));

        $response->setContent($content);

        return $response;
    }

    public function octgnexportAction($deck_id) {
        $em = $this->getDoctrine()->getManager();

        $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);

        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck->getUser()->getId();
        if (!$deck->getUser()->getIsShareDecks() && !$is_owner) {
            return $this->render('AppBundle:Default:error.html.twig', [
                'pagetitle' => "Error",
                'error' => 'You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share your decks" on their account.'
            ]);
        }

        $content = $this->renderView('AppBundle:Export:octgn.xml.twig', [
            "deck" => $deck->getTextExport()
        ]);

        $response = new Response();

        $response->headers->set('Content-Type', 'application/octgn');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->get('texts')->slugify($deck->getName()) . '.o8d'));

        $response->setContent($content);

        return $response;
    }

    public function cloneAction($deck_id) {
        $em = $this->getDoctrine()->getManager();

        $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);

        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck->getUser()->getId();
        if (!$deck->getUser()->getIsShareDecks() && !$is_owner) {
            return $this->render('AppBundle:Default:error.html.twig', [
                'pagetitle' => "Error",
                'error' => 'You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share your decks" on their account.'
            ]);
        }

        $content = [];
        foreach ($deck->getSlots() as $slot) {
            $content[$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        return $this->forward('AppBundle:Builder:save', [
            'name' => $deck->getName() . ' (clone)',
            'content' => json_encode($content),
            'deck_id' => $deck->getParent() ? $deck->getParent()->getId() : null
        ]);
    }

    public function saveAction(Request $request) {
        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();
        if (count($user->getDecks()) > $user->getMaxNbDecks()) {
            return new Response('You have reached the maximum number of decks allowed. Delete some decks or increase your reputation.');
        }

        $id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $deck = null;
        $source_deck = null;
        if ($id) {
            $deck = $em->getRepository('AppBundle:Deck')->find($id);
            if (!$deck || $user->getId() != $deck->getUser()->getId()) {
                throw new UnauthorizedHttpException("You don't have access to this deck.");
            }
            $source_deck = $deck;
        }

        $cancel_edits = (boolean)filter_var($request->get('cancel_edits'), FILTER_SANITIZE_NUMBER_INT);
        if ($cancel_edits) {
            if ($deck) {
                $this->get('decks')->revertDeck($deck);
            }

            return $this->redirect($this->generateUrl('decks_list'));
        }

        $is_copy = (boolean)filter_var($request->get('copy'), FILTER_SANITIZE_NUMBER_INT);
        if ($is_copy || !$id) {
            $deck = new Deck();
        }

        $content = (array)json_decode($request->get('content'));
        if (!count($content)) {
            return new Response('Cannot import empty deck');
        }

        $name = filter_var($request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $decklist_id = filter_var($request->get('decklist_id'), FILTER_SANITIZE_NUMBER_INT);
        $description = trim($request->get('description'));
        $tags = filter_var($request->get('tags'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

        $this->get('decks')->saveDeck($this->getUser(), $deck, $decklist_id, $name, $description, $tags, $content, $source_deck ? $source_deck : null);
        $em->flush();

        return $this->redirect($this->generateUrl('decks_list'));
    }

    public function deleteAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $deck_id = filter_var($request->get('deck_id'), FILTER_SANITIZE_NUMBER_INT);
        $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);
        if (!$deck) {
            return $this->redirect($this->generateUrl('decks_list'));
        }

        if ($this->getUser()->getId() != $deck->getUser()->getId()) {
            throw new UnauthorizedHttpException("You don't have access to this deck.");
        }

        foreach ($deck->getChildren() as $decklist) {
            $decklist->setParent(null);
        }
        $em->remove($deck);
        $em->flush();

        $this->get('session')->getFlashBag()->set('notice', "Deck deleted.");

        return $this->redirect($this->generateUrl('decks_list'));
    }

    public function deleteListAction(Request $request) {
        $em = $this->getDoctrine()->getManager();

        $list_id = explode('-', $request->get('ids'));

        foreach ($list_id as $id) {
            /* @var $deck Deck */
            $deck = $em->getRepository('AppBundle:Deck')->find($id);
            if (!$deck) {
                continue;
            }

            if ($this->getUser()->getId() != $deck->getUser()->getId()) {
                continue;
            }

            foreach ($deck->getChildren() as $decklist) {
                $decklist->setParent(null);
            }
            $em->remove($deck);
        }
        $em->flush();

        $this->get('session')->getFlashBag()->set('notice', "Decks deleted.");

        return $this->redirect($this->generateUrl('decks_list'));
    }

    public function editAction($deck_id) {
        $deck = $this->getDoctrine()->getManager()->getRepository('AppBundle:Deck')->find($deck_id);

        if ($this->getUser()->getId() != $deck->getUser()->getId()) {
            return $this->render('AppBundle:Default:error.html.twig', [
                'pagetitle' => "Error",
                'error' => 'You are not allowed to view this deck.'
            ]);
        }

        return $this->render('AppBundle:Builder:deckedit.html.twig', [
            'pagetitle' => "Deckbuilder",
            'deck' => $deck,
        ]);
    }

    public function viewAction($deck_id) {
        $deck = $this->getDoctrine()->getManager()->getRepository('AppBundle:Deck')->find($deck_id);

        if (!$deck) {
            return $this->render('AppBundle:Default:error.html.twig', [
                'pagetitle' => "Error",
                'error' => "This deck doesn't exist."
            ]);
        }

        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck->getUser()->getId();
        if (!$deck->getUser()->getIsShareDecks() && !$is_owner) {
            return $this->render('AppBundle:Default:error.html.twig', [
                'pagetitle' => "Error",
                'error' => 'You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share your decks" on their account.'
            ]);
        }

        return $this->render('AppBundle:Builder:deckview.html.twig', [
            'pagetitle' => "Deckbuilder",
            'deck' => $deck,
            'deck_id' => $deck_id,
            'is_owner' => $is_owner,
        ]);
    }

    public function compareAction($deck1_id, $deck2_id, Request $request) {
        $em = $this->getDoctrine()->getManager();

        $deck1 = $em->getRepository('AppBundle:Deck')->find($deck1_id);
        $deck2 = $em->getRepository('AppBundle:Deck')->find($deck2_id);

        if (!$deck1 || !$deck2) {
            return $this->render('AppBundle:Default:error.html.twig', [
                    'pagetitle' => "Error",
                    'error' => 'This deck cannot be found.'
                ]);
        }

        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck1->getUser()->getId();
        if (!$deck1->getUser()->getIsShareDecks() && !$is_owner) {
            return $this->render('AppBundle:Default:error.html.twig', [
                    'pagetitle' => "Error",
                    'error' => 'You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share your decks" on their account.'
                ]);
        }

        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck2->getUser()->getId();
        if (!$deck2->getUser()->getIsShareDecks() && !$is_owner) {
            return $this->render('AppBundle:Default:error.html.twig', [
                    'pagetitle' => "Error",
                    'error' => 'You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share your decks" on their account.'
                ]);
        }

        $heroIntersection = $this->get('diff')->getSlotsDiff([$deck1->getSlots()->getHeroDeck(), $deck2->getSlots()->getHeroDeck()]);
        $drawIntersection = $this->get('diff')->getSlotsDiff([$deck1->getSlots()->getDrawDeck(), $deck2->getSlots()->getDrawDeck()]);

        return $this->render('AppBundle:Compare:deck_compare.html.twig', [
            'deck1' => $deck1,
            'deck2' => $deck2,
            'hero_deck' => $heroIntersection,
            'draw_deck' => $drawIntersection,
        ]);
    }

    public function listAction() {
        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();

        $decks = $this->get('decks')->getByUser($user, false);

        if (count($decks)) {
            $tags = [];
            foreach ($decks as &$deck) {
                $tags[] = $deck['tags'];

                $heroDeck = $em->getRepository('AppBundle:Deck')->find($deck['id'])->getSlots()->getHeroDeck();
                $heroes = [];
                foreach ($heroDeck as $hero) {
                    $heroes[] = $hero->getCard()->getCode();
                }

                $deck['heroes'] = $heroes;
            }

            $tags = array_unique($tags);

            return $this->render('AppBundle:Builder:decks.html.twig', [
                'pagetitle' => "My Decks",
                'pagedescription' => "Create custom decks with the help of a powerful deckbuilder.",
                'decks' => $decks,
                'tags' => $tags,
                'nbmax' => $user->getMaxNbDecks(),
                'nbdecks' => count($decks),
                'cannotcreate' => $user->getMaxNbDecks() <= count($decks),
            ]);
        } else {
            return $this->render('AppBundle:Builder:no-decks.html.twig', [
                'pagetitle' => "My Decks",
                'pagedescription' => "Create custom decks with the help of a powerful deckbuilder.",
                'nbmax' => $user->getMaxNbDecks(),
            ]);
        }
    }

    public function copyAction($decklist_id) {
        $em = $this->getDoctrine()->getManager();

        $decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);

        $content = [];
        foreach ($decklist->getSlots() as $slot) {
            $content[$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        return $this->forward('AppBundle:Builder:save', [
            'name' => $decklist->getName(),
            'content' => json_encode($content),
            'decklist_id' => $decklist_id
        ]);
    }

    public function downloadallAction() {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        $decks = $this->get('decks')->getByUser($user, false);

        $file = tempnam("tmp", "zip");
        $zip = new \ZipArchive();
        $res = $zip->open($file, \ZipArchive::OVERWRITE);

        if ($res === true) {
            foreach ($decks as $deck) {
                $content = [];
                foreach ($deck['cards'] as $slot) {
                    $card = $em->getRepository('AppBundle:Card')->findOneBy(['code' => $slot['card_code']]);
                    if (!$card) {
                        continue;
                    }

                    $cardname = $card->getName();
                    $packname = $card->getPack()->getName();

                    if ($packname == 'Core Set') {
                        $packname = 'Core';
                    }

                    $qty = $slot['qty'];
                    $content[] = "$cardname ($packname) x$qty";
                }
                $filename = str_replace('/', ' ', $deck['name']) . '.txt';
                $zip->addFromString($filename, implode("\r\n", $content));
            }
            $zip->close();
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Length', filesize($file));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->get('texts')->slugify('ringsdb') . '.zip'));

        $response->setContent(file_get_contents($file));
        unlink($file);

        return $response;
    }

    public function uploadallAction(Request $request) {
        $em = $this->getDoctrine()->getManager();

        // time-consuming task
        ini_set('max_execution_time', 300);

        $uploadedFile = $request->files->get('uparchive');
        if (!isset($uploadedFile)) {
            return new Response('No file');
        }

        $filename = $uploadedFile->getPathname();

        if (function_exists("finfo_open")) {
            // return mime type ala mimetype extension
            $finfo = finfo_open(FILEINFO_MIME);

            // check to see if the mime-type is 'zip'
            if (substr(finfo_file($finfo, $filename), 0, 15) !== 'application/zip') {
                return new Response('Bad file');
            }
        }

        $zip = new \ZipArchive;
        $res = $zip->open($filename);
        if ($res === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                $parse = $this->parseTextImport($zip->getFromIndex($i));

                $deck = new Deck();
                $em->persist($deck);
                $this->get('decks')->saveDeck($this->getUser(), $deck, null, $name, '', '', $parse['content']);
            }
        }
        $zip->close();

        $em->flush();

        $this->get('session')->getFlashBag()->set('notice', "Decks imported.");

        return $this->redirect($this->generateUrl('decks_list'));
    }

    public function autosaveAction(Request $request) {
        $user = $this->getUser();

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $deck_id = $request->get('deck_id');

        $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);
        if (!$deck) {
            throw new BadRequestHttpException("Cannot find deck " . $deck_id);
        }
        if ($user->getId() != $deck->getUser()->getId()) {
            throw new UnauthorizedHttpException("You don't have access to this deck.");
        }

        $diff = (array)json_decode($request->get('diff'));
        if (count($diff) != 2) {
            $this->get('logger')->error("cannot use diff", $diff);
            throw new BadRequestHttpException("Wrong content " . json_encode($diff));
        }

        if (count($diff[0]) || count($diff[1])) {
            $change = new Deckchange();
            $change->setDeck($deck);
            $change->setVariation(json_encode($diff));
            $change->setIsSaved(false);
            $em->persist($change);
            $em->flush();
        }

        return new Response($change->getDatecreation()->format('c'));
    }
}
