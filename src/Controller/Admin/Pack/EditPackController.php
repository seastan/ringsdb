<?php

declare(strict_types=1);

namespace App\Controller\Admin\Pack;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Pack;
use App\Form\PackType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EditPackController extends AbstractController
{
    use DeleteFormTrait;

    /**
     * Displays a form to edit an existing Pack entity.
     */
    #[Route(path: '/admin/pack/{id}/edit', name: 'admin_pack_edit')]
    public function __invoke(#[MapEntity(message: 'Unable to find Pack entity.')] Pack $entity): Response
    {
        $editForm = $this->createForm(PackType::class, $entity, ['method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($entity->getId());

        return $this->render('Pack/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
