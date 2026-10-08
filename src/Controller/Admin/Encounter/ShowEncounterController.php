<?php

declare(strict_types=1);

namespace App\Controller\Admin\Encounter;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Encounter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowEncounterController extends AbstractController
{
    use DeleteFormTrait;

    /**
     * Finds and displays a Encounter entity.
     */
    #[Route(path: '/admin/encounter/{id}/show', name: 'admin_encounter_show')]
    public function __invoke(#[MapEntity(message: 'Unable to find Encounter entity.')] Encounter $entity): Response
    {
        $deleteForm = $this->createDeleteForm($entity->getId());

        return $this->render('Encounter/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }
}
