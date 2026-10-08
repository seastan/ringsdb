<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Card;
use App\Entity\Pack;
use App\Model\JsonpDto;
use App\Services\CardsData;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Card')]
class ListCardsByPackController extends AbstractController
{
    use JsonpTrait;

    public function __construct(
        private readonly CardsData $cardsData,
        private readonly int $cacheExpiration
    ) {
    }

    /**
     * All the Cards from One Pack.
     *
     * Get the description of all the card from a pack, as an array of JSON objects.
     */
    #[OA\Parameter(name: 'pack_code', in: 'path', description: "The code of the pack to get the cards from, e.g. 'Core'")]
    #[Route(path: '/api/public/cards/{pack_code}.{_format}', name: 'api_cards_pack', requirements: ['_format' => 'json|xml|xlsx|xls'], defaults: ['_format' => 'json'], methods: ['GET'])]
    public function __invoke(Request $request, #[MapEntity(mapping: ['pack_code' => 'code'], message: 'Pack not found')] Pack $pack, #[MapQueryString] JsonpDto $query = new JsonpDto()): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $query->jsonp;
        $format = $request->getRequestFormat();
        if ('json' !== $format) {
            $response->setContent($request->getRequestFormat().' format not supported. Only json is supported.');

            return $response;
        }

        $conditions = $this->cardsData->syntax('e:'.$pack->getCode());
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
}
