<?php

declare(strict_types=1);

namespace App\Controller\Admin\Encounter;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Encounter;
use App\Form\EncounterType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EditEncounterController extends AbstractController
{
    use DeleteFormTrait;

    /**
     * Displays a form to edit an existing Encounter entity.
     */
    #[Route(path: '/admin/encounter/{id}/edit', name: 'admin_encounter_edit')]
    public function __invoke(#[MapEntity(message: 'Unable to find Encounter entity.')] Encounter $entity): Response
    {
        $editForm = $this->createForm(EncounterType::class, $entity, ['method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($entity->getId());

        return $this->render('Encounter/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
