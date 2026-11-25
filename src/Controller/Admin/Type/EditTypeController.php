<?php

declare(strict_types=1);

namespace App\Controller\Admin\Type;

use App\Entity\Type;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EditTypeController extends AbstractController
{
    use TypeFormsTrait;

    /**
     * Displays a form to edit an existing Type entity.
     */
    #[Route(path: '/admin/type/{id}/edit', name: 'admin_type_edit')]
    public function __invoke(#[MapEntity(message: 'Unable to find Type entity.')] Type $entity): Response
    {
        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($entity->getId());

        return $this->render('Type/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
