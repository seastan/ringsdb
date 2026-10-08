<?php

declare(strict_types=1);

namespace App\Controller\Admin\Sphere;

use App\Entity\Sphere;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DeleteSphereController extends AbstractController
{
    use SphereFormsTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Deletes a Sphere entity.
     */
    #[Route(path: '/admin/sphere/{id}/delete', name: 'admin_sphere_delete', methods: ['POST', 'DELETE'])]
    public function __invoke(Request $request, #[MapEntity(message: 'Unable to find Sphere entity.')] Sphere $entity): RedirectResponse
    {
        $form = $this->createDeleteForm($entity->getId());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_sphere'));
    }
}
