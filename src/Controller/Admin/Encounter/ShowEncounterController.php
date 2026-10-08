<?php

declare(strict_types=1);

namespace App\Controller\Admin\Encounter;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Encounter;
use App\Repository\EncounterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowEncounterController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly EncounterRepository $encounterRepository
    ) {
    }

    /**
     * Finds and displays a Encounter entity.
     */
    #[Route(path: '/admin/encounter/{id}/show', name: 'admin_encounter_show')]
    public function __invoke(int $id): Response
    {
        $entity = $this->encounterRepository->find($id);
        if (!$entity instanceof Encounter) {
            throw $this->createNotFoundException('Unable to find Encounter entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Encounter/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }
}
