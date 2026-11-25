<?php

declare(strict_types=1);

namespace App\Controller\Admin\Scenario;

use App\Entity\Scenario;
use App\Repository\ScenarioRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexScenarioController extends AbstractController
{
    public function __construct(
        private readonly ScenarioRepository $scenarioRepository
    ) {
    }

    /**
     * Lists all Scenario entities.
     */
    #[Route(path: '/admin/scenario/', name: 'admin_scenario')]
    public function __invoke(): Response
    {
        $entities = $this->scenarioRepository->findAll();

        return $this->render('Scenario/index.html.twig', ['entities' => $entities]);
    }
}
