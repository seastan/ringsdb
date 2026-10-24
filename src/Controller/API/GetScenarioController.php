<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Scenario;
use App\Model\JsonpDto;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Scenario')]
class GetScenarioController extends AbstractController
{
    use JsonpTrait;

    public function __construct(
        private readonly int $cacheExpiration
    ) {
    }

    /**
     * One Scenario.
     *
     * Get the description of a scenario as a JSON object.
     */
    #[OA\Parameter(name: 'scenario_id', in: 'path', description: 'The numeric identifier of the scenario', schema: new OA\Schema(type: 'integer'))]
    #[Route(path: '/api/public/scenario/{scenario_id}.{_format}', name: 'api_scenario', requirements: ['_format' => 'json', 'scenario_id' => '\d+'], defaults: ['_format' => 'json'], methods: ['GET'])]
    public function __invoke(Request $request, #[MapEntity(id: 'scenario_id', message: 'Scenario not found.')] Scenario $scenario, #[MapQueryString] JsonpDto $query = new JsonpDto()): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $query->jsonp;
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
}
