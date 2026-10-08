<?php

declare(strict_types=1);

namespace App\Controller\Admin\Scenario;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Scenario;
use App\Repository\ScenarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DeleteScenarioController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly ScenarioRepository $scenarioRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Deletes a Scenario entity.
     */
    #[Route(path: '/admin/scenario/{id}/delete', name: 'admin_scenario_delete', methods: ['POST', 'DELETE'])]
    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $this->scenarioRepository->find($id);
            if (!$entity instanceof Scenario) {
                throw $this->createNotFoundException('Unable to find Scenario entity.');
            }

            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_scenario'));
    }
}
