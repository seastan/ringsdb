<?php

declare(strict_types=1);

namespace App\Controller\Admin\Sphere;

use App\Entity\Sphere;
use App\Repository\SphereRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowSphereController extends AbstractController
{
    use SphereFormsTrait;

    public function __construct(
        private readonly SphereRepository $sphereRepository
    ) {
    }

    /**
     * Finds and displays a Sphere entity.
     */
    #[Route(path: '/admin/sphere/{id}/show', name: 'admin_sphere_show')]
    public function __invoke(int $id): Response
    {
        $entity = $this->sphereRepository->find($id);
        if (!$entity instanceof Sphere) {
            throw $this->createNotFoundException('Unable to find Sphere entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Sphere/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }
}
