<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Decklist;
use App\Model\JsonpDto;
use Doctrine\ORM\EntityManager;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Decklist')]
class GetDecklistController extends AbstractController
{
    use JsonpTrait;

    public function __construct(
        private readonly int $cacheExpiration
    ) {
    }

    /**
     * One Decklist.
     *
     * Get the description of a decklist as a JSON object.
     */
    #[OA\Parameter(name: 'decklist_id', in: 'path', description: 'The numeric identifier of the decklist', schema: new OA\Schema(type: 'integer'))]
    #[Route(path: '/api/public/decklist/{decklist_id}.{_format}', name: 'api_decklist', requirements: ['_format' => 'json', 'decklist_id' => '\d+'], defaults: ['_format' => 'json'], methods: ['GET'])]
    public function __invoke(Request $request, #[MapEntity(id: 'decklist_id', message: 'Decklist not found')] Decklist $decklist, #[MapQueryString] JsonpDto $query = new JsonpDto()): Response
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

        /* @var $em EntityManager */
        $response->setLastModified($decklist->getDateUpdate());
        if ($response->isNotModified($request)) {
            return $response;
        }

        $content = json_encode($decklist);
        $this->setJsonContent($response, (string) $content, $jsonp);

        return $response;
    }
}
