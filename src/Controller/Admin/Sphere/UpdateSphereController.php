<?php

declare(strict_types=1);

namespace App\Controller\Admin\Sphere;

use App\Entity\Sphere;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UpdateSphereController extends AbstractController
{
    use SphereFormsTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Edits an existing Sphere entity.
     */
    #[Route(path: '/admin/sphere/{id}/update', name: 'admin_sphere_update', methods: ['POST', 'PUT'])]
    public function __invoke(Request $request, #[MapEntity(message: 'Unable to find Sphere entity.')] Sphere $entity): Response
    {
        $deleteForm = $this->createDeleteForm($entity->getId());
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_sphere_edit', ['id' => $entity->getId()]));
        }

        return $this->render('Sphere/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
