<?php

declare(strict_types=1);

namespace App\Controller\Admin\Scenario;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Scenario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DeleteScenarioController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Deletes a Scenario entity.
     */
    #[Route(path: '/admin/scenario/{id}/delete', name: 'admin_scenario_delete', methods: ['POST', 'DELETE'])]
    public function __invoke(Request $request, #[MapEntity(message: 'Unable to find Scenario entity.')] Scenario $entity): RedirectResponse
    {
        $form = $this->createDeleteForm($entity->getId());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_scenario'));
    }
}
