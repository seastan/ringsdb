<?php

declare(strict_types=1);

namespace App\Controller\Admin\Encounter;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Encounter;
use App\Repository\EncounterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DeleteEncounterController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly EncounterRepository $encounterRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Deletes a Encounter entity.
     */
    #[Route(path: '/admin/encounter/{id}/delete', name: 'admin_encounter_delete', methods: ['POST', 'DELETE'])]
    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $this->encounterRepository->find($id);
            if (!$entity instanceof Encounter) {
                throw $this->createNotFoundException('Unable to find Encounter entity.');
            }

            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_encounter'));
    }
}
