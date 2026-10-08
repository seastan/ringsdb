<?php

declare(strict_types=1);

namespace App\Controller\Admin\Encounter;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Encounter;
use App\Form\EncounterType;
use App\Repository\EncounterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UpdateEncounterController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly EncounterRepository $encounterRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Edits an existing Encounter entity.
     */
    #[Route(path: '/admin/encounter/{id}/update', name: 'admin_encounter_update', methods: ['POST', 'PUT'])]
    public function __invoke(Request $request, int $id): Response
    {
        $entity = $this->encounterRepository->find($id);
        if (!$entity instanceof Encounter) {
            throw $this->createNotFoundException('Unable to find Encounter entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(EncounterType::class, $entity, ['method' => 'PUT']);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_encounter_edit', ['id' => $id]));
        }

        return $this->render('Encounter/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
