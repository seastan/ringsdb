<?php

declare(strict_types=1);

namespace App\Controller\Admin\Cycle;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Cycle;
use App\Form\CycleType;
use App\Repository\CycleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UpdateCycleController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly CycleRepository $cycleRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Edits an existing Cycle entity.
     */
    #[Route(path: '/admin/cycle/{id}/update', name: 'admin_cycle_update', methods: ['POST', 'PUT'])]
    public function __invoke(Request $request, int $id): Response
    {
        $entity = $this->cycleRepository->find($id);
        if (!$entity instanceof Cycle) {
            throw $this->createNotFoundException('Unable to find Cycle entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(CycleType::class, $entity, ['method' => 'PUT']);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_cycle_edit', ['id' => $id]));
        }

        return $this->render('Cycle/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
