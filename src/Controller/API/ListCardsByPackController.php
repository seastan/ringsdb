<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Card;
use App\Entity\Pack;
use App\Repository\PackRepository;
use App\Services\CardsData;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ListCardsByPackController extends AbstractController
{
    use JsonpTrait;

    public function __construct(
        private readonly CardsData $cardsData,
        private readonly int $cacheExpiration,
        private readonly PackRepository $packRepository
    ) {
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
     */
    #[Route(path: '/api/public/cards/{pack_code}.{_format}', name: 'api_cards_pack', requirements: ['_format' => 'json|xml|xlsx|xls'], defaults: ['_format' => 'json'], methods: ['GET'])]
    public function __invoke(Request $request, string $pack_code): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->getString('jsonp');
        $format = $request->getRequestFormat();
        if ('json' !== $format) {
            $response->setContent($request->getRequestFormat().' format not supported. Only json is supported.');

            return $response;
        }

        /* @var $em EntityManager */
        /* @var $pack \App\Entity\Pack */
        $pack = $this->packRepository->findOneBy(['code' => $pack_code]);
        if (!$pack instanceof Pack) {
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
}
