<?php

declare(strict_types=1);

namespace App\Controller\Admin\Cycle;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Cycle;
use App\Form\CycleType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EditCycleController extends AbstractController
{
    use DeleteFormTrait;

    /**
     * Displays a form to edit an existing Cycle entity.
     */
    #[Route(path: '/admin/cycle/{id}/edit', name: 'admin_cycle_edit')]
    public function __invoke(#[MapEntity(message: 'Unable to find Cycle entity.')] Cycle $entity): Response
    {
        $editForm = $this->createForm(CycleType::class, $entity, ['method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($entity->getId());

        return $this->render('Cycle/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
