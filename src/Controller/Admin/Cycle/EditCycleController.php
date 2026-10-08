<?php

declare(strict_types=1);

namespace App\Controller\Admin\Cycle;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Cycle;
use App\Form\CycleType;
use App\Repository\CycleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EditCycleController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly CycleRepository $cycleRepository
    ) {
    }

    /**
     * Displays a form to edit an existing Cycle entity.
     */
    #[Route(path: '/admin/cycle/{id}/edit', name: 'admin_cycle_edit')]
    public function __invoke(int $id): Response
    {
        $entity = $this->cycleRepository->find($id);
        if (!$entity instanceof Cycle) {
            throw $this->createNotFoundException('Unable to find Cycle entity.');
        }

        $editForm = $this->createForm(CycleType::class, $entity, ['method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Cycle/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
