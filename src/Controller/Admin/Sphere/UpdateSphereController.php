<?php

declare(strict_types=1);

namespace App\Controller\Admin\Sphere;

use App\Entity\Sphere;
use App\Repository\SphereRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UpdateSphereController extends AbstractController
{
    use SphereFormsTrait;

    public function __construct(
        private readonly SphereRepository $sphereRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Edits an existing Sphere entity.
     */
    #[Route(path: '/admin/sphere/{id}/update', name: 'admin_sphere_update', methods: ['POST', 'PUT'])]
    public function __invoke(Request $request, int $id): Response
    {
        $entity = $this->sphereRepository->find($id);
        if (!$entity instanceof Sphere) {
            throw $this->createNotFoundException('Unable to find Sphere entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_sphere_edit', ['id' => $id]));
        }

        return $this->render('Sphere/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
