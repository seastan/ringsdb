<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Deck;
use AppBundle\Entity\Deckchange;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class BuilderController extends Controller {

    public function newAction() {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $deck \AppBundle\Entity\Deck */
        $deck = new Deck();
        $deck->setName('New Lord of the Rings LCG Deck');
        $deck->setDescriptionMd('');
        $deck->setLastPack(null);
        $deck->setProblem('too_few_heroes');
        $deck->setTags('');
        $deck->setUser($this->getUser());

        $em->persist($deck);
        $em->flush();

        return $this->redirect($this->get('router')->generate('deck_edit', ['deck_id' => $deck->getId()]));
    }

    public function editAction($deck_id) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $deck \AppBundle\Entity\Deck */
        $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);

        if (!$deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }

        if ($this->getUser()->getId() != $deck->getUser()->getId()) {
            throw new AccessDeniedHttpException("You are not allowed to view this deck.");
        }

        return $this->render('AppBundle:Builder:deckedit.html.twig', [
            'pagetitle' => "Deckbuilder",
            'deck' => $deck,
        ]);
    }

    public function viewAction($deck_id) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $deck \AppBundle\Entity\Deck */
        $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);

        if (!$deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }

        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck->getUser()->getId();

        if (!$deck->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
        }

        return $this->render('AppBundle:Builder:deckview.html.twig', [
            'pagetitle' => "Deckbuilder",
            'deck' => $deck,
            'deck_id' => $deck_id,
            'is_owner' => $is_owner,
        ]);
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
            throw new UnprocessableEntityHttpException("No file uploaded");
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
                throw new UnprocessableEntityHttpException("Bad file");
            }
        }

        if ($filetype == "octgn" || ($filetype == "auto" && $origext == "o8d")) {
            $parse = $this->parseOctgnImport(file_get_contents($filename));
        } else {
            $parse = $this->parseTextImport(file_get_contents($filename));
        }

        return $this->forward('AppBundle:Builder:save', [
            'name' => str_replace(".$origext", '', $origname),
            'content' => json_encode($parse['content']),
            'description' => $parse['description']
        ]);
    }

    public function parseTextImport($text) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $content = [
            'main' => [],
            'side' => [],
        ];
        $addToSideboard = false;

        $text = str_replace(['“', '”', '’', '&rsquo;'], ['"', '"', '\'', '\''], $text);

        $lines = explode("\n", $text);
        $identity = null;

        foreach ($lines as $line) {
            $matches = [];
            $pack_name = null;
            $name = null;
            $quantity = 1;

            if (trim($line) == 'Sideboard') {
                $addToSideboard = true;
                continue;
            }

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
                /* @var $pack \AppBundle\Entity\Pack */
                $pack = $em->getRepository('AppBundle:Pack')->findOneBy(['name' => $pack_name]);

                if (!$pack) {
                    $pack = $em->getRepository('AppBundle:Pack')->findOneBy(['code' => $pack_name]);
                }
            }

            if ($pack) {
                /* @var $pack \AppBundle\Entity\Card */
                $card = $em->getRepository('AppBundle:Card')->findOneBy([
                    'name' => $name,
                    'pack' => $pack
                ]);
            } else {
                /* @var $pack \AppBundle\Entity\Card */
                $card = $em->getRepository('AppBundle:Card')->findOneBy([
                    'name' => $name,
                ]);
            }

            if ($card) {
                if ($addToSideboard) {
                    $content['side'][$card->getCode()] = $quantity;
                } else {
                    $content['main'][$card->getCode()] = $quantity;
                }
            }
        }

        return [
            "content" => $content,
            "description" => ""
        ];
    }

    public function parseOctgnImport($octgn) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $crawler = new Crawler();
        $crawler->addXmlContent($octgn);

        // read octgnid
        $octgnids = [];
        $sideoctgnids = [];

        $cardcrawler = $crawler->filter('deck > section[name!="Sideboard"] > card');
        foreach ($cardcrawler as $domElement) {
            $octgnids[$domElement->getAttribute('id')] = intval($domElement->getAttribute('qty'));
        }

        $cardcrawler = $crawler->filter('deck > section[name="Sideboard"] > card');
        foreach ($cardcrawler as $domElement) {
            $sideoctgnids[$domElement->getAttribute('id')] = intval($domElement->getAttribute('qty'));
        }

        // read desc
        $desccrawler = $crawler->filter('deck > notes');
        $descriptions = [];
        foreach ($desccrawler as $domElement) {
            $descriptions[] = $domElement->nodeValue;
        }

        $content = [];
        foreach ($octgnids as $octgnid => $qty) {
            /* @var $pack \AppBundle\Entity\Card */
            $card = $em->getRepository('AppBundle:Card')->findOneBy(['octgnid' => $octgnid]);

            if ($card) {
                $content[$card->getCode()] = $qty;
            }
        }

        $sidecontent = [];
        foreach ($sideoctgnids as $octgnid => $qty) {
            /* @var $pack \AppBundle\Entity\Card */
            $card = $em->getRepository('AppBundle:Card')->findOneBy(['octgnid' => $octgnid]);

            if ($card) {
                $sidecontent[$card->getCode()] = $qty;
            }
        }

        $description = implode("\n", $descriptions);

        return [
            "content" => [
                'main' => $content,
                'side' => $sidecontent,
            ],
            "description" => $description
        ];
    }

    public function textexportAction($deck_id) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $deck \AppBundle\Entity\Deck */
        $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);

        if (!$deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }

        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck->getUser()->getId();

        if (!$deck->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
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
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $deck \AppBundle\Entity\Deck */
        $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);

        if (!$deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }

        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck->getUser()->getId();

        if (!$deck->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
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
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $deck \AppBundle\Entity\Deck */
        $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);

        if (!$deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }

        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck->getUser()->getId();

        if (!$deck->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
        }

        $content = [
            'main' => [],
            'side' => []
        ];

        foreach ($deck->getSlots() as $slot) {
            $content['main'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        foreach ($deck->getSideslots() as $slot) {
            $content['side'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        return $this->forward('AppBundle:Builder:save', [
            'name' => $deck->getName() . ' (clone)',
            'content' => json_encode($content),
            'decklist_id' => $deck->getParent() ? $deck->getParent()->getId() : null
        ]);
    }

    public function saveAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        if (count($user->getDecks()) > $user->getMaxNbDecks()) {
            throw new UnprocessableEntityHttpException('You have reached the maximum number of decks allowed. Delete some decks or increase your reputation.');
        }

        $id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $deck = null;
        $source_deck = null;

        if ($id) {
            /* @var $deck \AppBundle\Entity\Deck */
            $deck = $em->getRepository('AppBundle:Deck')->find($id);

            if (!$deck || $user->getId() != $deck->getUser()->getId()) {
                throw new AccessDeniedHttpException("You don't have access to this deck.");
            }

            $source_deck = $deck;
        }

        $cancel_edits = (boolean) filter_var($request->get('cancel_edits'), FILTER_SANITIZE_NUMBER_INT);
        if ($cancel_edits) {
            if ($deck) {
                $this->get('decks')->revertDeck($deck);
            }

            return $this->redirect($this->generateUrl('decks_list'));
        }

        $is_copy = (boolean) filter_var($request->get('copy'), FILTER_SANITIZE_NUMBER_INT);
        if ($is_copy || !$id) {
            /* @var $deck \AppBundle\Entity\Deck */
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
        $description = trim($request->get('description'));
        $tags = filter_var($request->get('tags'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

        $this->get('decks')->saveDeck($this->getUser(), $deck, $decklist_id, $name, $description, $tags, $content, $source_deck ?: null);
        $em->flush();

        return $this->redirect($this->generateUrl('decks_list'));
    }

    public function deleteAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $deck_id = filter_var($request->get('deck_id'), FILTER_SANITIZE_NUMBER_INT);

        /* @var $deck \AppBundle\Entity\Deck */
        $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);
        if (!$deck) {
            return $this->redirect($this->generateUrl('decks_list'));
        }

        if ($this->getUser()->getId() != $deck->getUser()->getId()) {
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

            $this->get('session')->getFlashBag()->set('notice', "Deck deleted.");
        }

        return $this->redirect($this->generateUrl('decks_list'));
    }

    public function deleteListAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $list_id = explode('-', $request->get('ids'));

        foreach ($list_id as $id) {
            /* @var $deck \AppBundle\Entity\Deck */
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

    public function compareAction($deck1_id, $deck2_id) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $deck1 \AppBundle\Entity\Deck */
        $deck1 = $em->getRepository('AppBundle:Deck')->find($deck1_id);

        /* @var $deck2 \AppBundle\Entity\Deck */
        $deck2 = $em->getRepository('AppBundle:Deck')->find($deck2_id);

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

        $diff = $this->get('diff');
        $heroIntersection = $diff->getSlotsDiff([$deck1->getSlots()->getHeroDeck(), $deck2->getSlots()->getHeroDeck()]);
        $drawIntersection = $diff->getSlotsDiff([$deck1->getSlots()->getDrawDeck(), $deck2->getSlots()->getDrawDeck()]);
        $sideIntersection = $diff->getSlotsDiff([$deck1->getSideSlots(), $deck2->getSideSlots()]);

        return $this->render('AppBundle:Compare:deck_compare.html.twig', [
            'deck1' => $deck1,
            'deck2' => $deck2,
            'hero_deck' => $heroIntersection,
            'draw_deck' => $drawIntersection,
            'sideboard' => $sideIntersection,
        ]);
    }

    public function listAction() {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();

        /* @var $decks \AppBundle\Entity\Deck[] */
        $decks = $this->get('decks')->getByUser($user, false);

        if (count($decks)) {
            $tags = [];
            foreach ($decks as &$deck) {
                $tags[] = $deck['tags'];

                /* @var $heroDeck \AppBundle\Entity\Deckslot[] */
                $heroDeck = $em->getRepository('AppBundle:Deck')->find($deck['id'])->getSlots()->getHeroDeck();
                $heroes = [];

                foreach ($heroDeck as $hero) {
                    $heroes[] = $hero->getCard();
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
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $decklist \AppBundle\Entity\Decklist */
        $decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);

        if (!$decklist) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }

        $content = [
            'main' => [],
            'side' => []
        ];

        foreach ($decklist->getSlots() as $slot) {
            $content['main'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        foreach ($decklist->getSideslots() as $slot) {
            $content['side'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        return $this->forward('AppBundle:Builder:save', [
            'name' => $decklist->getName(),
            'content' => json_encode($content),
            'decklist_id' => $decklist_id
        ]);
    }

    public function octgnexportListAction(Request $request) {
        $list_id = $request->get('ids');

        return $this->downloadFromSelection($list_id, true);
    }

    public function textexportListAction(Request $request) {
        $list_id = $request->get('ids');

        return $this->downloadFromSelection($list_id, false);
    }

    public function downloadFromSelection($list_id, $octgn) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $file = tempnam("tmp", "zip");
        $zip = new \ZipArchive();
        $res = $zip->open($file, \ZipArchive::OVERWRITE);

        if ($res === true) {
            foreach ($list_id as $id) {
                /* @var $deck \AppBundle\Entity\Deck */
                $deck = $em->getRepository('AppBundle:Deck')->find($id);

                if (!$deck) {
                    continue;
                }

                if ($this->getUser()->getId() != $deck->getUser()->getId()) {
                    continue;
                }

                if ($octgn) {
                    $extension = 'o8d';
                    $content = $this->renderView('AppBundle:Export:octgn.xml.twig', [
                        "deck" => $deck->getTextExport()
                    ]);
                } else {
                    $extension = 'txt';
                    $content = $this->renderView('AppBundle:Export:plain.txt.twig', [
                        "deck" => $deck->getTextExport()
                    ]);
                }

                $filename = $this->get('texts')->slugify($deck->getName()) . ' ' . $deck->getVersion() . '.' . $extension;

                $zip->addFromString($filename, $content);
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
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        // time-consuming task
        ini_set('max_execution_time', 300);

        $uploadedFile = $request->files->get('uparchive');
        if (!isset($uploadedFile)) {
            throw new UnprocessableEntityHttpException("No file uploaded");
        }

        $filename = $uploadedFile->getPathname();

        if (function_exists("finfo_open")) {
            // return mime type ala mimetype extension
            $finfo = finfo_open(FILEINFO_MIME);

            // check to see if the mime-type is 'zip'
            if (substr(finfo_file($finfo, $filename), 0, 15) !== 'application/zip') {
                throw new UnprocessableEntityHttpException("Bad file");
            }
        }

        $zip = new \ZipArchive;
        $res = $zip->open($filename);
        if ($res === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);

                if (pathinfo($name, PATHINFO_EXTENSION) == 'o8d') {
                    $parse = $this->parseOctgnImport($zip->getFromIndex($i));
                } else {
                    $parse = $this->parseTextImport($zip->getFromIndex($i));
                }

                $deckname = pathinfo($name, PATHINFO_FILENAME);

                if (isset($parse['content']) && $parse['content']) {
                    /* @var $deck \AppBundle\Entity\Deck */
                    $deck = new Deck();
                    $em->persist($deck);
                    $this->get('decks')->saveDeck($this->getUser(), $deck, null, $deckname, '', '', $parse['content'], null);
                }
            }
        }
        $zip->close();

        $em->flush();

        $this->get('session')->getFlashBag()->set('notice', "Decks imported.");

        return $this->redirect($this->generateUrl('decks_list'));
    }

    public function autosaveAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();

        $deck_id = $request->get('deck_id');

        /* @var $deck \AppBundle\Entity\Deck */
        $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);

        if (!$deck) {
            throw new UnprocessableEntityHttpException("Cannot find deck " . $deck_id);
        }

        if ($user->getId() != $deck->getUser()->getId()) {
            throw new AccessDeniedHttpException("You don't have access to this deck.");
        }

        $diff = (array) json_decode($request->get('diff'));
        if (count($diff) != 4 && count($diff) != 2) {
            $this->get('logger')->error("cannot use diff", $diff);
            throw new UnprocessableEntityHttpException("Wrong content " . json_encode($diff));
        }

        if (count($diff[0]) || count($diff[1]) || count($diff[2]) || count($diff[3])) {
            /* @var $change \AppBundle\Entity\Deckchange */
            $change = new Deckchange();
            $change->setDeck($deck);
            $change->setVariation(json_encode($diff));
            $change->setIsSaved(false);
            $em->persist($change);
            $em->flush();

            return new Response($change->getDatecreation()->format('c'));
        }

        return new Response();
    }
}