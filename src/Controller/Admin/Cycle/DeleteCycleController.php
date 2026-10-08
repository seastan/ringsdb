<?php

declare(strict_types=1);

namespace App\Controller\Admin\Cycle;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Cycle;
use App\Repository\CycleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DeleteCycleController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly CycleRepository $cycleRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Deletes a Cycle entity.
     */
    #[Route(path: '/admin/cycle/{id}/delete', name: 'admin_cycle_delete', methods: ['POST', 'DELETE'])]
    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $this->cycleRepository->find($id);
            if (!$entity instanceof Cycle) {
                throw $this->createNotFoundException('Unable to find Cycle entity.');
            }

            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_cycle'));
    }
}
