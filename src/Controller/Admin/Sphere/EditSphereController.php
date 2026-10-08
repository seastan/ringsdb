<?php

declare(strict_types=1);

namespace App\Controller\Admin\Sphere;

use App\Entity\Sphere;
use App\Repository\SphereRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EditSphereController extends AbstractController
{
    use SphereFormsTrait;

    public function __construct(
        private readonly SphereRepository $sphereRepository
    ) {
    }

    /**
     * Displays a form to edit an existing Sphere entity.
     */
    #[Route(path: '/admin/sphere/{id}/edit', name: 'admin_sphere_edit')]
    public function __invoke(int $id): Response
    {
        $entity = $this->sphereRepository->find($id);
        if (!$entity instanceof Sphere) {
            throw $this->createNotFoundException('Unable to find Sphere entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Sphere/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
