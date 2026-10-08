<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Card;
use App\Repository\CardRepository;
use App\Services\CardsData;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GetCardController extends AbstractController
{
    use JsonpTrait;

    public function __construct(
        private readonly CardsData $cardsData,
        private readonly int $cacheExpiration,
        private readonly CardRepository $cardRepository
    ) {
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
     */
    #[Route(path: '/api/public/card/{card_code}.{_format}', name: 'api_card', requirements: ['_format' => 'json'], defaults: ['_format' => 'json'], methods: ['GET'])]
    public function __invoke(Request $request, string $card_code): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->getString('jsonp');
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
}
