<?php

declare(strict_types=1);

namespace App\Controller\Admin\Sphere;

use App\Entity\Sphere;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EditSphereController extends AbstractController
{
    use SphereFormsTrait;

    /**
     * Displays a form to edit an existing Sphere entity.
     */
    #[Route(path: '/admin/sphere/{id}/edit', name: 'admin_sphere_edit')]
    public function __invoke(#[MapEntity(message: 'Unable to find Sphere entity.')] Sphere $entity): Response
    {
        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($entity->getId());

        return $this->render('Sphere/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
