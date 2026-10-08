<?php

declare(strict_types=1);

namespace App\Controller\Admin\Pack;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Pack;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowPackController extends AbstractController
{
    use DeleteFormTrait;

    /**
     * Finds and displays a Pack entity.
     */
    #[Route(path: '/admin/pack/{id}/show', name: 'admin_pack_show')]
    public function __invoke(#[MapEntity(message: 'Unable to find Pack entity.')] Pack $entity): Response
    {
        $deleteForm = $this->createDeleteForm($entity->getId());

        return $this->render('Pack/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }
}
