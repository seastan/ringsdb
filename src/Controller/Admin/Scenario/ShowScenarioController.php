<?php

declare(strict_types=1);

namespace App\Controller\Admin\Scenario;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Scenario;
use App\Repository\ScenarioRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowScenarioController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly ScenarioRepository $scenarioRepository
    ) {
    }

    /**
     * Finds and displays a Scenario entity.
     */
    #[Route(path: '/admin/scenario/{id}/show', name: 'admin_scenario_show')]
    public function __invoke(int $id): Response
    {
        $entity = $this->scenarioRepository->find($id);
        if (!$entity instanceof Scenario) {
            throw $this->createNotFoundException('Unable to find Scenario entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Scenario/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }
}
