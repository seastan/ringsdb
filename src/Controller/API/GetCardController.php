<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Card;
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
class GetCardController extends AbstractController
{
    use JsonpTrait;

    public function __construct(
        private readonly CardsData $cardsData,
        private readonly int $cacheExpiration
    ) {
    }

    /**
     * One Card.
     *
     * Get the description of a card as a JSON object.
     */
    #[OA\Parameter(name: 'card_code', in: 'path', description: "The code of the card to get, e.g. '01001'")]
    #[Route(path: '/api/public/card/{card_code}.{_format}', name: 'api_card', requirements: ['_format' => 'json'], defaults: ['_format' => 'json'], methods: ['GET'])]
    public function __invoke(Request $request, #[MapEntity(mapping: ['card_code' => 'code'], message: 'Card not found')] Card $card, #[MapQueryString] JsonpDto $query = new JsonpDto()): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $query->jsonp;
        // check the last-modified-since header
        $lastModified = $card->getDateUpdate();
        $response->setLastModified($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        // build the response
        $content = json_encode($this->cardsData->getCardInfo($card, true));
        $this->setJsonContent($response, (string) $content, $jsonp);

        return $response;
    }
}
