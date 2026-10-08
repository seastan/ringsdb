<?php

declare(strict_types=1);

namespace App\Controller\Admin\Encounter;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Encounter;
use App\Form\EncounterType;
use App\Repository\EncounterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EditEncounterController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly EncounterRepository $encounterRepository
    ) {
    }

    /**
     * Displays a form to edit an existing Encounter entity.
     */
    #[Route(path: '/admin/encounter/{id}/edit', name: 'admin_encounter_edit')]
    public function __invoke(int $id): Response
    {
        $entity = $this->encounterRepository->find($id);
        if (!$entity instanceof Encounter) {
            throw $this->createNotFoundException('Unable to find Encounter entity.');
        }

        $editForm = $this->createForm(EncounterType::class, $entity, ['method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Encounter/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
