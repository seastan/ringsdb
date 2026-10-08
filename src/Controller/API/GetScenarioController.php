<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Scenario;
use App\Repository\ScenarioRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GetScenarioController extends AbstractController
{
    use JsonpTrait;

    public function __construct(
        private readonly int $cacheExpiration
    ) {
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
     */
    #[Route(path: '/api/public/scenario/{scenario_id}.{_format}', name: 'api_scenario', requirements: ['_format' => 'json', 'scenario_id' => '\d+'], defaults: ['_format' => 'json'], methods: ['GET'])]
    public function __invoke(Request $request, ScenarioRepository $scenarioRepository, int $scenario_id): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->getString('jsonp');
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
}
