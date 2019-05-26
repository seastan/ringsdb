<?php
namespace AppBundle\Controller;

use AppBundle\Entity\Fellowship;
use AppBundle\Entity\FellowshipComment;
use AppBundle\Entity\FellowshipDeck;
use AppBundle\Entity\FellowshipDecklist;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FellowshipController extends Controller {

    public function mylistAction() {
        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();

        /* @var $fellowships \AppBundle\Entity\Fellowship[] */
        $fellowships = $user->getFellowships();

        if (count($fellowships)) {
            return $this->render('AppBundle:Fellowship:my-fellowships.html.twig', [
                'pagetitle' => "My Fellowships",
                'pagedescription' => "Create fellowships, a link between decks that work well together or are meant to be played together.",
                'fellowships' => $fellowships,
            ]);
        } else {
            return $this->render('AppBundle:Fellowship:no-fellowships.html.twig', [
                'pagetitle' => "My Fellowships",
                'pagedescription' => "Create fellowships, a link between decks that work well together or are meant to be played together.",
            ]);
        }
    }

    public function listAction($type, $page = 1, Request $request) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        /**
         * @var $fellowship_manager \AppBundle\Model\FellowshipManager
         */
        $fellowship_manager = $this->get('fellowship_manager');
        $fellowship_manager->setLimit(30);
        $fellowship_manager->setPage($page);

        $header = '';

        switch ($type) {
            case 'find':
                $pagetitle = "Fellowship search results";
                $header = $this->searchForm($request);
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
                $pagetitle = "Favorite Fellowships";
                break;

            case 'mine':
                $response->setPrivate();
                $user = $this->getUser();
                if ($user) {
                    $paginator = $fellowship_manager->findFellowshipsByAuthor($user);
                } else {
                    $paginator = $fellowship_manager->getEmptyList();
                }
                $pagetitle = "My Public Fellowships";
                break;

            case 'recent':
                $paginator = $fellowship_manager->findFellowshipsByAge(false);
                $pagetitle = "Recent Fellowships";
                break;

            case 'halloffame':
                $paginator = $fellowship_manager->findFellowshipsInHallOfFame();
                $pagetitle = "Hall of Fame";
                break;

            case 'hottopics':
                $paginator = $fellowship_manager->findFellowshipsInHotTopic();
                $pagetitle = "Hot Topics";
                break;

            case 'popular':
            default:
                $paginator = $fellowship_manager->findFellowshipsByPopularity();
                $pagetitle = "Popular Fellowships";
                break;
        }

        return $this->render('AppBundle:Fellowship:public-fellowships.html.twig', [
            'pagetitle' => $pagetitle,
            'pagedescription' => "Browse the collection of thousands of premade decks.",
            'fellowships' => $paginator,
            'url' => $request->getRequestUri(),
            'header' => $header,
            'type' => $type,
            'pages' => $fellowship_manager->getClosePages(),
            'prevurl' => $fellowship_manager->getPreviousUrl(),
            'nexturl' => $fellowship_manager->getNextUrl(),
        ], $response);
    }

    public function newAction($deck1_id, $deck2_id, $deck3_id, $deck4_id) {
        $response = new Response();

        $decks = [];
        $deck_ids = func_get_args();

        for ($i = 0; $i < 4; $i++) {
            $decks[$i] = null;

            if ($deck_ids[$i]) {
                /* @var $decks \AppBundle\Entity\Deck[] */
                $decks[$i] = $this->getDoctrine()->getManager()->getRepository('AppBundle:Deck')->find($deck_ids[$i]);

                if ($decks[$i]) {
                    /* @var $user \AppBundle\Entity\User */
                    $user = $decks[$i]->getUser();

                    if (!$user->getIsShareDecks() && $user->getId() != $this->getUser()->getId()) {
                        $decks[$i] = null;
                    }
                }
            }
        }

        return $this->render('AppBundle:Fellowship:edit.html.twig', [
            'pagetitle' => "Create a Fellowship",
            'deck1' => $decks[0],
            'deck2' => $decks[1],
            'deck3' => $decks[2],
            'deck4' => $decks[3],
            'is_public' => false
        ], $response);
    }

    public function editAction($fellowship_id) {
        $response = new Response();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();

        /* @var $fellowship \AppBundle\Entity\Fellowship */
        $fellowship = $this->getDoctrine()->getRepository('AppBundle:Fellowship')->find($fellowship_id);

        if (!$fellowship) {
            throw new NotFoundHttpException("This fellowship does not exists.");
        }

        if ($user->getId() !== $fellowship->getUser()->getId()) {
            throw new AccessDeniedHttpException("Access denied to this object.");
        }

        $data = [
            'pagetitle' => "Edit Fellowship",
            'deck1' => null,
            'deck2' => null,
            'deck3' => null,
            'deck4' => null,
            'fellowship' => $fellowship,
            'is_public' => $fellowship->getIsPublic()
        ];

        /* @var $fellowship_decks \AppBundle\Entity\FellowshipDeck[] */
        $fellowship_decks = $fellowship->getDecks();
        foreach ($fellowship_decks as $fellowship_deck) {
            $data['deck' . $fellowship_deck->getDeckNumber()] = $fellowship_deck->getDeck();
        }

        /* @var $fellowship_decks \AppBundle\Entity\FellowshipDecklist[] */
        $fellowship_decklists = $fellowship->getDecklists();
        foreach ($fellowship_decklists as $fellowship_decklist) {
            $data['deck' . $fellowship_decklist->getDeckNumber()] = $fellowship_decklist->getDecklist();
        }

        return $this->render('AppBundle:Fellowship:edit.html.twig', $data, $response);
    }

    public function viewAction($fellowship_id) {
        /* @var $fellowship \AppBundle\Entity\Fellowship */
        $fellowship = $this->getDoctrine()->getManager()->getRepository('AppBundle:Fellowship')->find($fellowship_id);

        if (!$fellowship) {
            throw new NotFoundHttpException("This fellowship does not exists.");
        }

        $is_owner = $this->getUser() && $this->getUser()->getId() == $fellowship->getUser()->getId();
        $is_public = $fellowship->getIsPublic();

        if (!$fellowship->getUser()->getIsShareDecks() && !$is_owner && !$is_public) {
            throw new AccessDeniedHttpException('You are not allowed to view this fellowship. To get access, you can ask it\'s owner to enable "Share my decks" on their account.');
        }

        if ($is_public) {
            $commenters = array_map(function($comment) {
                /* @var $comment \AppBundle\Entity\FellowshipComment */
                return $comment->getUser()->getUsername();
            }, $fellowship->getComments()->getValues());

            $commenters[] = $fellowship->getUser()->getUsername();
        } else {
            $commenters = [];
        }

        $data = [
            'pagetitle' => "Fellowship",
            'deck1' => null,
            'deck2' => null,
            'deck3' => null,
            'deck4' => null,
            'fellowship' => $fellowship,
            'is_owner' => $is_owner,
            'is_public' => $is_public,
            'commenters' => $commenters
        ];

        /* @var $fellowship_decks \AppBundle\Entity\FellowshipDeck[] */
        $fellowship_decks = $fellowship->getDecks();
        foreach ($fellowship_decks as $fellowship_deck) {
            $data['deck' . $fellowship_deck->getDeckNumber()] = $fellowship_deck->getDeck();
        }

        /* @var $fellowship_decks \AppBundle\Entity\FellowshipDecklist[] */
        $fellowship_decklists = $fellowship->getDecklists();
        foreach ($fellowship_decklists as $fellowship_decklist) {
            $data['deck' . $fellowship_decklist->getDeckNumber()] = $fellowship_decklist->getDecklist();
        }

        return $this->render('AppBundle:Fellowship:view.html.twig', $data);
    }

    public function saveAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();

        $fellowship_id = intval(filter_var($request->request->get('fellowship_id'), FILTER_SANITIZE_NUMBER_INT));

        if ($fellowship_id) {
            /* @var $fellowship \AppBundle\Entity\Fellowship */
            $fellowship = $em->getRepository('AppBundle:Fellowship')->find($fellowship_id);

            if (!$fellowship) {
                throw new NotFoundHttpException("This fellowship does not exists.");
            }

            if ($user->getId() !== $fellowship->getUser()->getId()) {
                throw new AccessDeniedHttpException("Access denied to this object.");
            }
        } else {
            $fellowship = new Fellowship();
            $fellowship->setIsPublic(false);
            $fellowship->setNbVotes(0);
            $fellowship->setNbComments(0);
            $fellowship->setNbFavorites(0);
            $fellowship->setNbDecks(0);
        }

        $name = trim(filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $name = substr($name, 0, 60);
        if (empty($name)) {
            $name = "Untitled Fellowship";
        }

        $auto_publish = boolval(filter_var($request->request->get('auto_publish'), FILTER_SANITIZE_NUMBER_INT));


        $descriptionMd = trim($request->request->get('descriptionMd'));
        $descriptionHtml = $this->get('texts')->markdown($descriptionMd);

        $fellowship->setUser($user);
        $fellowship->setName($name);
        $fellowship->setNameCanonical($this->get('texts')->slugify($name));
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
            for ($i = 1; $i <= 4; $i++) {
                $deck_id = intval(filter_var($request->request->get("deck".$i."_id"), FILTER_SANITIZE_NUMBER_INT));
                $is_decklist = filter_var($request->get("deck".$i."_is_decklist"), FILTER_SANITIZE_STRING) == 'true';

                if ($deck_id) {
                    if (!$is_decklist) {
                        /* @var $deck \AppBundle\Entity\Deck */
                        $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);

                        if (!$deck) {
                            throw new NotFoundHttpException("One of the selected decks does not exists.");
                        }

                        $deck_user = $deck->getUser();
                        $is_owner = $user->getId() == $deck_user->getId();
                        if (!$is_owner && !$deck_user->getIsShareDecks()) {
                            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
                        }

                        if (!$is_owner) {
                            $deck = $this->get('decks')->cloneDeck($deck, $user);
                        }

                        $fellowship_deck = new FellowshipDeck();
                        $fellowship_deck->setDeck($deck);
                        $fellowship_deck->setDeckNumber($i - $skip);
                        $fellowship_deck->setFellowship($fellowship);

                        $fellowship->addDeck($fellowship_deck);
                    } else {
                        /* @var $decklist \AppBundle\Entity\Decklist */
                        $decklist = $em->getRepository('AppBundle:Decklist')->find($deck_id);

                        if (!$decklist) {
                            throw new NotFoundHttpException("One of the selected decks does not exists.");
                        }

                        $fellowship_decklist = new FellowshipDecklist();
                        $fellowship_decklist->setDecklist($decklist);
                        $fellowship_decklist->setDeckNumber($i - $skip);
                        $fellowship_decklist->setFellowship($fellowship);

                        $fellowship->addDecklist($fellowship_decklist);
                    }
                    $nb_decks++;
                } else {
                    $skip++;
                }
            }
            if ($nb_decks == 0) {
                throw new UnprocessableEntityHttpException("You can't save an empty fellowship.");
            }

            $fellowship->setNbDecks($nb_decks);
        }

        if ($auto_publish && empty($fellowship->getDecks())) {
            $fellowship->setIsPublic(true);
            $fellowship->setDatePublish(new \DateTime());
        }


        $em->persist($fellowship);
        $em->flush();

        return $this->redirect($this->generateUrl('fellowship_view', [
            'fellowship_id' => $fellowship->getId()
        ]));
    }

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

        if ($fellowship->getIsPublic()) {
            $this->get('session')->getFlashBag()->set('error', "This fellowship is already published.");

            return $this->redirect($this->generateUrl('fellowship_view', [ 'fellowship_id' => $fellowship->getId() ]));
        }

        $data = [
            'pagetitle' => "Publish Fellowship",
            'deck1' => null,
            'deck2' => null,
            'deck3' => null,
            'deck4' => null,
            'deck1_duplicates' => [],
            'deck2_duplicates' => [],
            'deck3_duplicates' => [],
            'deck4_duplicates' => [],
            'deck1_match' => null,
            'deck2_match' => null,
            'deck3_match' => null,
            'deck4_match' => null,
            'deck1_signature' => null,
            'deck2_signature' => null,
            'deck3_signature' => null,
            'deck4_signature' => null,
            'fellowship' => $fellowship,
        ];

        /* @var $fellowship_decks \AppBundle\Entity\FellowshipDeck[] */
        $fellowship_decks = $fellowship->getDecks();
        foreach ($fellowship_decks as &$fellowship_deck) {
            $deck = $fellowship_deck->getDeck();

            if ($deck->getMajorVersion() > 0 && $deck->getMinorVersion() == 1) {
                // There may be a perfect copy published
                /* @var $pub \AppBundle\Entity\Decklist */
                $pub = $deck->getChildren()->first();
                if ($pub) {
                    $data['deck' . $fellowship_deck->getDeckNumber() . '_match'] = $pub->getId();
                }
            }

            // Finding duplicates
            $content = [
                'main' => $deck->getSlots()->getContent(),
                'side' => $deck->getSideslots()->getContent(),
            ];

            $this_content = json_encode($content);
            $this_signature = md5($this_content);

            $old_decklists = $this->getDoctrine()->getRepository('AppBundle:Decklist')->findBy([ 'signature' => $this_signature ]);

            foreach ($old_decklists as $decklist) {
                /* @var $decklist \AppBundle\Entity\Decklist */
                if ($decklist->getParent()->getId() == $deck->getId()) {
                    continue;
                }

                $deck_content = [
                    'main' => $decklist->getSlots()->getContent(),
                    'side' => $decklist->getSideslots()->getContent(),
                ];

                if (json_encode($deck_content) == $this_content) {
                    $data['deck' . $fellowship_deck->getDeckNumber() . '_duplicates'][] = $decklist;
                }
            }

            $data['deck' . $fellowship_deck->getDeckNumber()] = $fellowship_deck->getDeck();
            $data['deck' . $fellowship_deck->getDeckNumber() . '_signature'] = $this_signature;
        }

        /* @var $fellowship_decks \AppBundle\Entity\FellowshipDecklist[] */
        $fellowship_decklists = $fellowship->getDecklists();
        foreach ($fellowship_decklists as &$fellowship_decklist) {
            $data['deck' . $fellowship_decklist->getDeckNumber()] = $fellowship_decklist->getDecklist();
        }

        return $this->render('AppBundle:Fellowship:publish.html.twig', $data);
    }


    public function publishAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException("You must be logged in for this operation.");
        }

        $fellowship_id = intval(filter_var($request->request->get('fellowship_id'), FILTER_SANITIZE_NUMBER_INT));

        /* @var $fellowship \AppBundle\Entity\Fellowship */
        $fellowship = $em->getRepository('AppBundle:Fellowship')->find($fellowship_id);
        if (!$fellowship || $fellowship->getUser()->getId() != $user->getId()) {
            throw new AccessDeniedHttpException("You don't have access to this fellowship.");
        }

        if ($fellowship->getIsPublic()) {
            $this->get('session')->getFlashBag()->set('error', "This fellowship is already published.");

            return $this->redirect($this->generateUrl('fellowship_view', [ 'fellowship_id' => $fellowship->getId() ]));
        }

        $name = trim(filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $name = substr($name, 0, 60);
        if (empty($name)) {
            $name = "Untitled Fellowship";
        }

        $descriptionMd = trim($request->request->get('descriptionMd'));
        $descriptionHtml = $this->get('texts')->markdown($descriptionMd);

        $fellowship->setName($name);
        $fellowship->setNameCanonical($this->get('texts')->slugify($name));
        $fellowship->setDescriptionMd($descriptionMd);
        $fellowship->setDescriptionHtml($descriptionHtml);
        $fellowship->setDateUpdate(new \DateTime());

        $fellowship->setIsPublic(true);
        $fellowship->setDatePublish(new \DateTime());

        foreach ($fellowship->getDecks() as &$fellowship_deck) {
            /* @var $fellowship_deck \AppBundle\Entity\FellowshipDeck */
            $new_id = intval(filter_var($request->request->get('deck_selection_' . $fellowship_deck->getDeckNumber()), FILTER_SANITIZE_NUMBER_INT));

            if ($new_id) {
                $decklist = $em->getRepository('AppBundle:Decklist')->find($new_id);

                if (!$decklist) {
                    throw new NotFoundHttpException("One of the selected decks does not exists.");
                }
            } else {
                $deck = $fellowship_deck->getDeck();
                $decklist = $this->get('decklist_factory')->createDecklistFromDeck($deck, $deck->getName(), $deck->getDescriptionMd());
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
        $problem = $this->get('fellowship_validation_helper')->findProblem($fellowship);
        if ($problem) {
            $this->get('session')->getFlashBag()->set('error', "This fellowship cannot be published because it is invalid.");

            return $this->redirect($this->generateUrl('fellowship_view', [ 'fellowship_id' => $fellowship->getId() ]));
        }

        $em->persist($fellowship);
        $em->flush();

        return $this->redirect($this->generateUrl('fellowship_view', [
            'fellowship_id' => $fellowship->getId(),
            'fellowship_name' => $fellowship->getNameCanonical()
        ]));
    }

    public function deleteAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException("You must be logged in for this operation.");
        }

        $fellowship_id = filter_var($request->get('fellowship_id'), FILTER_SANITIZE_NUMBER_INT);

        /* @var $fellowship \AppBundle\Entity\Fellowship */
        $fellowship = $em->getRepository('AppBundle:Fellowship')->find($fellowship_id);
        if (!$fellowship) {
            return $this->redirect($this->generateUrl('myfellowships_list'));
        }

        if (!$fellowship || $fellowship->getUser()->getId() != $user->getId()) {
            throw new AccessDeniedHttpException("You don't have access to this fellowship.");
        }

        if ($fellowship->getNbVotes() || $fellowship->getNbfavorites() || $fellowship->getNbcomments()) {
            $this->get('session')->getFlashBag()->set('error', "You can't delete a published fellowship.");
        } else {
            /* @var $decks \AppBundle\Entity\FellowshipDeck[] */
            $decks = $fellowship->getDecks();
            foreach ($decks as $deck) {
                $em->remove($deck);
            }

            /* @var $decks \AppBundle\Entity\FellowshipDecklist[] */
            $decklists = $fellowship->getDecklists();
            foreach ($decklists as $decklist) {
                $em->remove($decklist);
            }

            $em->remove($fellowship);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('myfellowships_list'));
    }

    public function deleteListAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException("You must be logged in for this operation.");
        }

        $list_id = explode('-', $request->get('ids'));
        $message = null;

        foreach ($list_id as $id) {
            /* @var $fellowship \AppBundle\Entity\Fellowship */
            $fellowship = $em->getRepository('AppBundle:Fellowship')->find($id);
            if (!$fellowship) {
                continue;
            }

            if ($user->getId() != $fellowship->getUser()->getId()) {
                continue;
            }

            if ($fellowship->getNbVotes() || $fellowship->getNbfavorites() || $fellowship->getNbcomments()) {
                $message = "You can't delete a published fellowship. Unpublished selected fellowships were deleted.";
            } else {
                /* @var $decks \AppBundle\Entity\FellowshipDeck[] */
                $decks = $fellowship->getDecks();
                foreach ($decks as $deck) {
                    $em->remove($deck);
                }

                /* @var $decks \AppBundle\Entity\FellowshipDecklist[] */
                $decklists = $fellowship->getDecklists();
                foreach ($decklists as $decklist) {
                    $em->remove($decklist);
                }

                $em->remove($fellowship);
            }
        }
        $em->flush();

        $this->get('session')->getFlashBag()->set('notice', $message ?: "Fellowships deleted.");

        return $this->redirect($this->generateUrl('myfellowships_list'));
    }

    private function searchForm(Request $request) {
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
            $packs = $dbh->executeQuery("SELECT id FROM pack")->fetchAll(\PDO::FETCH_COLUMN);
        }

        $categories = [];
        $on = 0;
        $off = 0;
        $categories[] = ["label" => "Core / Deluxe", "packs" => []];
        $list_cycles = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findBy([], ["position" => "ASC"]);
        foreach ($list_cycles as $cycle) {
            /* @var $cycle \AppBundle\Entity\Cycle */
            $size = count($cycle->getPacks());
            if ($cycle->getPosition() == 0 || $size == 0) {
                continue;
            }

            $first_pack = $cycle->getPacks()[0];
            if ($size === 1 && $first_pack->getName() == $cycle->getName()) {
                $checked = count($packs) ? in_array($first_pack->getId(), $packs) : true;
                if ($checked) {
                    $on++;
                } else {
                    $off++;
                }
                $categories[0]["packs"][] = ["id" => $first_pack->getId(), "label" => $first_pack->getName(), "checked" => $checked, "future" => $first_pack->getDateRelease() === null];
            } else {
                $category = ["label" => $cycle->getName(), "packs" => []];
                foreach ($cycle->getPacks() as $pack) {
                    $checked = count($packs) ? in_array($pack->getId(), $packs) : true;
                    if ($checked) {
                        $on++;
                    } else {
                        $off++;
                    }
                    $category['packs'][] = ["id" => $pack->getId(), "label" => $pack->getName(), "checked" => $checked, "future" => $pack->getDateRelease() === null];
                }
                $categories[] = $category;
            }
        }

        $params = [
            'allowed' => $categories,
            'on' => $on,
            'off' => $off,
            'author' => $author_name,
            'name' => $fellowship_name,
            'numcores' => $numcores,
            'numplaysets' => $numplaysets
        ];
        $params['sort_' . $sort] = ' selected="selected"';
        $params['nb_decks_selected'] = $nb_decks;

        if (!empty($cards_code) && is_array($cards_code)) {
            $cards = $dbh->executeQuery("SELECT
    				c.name,
    				c.code,
                    s.code AS sphere_code,
                    p.name AS pack_name
    				FROM card c
                    INNER JOIN sphere s ON s.id = c.sphere_id
                    INNER JOIN pack p ON p.id = c.pack_id
                    WHERE c.code IN (?)
    				ORDER BY c.code DESC", [$cards_code], [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY])->fetchAll();

            $params['cards'] = '';

            foreach ($cards as $card) {
                $params['cards'] .= $this->renderView('AppBundle:Search:card.html.twig', $card);
            }
        }

        return $this->renderView('AppBundle:Fellowship:form.html.twig', $params);
    }

    public function searchAction(Request $request) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        $dbh = $this->getDoctrine()->getConnection();
        $spheres = $dbh->executeQuery("SELECT s.name, s.code FROM sphere s ORDER BY s.name ASC")->fetchAll();

        $owned_packs = '';
        if ($this->getUser()) {
            $owned_packs = $this->getUser()->getOwnedPacks();
        }

        if ($owned_packs) {
            $packs = explode(",", $owned_packs);
        } else {
            $packs = $dbh->executeQuery("SELECT id FROM pack WHERE date_release IS NOT NULL")->fetchAll(\PDO::FETCH_COLUMN);
        }

        $categories = [];
        $on = 0;
        $off = 0;
        $categories[] = ["label" => "Core / Deluxe", "packs" => []];
        $list_cycles = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findBy([], ["position" => "ASC"]);

        foreach ($list_cycles as $cycle) {
            /* @var $cycle \AppBundle\Entity\Cycle */
            $size = count($cycle->getPacks());
            if ($cycle->getPosition() == 0 || $size == 0) {
                continue;
            }

            $first_pack = $cycle->getPacks()[0];
            if ($size === 1 && $first_pack->getName() == $cycle->getName()) {
                $checked = count($packs) ? in_array($first_pack->getId(), $packs) : true;

                if ($checked) {
                    $on++;
                } else {
                    $off++;
                }

                $categories[0]["packs"][] = [
                    "id" => $first_pack->getId(),
                    "label" => $first_pack->getName(),
                    "checked" => $checked,
                    "future" => $first_pack->getDateRelease() === null
                ];
            } else {
                $category = ["label" => $cycle->getName(), "packs" => []];
                foreach ($cycle->getPacks() as $pack) {
                    $checked = count($packs) ? in_array($pack->getId(), $packs) : true;

                    if ($checked) {
                        $on++;
                    } else {
                        $off++;
                    }

                    $category['packs'][] = [
                        "id" => $pack->getId(),
                        "label" => $pack->getName(),
                        "checked" => $checked,
                        "future" => $pack->getDateRelease() === null
                    ];
                }
                $categories[] = $category;
            }
        }

        $searchForm = $this->renderView('AppBundle:Fellowship:form.html.twig', [
            'spheres' => $spheres,
            'allowed' => $categories,
            'on' => $on,
            'off' => $off,
            'author' => '',
            'name' => '',
            'numcores' => '12',
            'numplaysets' => '4'
        ]);

        return $this->render('AppBundle:Fellowship:public-fellowships.html.twig', [
            'pagetitle' => 'Fellowship Search',
            'fellowships' => null,
            'url' => $request->getRequestUri(),
            'header' => $searchForm,
            'type' => 'find',
            'pages' => null,
            'prevurl' => null,
            'nexturl' => null,
        ], $response);
    }

    public function octgnexportAction($fellowship_id) {
        return $this->downloadFromSelection($fellowship_id, true);
    }

    public function textexportAction($fellowship_id) {
        return $this->downloadFromSelection($fellowship_id, false);
    }

    public function downloadFromSelection($fellowship_id, $octgn) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException("You must be logged in for this operation.");
        }

        /* @var $fellowship \AppBundle\Entity\Fellowship */
        $fellowship = $em->getRepository('AppBundle:Fellowship')->find($fellowship_id);
        if (!$fellowship) {
            throw new AccessDeniedHttpException("You don't have access to this fellowship.");
        }

        $fellowship_user = $fellowship->getUser();
        $is_public = $fellowship->getIsPublic();

        if ($fellowship_user->getId() != $user->getId() && !$fellowship_user->getIsShareDecks() && !$is_public) {
            throw new AccessDeniedHttpException("You don't have access to this fellowship.");
        }

        $file = tempnam("tmp", "zip");
        $zip = new \ZipArchive();
        $res = $zip->open($file, \ZipArchive::OVERWRITE);

        if ($res === true) {
            $decks = [];

            /* @var $fellowship_decks \AppBundle\Entity\FellowshipDeck[] */
            $fellowship_decks = $fellowship->getDecks();
            foreach ($fellowship_decks as $fellowship_deck) {
                $decks[] = $fellowship_deck->getDeck();
            }

            /* @var $fellowship_decks \AppBundle\Entity\FellowshipDecklist[] */
            $fellowship_decklists = $fellowship->getDecklists();
            foreach ($fellowship_decklists as $fellowship_decklist) {
                $decks[] = $fellowship_decklist->getDecklist();
            }

            foreach ($decks as $deck) {
                /* @var $deck \AppBundle\Entity\Deck */
                if (!$deck) {
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
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->get('texts')->slugify('RingsDB - Fellowship ' . $fellowship_id) . '.zip'));

        $response->setContent(file_get_contents($file));
        unlink($file);

        return $response;
    }

    public function favoriteAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
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

        /* @var $author \AppBundle\Entity\User */
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
    public function commentAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();


        /* @var $user \AppBundle\Entity\User */
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
                if (!isset($spool[$fellowship->getUser()->getEmail()])) {
                    $spool[$fellowship->getUser()->getEmail()] = 'AppBundle:Emails:newfellowshipcomment_author.html.twig';
                }
            }

            foreach ($fellowship->getComments() as $comment) {
                /* @var $comment \AppBundle\Entity\FellowshipComment */
                $commenter = $comment->getUser();
                if ($commenter && $commenter->getIsNotifCommenter()) {
                    if (!isset($spool[$commenter->getEmail()])) {
                        $spool[$commenter->getEmail()] = 'AppBundle:Emails:newfellowshipcomment_commenter.html.twig';
                    }
                }
            }

            foreach ($mentionned_usernames as $mentionned_username) {
                /* @var $mentionned_user \AppBundle\Entity\User */
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
    public function hidecommentAction($comment_id, $hidden) {
        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $comment = $em->getRepository('AppBundle:FellowshipComment')->find($comment_id);
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

        /* @var $fellowship \AppBundle\Entity\Fellowship */
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
                /* @var $author \AppBundle\Entity\User */
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

    public function byauthorAction($username) {
        return $this->redirect($this->generateUrl('fellowships_list', ['type' => 'find', 'author' => $username]));
    }
}
