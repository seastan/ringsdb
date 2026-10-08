<?php

declare(strict_types=1);

namespace App\Controller\Admin\Cycle;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Cycle;
use App\Repository\CycleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowCycleController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly CycleRepository $cycleRepository
    ) {
    }

    /**
     * Finds and displays a Cycle entity.
     */
    #[Route(path: '/admin/cycle/{id}/show', name: 'admin_cycle_show')]
    public function __invoke(int $id): Response
    {
        $entity = $this->cycleRepository->find($id);
        if (!$entity instanceof Cycle) {
            throw $this->createNotFoundException('Unable to find Cycle entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Cycle/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }
}
