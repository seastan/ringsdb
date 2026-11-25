<?php

declare(strict_types=1);

namespace App\Controller\Admin\Cycle;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Cycle;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowCycleController extends AbstractController
{
    use DeleteFormTrait;

    /**
     * Finds and displays a Cycle entity.
     */
    #[Route(path: '/admin/cycle/{id}/show', name: 'admin_cycle_show')]
    public function __invoke(#[MapEntity(message: 'Unable to find Cycle entity.')] Cycle $entity): Response
    {
        $deleteForm = $this->createDeleteForm($entity->getId());

        return $this->render('Cycle/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }
}
