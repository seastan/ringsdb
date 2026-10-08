<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Decklist;
use App\Repository\DecklistRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GetDecklistController extends AbstractController
{
    use JsonpTrait;

    public function __construct(
        private readonly int $cacheExpiration,
        private readonly DecklistRepository $decklistRepository
    ) {
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
     */
    #[Route(path: '/api/public/decklist/{decklist_id}.{_format}', name: 'api_decklist', requirements: ['_format' => 'json', 'decklist_id' => '\d+'], defaults: ['_format' => 'json'], methods: ['GET'])]
    public function __invoke(Request $request, int $decklist_id): Response
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
        /* @var $decklist \App\Entity\Decklist */
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist instanceof Decklist) {
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
}
