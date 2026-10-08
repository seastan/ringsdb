<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Card;
use App\Entity\CardPrinting;
use App\Entity\Pack;
use App\Repository\CardRepository;
use App\Services\CardsData;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ListCardsController extends AbstractController
{
    use JsonpTrait;

    public function __construct(
        private readonly CardsData $cardsData,
        private readonly int $cacheExpiration,
        private readonly CardRepository $cardRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
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
     */
    #[Route(path: '/api/public/cards/', name: 'api_cards', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->getString('jsonp');
        /* @var $em EntityManager */
        /* @var $list_cards \App\Entity\Card[] */
        // Eager-load printings (+ their packs) and the card's pack/type/sphere so
        // getCardInfo doesn't issue N+1 queries while building packs[] for every card.
        $list_cards = $this->cardRepository->createQueryBuilder('c')->leftJoin('c.printings', 'cp')->addSelect('cp')->leftJoin('cp.pack', 'cpp')->addSelect('cpp')->leftJoin('c.type', 't')->addSelect('t')->leftJoin('c.sphere', 's')->addSelect('s')->orderBy('c.code', \SortDirection::Ascending)->getQuery()->getResult();
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
}
