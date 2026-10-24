<?php

declare(strict_types=1);

namespace App\Controller\Admin\Scenario;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Scenario;
use App\Form\ScenarioType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EditScenarioController extends AbstractController
{
    use DeleteFormTrait;

    /**
     * Displays a form to edit an existing Scenario entity.
     */
    #[Route(path: '/admin/scenario/{id}/edit', name: 'admin_scenario_edit')]
    public function __invoke(#[MapEntity(message: 'Unable to find Scenario entity.')] Scenario $entity): Response
    {
        $editForm = $this->createForm(ScenarioType::class, $entity, ['method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($entity->getId());

        return $this->render('Scenario/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
