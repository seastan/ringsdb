<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Services\CardsData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchCardsController extends AbstractController
{
    use JsonpTrait;

    public function __construct(
        private readonly CardsData $cardsData,
        private readonly int $cacheExpiration
    ) {
    }

    #[Route(path: '/api/public/cards/search/{q}', name: 'api_cards_search', methods: ['GET'])]
    public function __invoke(Request $request, string $q): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->getString('jsonp');
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
}
