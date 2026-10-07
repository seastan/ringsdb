<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Card;
use App\Entity\CardPrinting;
use App\Entity\Decklist;
use App\Entity\Pack;
use App\Entity\Scenario;
use App\Repository\CardRepository;
use App\Repository\DecklistRepository;
use App\Repository\PackRepository;
use App\Repository\ScenarioRepository;
use App\Repository\UserRepository;
use App\Services\CardsData;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApiController extends AbstractController
{
    public function __construct(
        private readonly CardsData $cardsData,
        private readonly int $cacheExpiration,
        private readonly CardRepository $cardRepository,
        private readonly DecklistRepository $decklistRepository,
        private readonly PackRepository $packRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Get the description of all the packs as an array of JSON objects.
     *
     * ApiDoc(
     *  section="Pack",
     *  resource=true,
     *  description="All the Packs",
     *  parameters={
     *    {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
     *  },
     * )
     *
     * @Route("/api/public/packs/", name="api_packs", methods={"GET"})
     */
    public function listPacksAction(Request $request): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->get('jsonp');
        /* @var $em EntityManager */
        /* @var $list_packs \App\Entity\Pack[] */
        $list_packs = $this->packRepository->findBy([], ['dateRelease' => 'ASC', 'position' => 'ASC']);
        // check the last-modified-since header
        $lastModified = null;
        foreach ($list_packs as $pack) {
            if (!$lastModified || $lastModified < $pack->getDateUpdate()) {
                $lastModified = $pack->getDateUpdate();
            }
        }

        $response->setLastModified($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $packs = [];
        /* @var $pack \App\Entity\Pack */
        foreach ($list_packs as $pack) {
            $real = count($pack->getCards());
            $max = $pack->getSize();
            $packs[] = [
                'name' => $pack->getName(),
                'code' => $pack->getCode(),
                'position' => $pack->getPosition(),
                'cycle_position' => $pack->getCycle() ? $pack->getCycle()->getPosition() : null,
                'available' => $pack->getDateRelease()
                    ? $pack->getDateRelease()->format('Y-m-d')
                    : '',
                'known' => intval($real),
                'total' => $max,
                'url' => $this->generateUrl('cards_list', ['pack_code' => $pack->getCode()], UrlGeneratorInterface::ABSOLUTE_URL),
                'id' => $pack->getId(),
            ];
        }

        $content = json_encode($packs);
        $this->setJsonContent($response, (string) $content, $jsonp);

        return $response;
    }

    /**
     * Get the description of a card as a JSON object.
     *
     * ApiDoc(
     *  section="Card",
     *  resource=true,
     *  description="One Card",
     *  parameters={
     *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
     *  },
     *  requirements={
     *      {
     *          "name"="card_code",
     *          "dataType"="string",
     *          "description"="The code of the card to get, e.g. '01001'"
     *      },
     *      {
     *          "name"="_format",
     *          "dataType"="string",
     *          "requirement"="json",
     *          "description"="The format of the returned data. Only 'json' is supported at the moment."
     *      }
     *  },
     * )
     *
     * @Route(
     *     "/api/public/card/{card_code}.{_format}",
     *     name="api_card",
     *     methods={"GET"},
     *     requirements={"_format"="json"},
     *     defaults={"_format"="json"}
     * )
     */
    public function getCardAction(Request $request, string $card_code): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->get('jsonp');
        /* @var $em EntityManager */
        /* @var $card \App\Entity\Card */
        $card = $this->cardRepository->findOneBy(['code' => $card_code]);
        if (!$card instanceof Card) {
            throw $this->createNotFoundException('Card not found');
        }

        // check the last-modified-since header
        $lastModified = $card->getDateUpdate();
        $response->setLastModified($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        // build the response
        /* @var $card \App\Entity\Card */
        $card = $this->cardsData->getCardInfo($card, true);
        $content = json_encode($card);
        $this->setJsonContent($response, (string) $content, $jsonp);

        return $response;
    }

    /**
     * Get the description of all the cards as an array of JSON objects.
     *
     * ApiDoc(
     *  section="Card",
     *  resource=true,
     *  description="All the Cards. Each card keeps pack_code/pack_name (its primary printing) plus a packs[] array listing every pack it appears in (pack_code, pack_name, position, quantity, image_code, illustrator, octgnid, imagesrc).",
     *  parameters={
     *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
     *  },
     * )
     *
     * @Route("/api/public/cards/", name="api_cards", methods={"GET"})
     */
    public function listCardsAction(Request $request): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->get('jsonp');
        /* @var $em EntityManager */
        /* @var $list_cards \App\Entity\Card[] */
        // Eager-load printings (+ their packs) and the card's pack/type/sphere so
        // getCardInfo doesn't issue N+1 queries while building packs[] for every card.
        $list_cards = $this->cardRepository->createQueryBuilder('c')->leftJoin('c.printings', 'cp')->addSelect('cp')->leftJoin('cp.pack', 'cpp')->addSelect('cpp')->leftJoin('c.type', 't')->addSelect('t')->leftJoin('c.sphere', 's')->addSelect('s')->orderBy('c.code', 'ASC')->getQuery()->getResult();
        // check the last-modified-since header (cards AND their printings, so a new
        // printing or repointed art invalidates the cached card list)
        $lastModified = null;
        /* @var $card \App\Entity\Card */
        foreach ($list_cards as $card) {
            if (!$lastModified || $lastModified < $card->getDateUpdate()) {
                $lastModified = $card->getDateUpdate();
            }
        }

        $printingMax = $this->entityManager
            ->createQuery('SELECT MAX(cp.dateUpdate) FROM '.CardPrinting::class.' cp')
            ->getSingleScalarResult();
        if ($printingMax) {
            $printingMax = new \DateTime((string) $printingMax);
            if (!$lastModified || $lastModified < $printingMax) {
                $lastModified = $printingMax;
            }
        }

        $response->setLastModified($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        // build the response
        $cards = [];
        /* @var $card \App\Entity\Card */
        foreach ($list_cards as $card) {
            $cards[] = $this->cardsData->getCardInfo($card, true);
        }

        $content = json_encode($cards);
        $this->setJsonContent($response, (string) $content, $jsonp);

        return $response;
    }

    /**
     * Get the description of all the card from a pack, as an array of JSON objects.
     *
     * ApiDoc(
     *  section="Card",
     *  resource=true,
     *  description="All the Cards from One Pack",
     *  parameters={
     *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
     *  },
     *  requirements={
     *      {
     *          "name"="pack_code",
     *          "dataType"="string",
     *          "description"="The code of the pack to get the cards from, e.g. 'core'"
     *      },
     *      {
     *          "name"="_format",
     *          "dataType"="string",
     *          "requirement"="json|xml|xlsx|xls",
     *          "description"="The format of the returned data. Only 'json' is supported at the moment."
     *      }
     *  },
     * )
     *
     * @Route(
     *     "/api/public/cards/{pack_code}.{_format}",
     *     name="api_cards_pack",
     *     methods={"GET"},
     *     requirements={"_format"="json|xml|xlsx|xls"},
     *     defaults={"_format"="json"}
     * )
     */
    public function listCardsByPackAction(Request $request, string $pack_code): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->get('jsonp');
        $format = $request->getRequestFormat();
        if ('json' !== $format) {
            $response->setContent($request->getRequestFormat().' format not supported. Only json is supported.');

            return $response;
        }

        /* @var $em EntityManager */
        /* @var $pack \App\Entity\Pack */
        $pack = $this->packRepository->findOneBy(['code' => $pack_code]);
        if (!$pack) {
            throw $this->createNotFoundException('Pack not found');
        }

        $conditions = $this->cardsData->syntax("e:{$pack_code}");
        $this->cardsData->validateConditions($conditions);
        $query = $this->cardsData->buildQueryFromConditions($conditions);
        $cards = [];
        $last_modified = null;
        /* @var $rows \App\Entity\Card[] */
        if ($query && ($rows = $this->cardsData->get_search_rows($conditions, 'set'))) {
            for ($rowindex = 0; $rowindex < count($rows); ++$rowindex) {
                if (empty($last_modified) || $last_modified < $rows[$rowindex]->getDateUpdate()) {
                    $last_modified = $rows[$rowindex]->getDateUpdate();
                }
            }

            $response->setLastModified($last_modified);
            if ($response->isNotModified($request)) {
                return $response;
            }

            for ($rowindex = 0; $rowindex < count($rows); ++$rowindex) {
                $card = $this->cardsData->getCardInfo($rows[$rowindex], true);
                $cards[] = $card;
            }
        }

        $content = json_encode($cards);
        $this->setJsonContent($response, (string) $content, $jsonp);

        return $response;
    }

    /**
     * Get the description of a decklist as a JSON object.
     *
     * ApiDoc(
     *  section="Decklist",
     *  resource=true,
     *  description="One Decklist",
     *  parameters={
     *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
     *  },
     *  requirements={
     *      {
     *          "name"="decklist_id",
     *          "dataType"="integer",
     *          "requirement"="\d+",
     *          "description"="The numeric identifier of the decklist"
     *      },
     *      {
     *          "name"="_format",
     *          "dataType"="string",
     *          "requirement"="json",
     *          "description"="The format of the returned data. Only 'json' is supported at the moment."
     *      }
     *  },
     * )
     *
     * @Route(
     *     "/api/public/decklist/{decklist_id}.{_format}",
     *     name="api_decklist",
     *     methods={"GET"},
     *     requirements={"_format"="json", "decklist_id"="\d+"},
     *     defaults={"_format"="json"}
     * )
     */
    public function getDecklistAction(Request $request, int $decklist_id): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->get('jsonp');
        $format = $request->getRequestFormat();
        if ('json' !== $format) {
            $response->setContent($request->getRequestFormat().' format not supported. Only json is supported.');

            return $response;
        }

        /* @var $em EntityManager */
        /* @var $decklist \App\Entity\Decklist */
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw $this->createNotFoundException('Decklist not found');
        }

        $response->setLastModified($decklist->getDateUpdate());
        if ($response->isNotModified($request)) {
            return $response;
        }

        $content = json_encode($decklist);
        $this->setJsonContent($response, (string) $content, $jsonp);

        return $response;
    }

    /**
     * Get the description of all the decklists published at a given date, as an array of JSON objects.
     *
     * ApiDoc(
     *  section="Decklist",
     *  resource=true,
     *  description="All the Decklists from One Day",
     *  parameters={
     *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
     *  },
     *  requirements={
     *      {
     *          "name"="date",
     *          "dataType"="string",
     *          "requirement"="\d\d\d\d-\d\d-\d\d",
     *          "description"="The date, format 'Y-m-d'"
     *      },
     *      {
     *          "name"="_format",
     *          "dataType"="string",
     *          "requirement"="json",
     *          "description"="The format of the returned data. Only 'json' is supported at the moment."
     *      }
     *  },
     * )
     *
     * @Route(
     *     "/api/public/decklists/by_date/{date}.{_format}",
     *     name="api_decklists_by_date",
     *     methods={"GET"},
     *     requirements={"_format"="json", "date"="\d\d\d\d-\d\d-\d\d"},
     *     defaults={"_format"="json"}
     * )
     */
    public function listDecklistsByDateAction(Request $request, UserRepository $userRepository, string $date): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->get('jsonp');
        $format = $request->getRequestFormat();
        if ('json' !== $format) {
            $response->setContent($request->getRequestFormat().' format not supported. Only json is supported.');

            return $response;
        }

        /* @var $em EntityManager */
        $qb = $this->decklistRepository->createQueryBuilder('d');
        $qb->andWhere("d.dateCreation LIKE '{$date}%'");

        $decklists = $qb->getQuery()->getResult();
        $cardRepo = $this->cardRepository;
        $userRepo = $userRepository;
        $decklists = json_decode((string) json_encode($decklists), true);
        foreach ($decklists as &$decklist) {
            $decklist['heroes_details'] = [];
            $username = '';
            $user = $userRepo->findOneBy(['id' => $decklist['user_id']]);
            if ($user) {
                $username = $user->getUsername();
            }

            $decklist['username'] = $username;
            $codes = array_keys($decklist['heroes']);
            foreach ($codes as $code) {
                $card = $cardRepo->findOneBy(['code' => $code]);
                if (!$card) {
                    continue;
                }

                $decklist['heroes_details'][] = [
                    'name' => $card->getName(),
                    'sphere' => $card->getSphere() ? $card->getSphere()->getName() : null,
                    'pack' => $card->getPack() ? $card->getPack()->getName() : null,
                ];
            }
        }

        $content = json_encode($decklists);
        $this->setJsonContent($response, (string) $content, $jsonp);

        return $response;
    }

    /**
     * Get the top 10 decklists published containing given card, as an array of JSON objects.
     *
     * ApiDoc(
     *  section="Decklist",
     *  resource=true,
     *  description="Top 10 Decklists containing a specific card",
     *  parameters={
     *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
     *  },
     *  requirements={
     *      {
     *          "name"="card_code",
     *          "dataType"="string",
     *          "description"="The code of the card to get, e.g. '01001'"
     *      },
     *      {
     *          "name"="_format",
     *          "dataType"="string",
     *          "requirement"="json",
     *          "description"="The format of the returned data. Only 'json' is supported at the moment."
     *      }
     *  },
     * )
     *
     * @Route(
     *     "/api/public/decklists/top_by_card/{card_code}.{_format}",
     *     name="api_decklists_by_card",
     *     methods={"GET"},
     *     requirements={"_format"="json"},
     *     defaults={"_format"="json"}
     * )
     */
    public function listTopDecklistsByCardAction(Request $request, string $card_code): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->get('jsonp');
        $format = $request->getRequestFormat();
        if ('json' !== $format) {
            $response->setContent($request->getRequestFormat().' format not supported. Only json is supported.');

            return $response;
        }

        /* @var $em EntityManager */
        $card = $this->cardRepository->findOneBy(['code' => $card_code]);
        if (!$card) {
            $response->setContent('[]');

            return $response;
        }

        $qb = $this->entityManager->createQueryBuilder();
        // Select decklists
        $qb->select('d.id, d.name, d.nameCanonical, d.dateCreation, d.dateUpdate');
        $qb->from(Decklist::class, 'd');
        // high popularity
        $qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.dateCreation), 2)) AS HIDDEN popularity');
        $qb->orderBy('popularity', 'DESC');
        $qb->addOrderBy('d.id', 'DESC');
        // containing the card
        $qb->innerJoin('d.slots', 's');
        $qb->andWhere('s.card = :card');
        $qb->setParameter('card', $card);
        // limit 10
        $qb->setMaxResults(10);

        $query = $qb->getQuery();
        /* @var $decklists ArrayCollection */
        $decklists = $query->getArrayResult();
        $lastModified = null;
        foreach ($decklists as &$decklist) {
            if (!$lastModified || $lastModified < $decklist['dateUpdate']) {
                $lastModified = $decklist['dateUpdate'];
            }
        }

        $response->setLastModified($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        foreach ($decklists as &$decklist) {
            $decklist['url'] = $this->generateUrl('decklist_detail', ['decklist_id' => $decklist['id'], 'decklist_name' => $decklist['nameCanonical']]);
            unset($decklist['descriptionMd']);
            unset($decklist['descriptionHtml']);
            $decklist['dateCreation'] = $decklist['dateCreation']->format('c');
            $decklist['dateUpdate'] = $decklist['dateUpdate']->format('c');
        }

        $content = json_encode($decklists);
        $this->setJsonContent($response, (string) $content, $jsonp);

        return $response;
    }

    /**
     * Get the description of a scenario as a JSON object.
     *
     * ApiDoc(
     *  section="Scenario",
     *  resource=true,
     *  description="One Scenario",
     *  parameters={
     *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
     *  },
     *  requirements={
     *      {
     *          "name"="scenario_id",
     *          "dataType"="integer",
     *          "description"="The code of the scenario to get, e.g. '01001'"
     *      },
     *      {
     *          "name"="_format",
     *          "dataType"="string",
     *          "requirement"="json",
     *          "description"="The format of the returned data. Only 'json' is supported at the moment."
     *      }
     *  },
     * )
     *
     * @Route(
     *     "/api/public/scenario/{scenario_id}.{_format}",
     *     name="api_scenario",
     *     methods={"GET"},
     *     requirements={"_format"="json", "scenario_id"="\d+"},
     *     defaults={"_format"="json"}
     * )
     */
    public function getScenarioAction(Request $request, ScenarioRepository $scenarioRepository, int $scenario_id): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->get('jsonp');
        /* @var $em EntityManager */
        /* @var $scenario \App\Entity\Scenario */
        $scenario = $scenarioRepository->findOneBy(['id' => $scenario_id]);
        if (!$scenario instanceof Scenario) {
            throw $this->createNotFoundException('Scenario not found.');
        }

        // check the last-modified-since header
        $lastModified = $scenario->getDateUpdate();
        $response->setLastModified($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $content = json_encode($scenario);
        $this->setJsonContent($response, (string) $content, $jsonp);

        return $response;
    }

    /**
     * @Route("/api/public/cards/search/{q}", name="api_cards_search", methods={"GET"})
     */
    public function searchCardsAction(Request $request, string $q): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->get('jsonp');
        $cards = [];
        $conditions = $this->cardsData->syntax(urldecode($q));
        $conditions = $this->cardsData->validateConditions($conditions);

        $last_modified = null;
        $query = $this->cardsData->buildQueryFromConditions($conditions);
        if ($query && ($rows = $this->cardsData->get_search_rows($conditions, 'set'))) {
            for ($rowindex = 0; $rowindex < count($rows); ++$rowindex) {
                if (empty($last_modified) || $last_modified < $rows[$rowindex]->getDateUpdate()) {
                    $last_modified = $rows[$rowindex]->getDateUpdate();
                }
            }

            $response->setLastModified($last_modified);
            if ($response->isNotModified($request)) {
                return $response;
            }

            for ($rowindex = 0; $rowindex < count($rows); ++$rowindex) {
                $card = $this->cardsData->getCardInfo($rows[$rowindex], true);
                $cards[] = $card;
            }
        }

        $content = json_encode($cards);
        $this->setJsonContent($response, (string) $content, $jsonp);

        return $response;
    }

    /**
     * The JSON content of an API response, wrapped in the JSONP callback when one is given. The
     * callback is validated by JsonResponse::setCallback() (a JavaScript identifier, with dots and
     * brackets, no reserved word) and the script prefixed with a comment, against content sniffing.
     * An empty callback is ignored.
     *
     * @param string|null $callback
     */
    private function setJsonContent(Response $response, string $json, $callback): void
    {
        if (null === $callback || '' === $callback) {
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent($json);

            return;
        }

        $jsonp = new JsonResponse();
        $jsonp->setJson($json);
        try {
            $jsonp->setCallback($callback);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            throw new BadRequestHttpException('Invalid JSONP callback.', $invalidArgumentException);
        }

        $response->headers->set('Content-Type', 'application/javascript');
        $response->setContent((string) $jsonp->getContent());
    }
}
