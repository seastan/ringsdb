<?php

declare(strict_types=1);

namespace App\Controller\Admin\Sphere;

use App\Entity\Sphere;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowSphereController extends AbstractController
{
    use SphereFormsTrait;

    /**
     * Finds and displays a Sphere entity.
     */
    #[Route(path: '/admin/sphere/{id}/show', name: 'admin_sphere_show')]
    public function __invoke(#[MapEntity(message: 'Unable to find Sphere entity.')] Sphere $entity): Response
    {
        $deleteForm = $this->createDeleteForm($entity->getId());

        return $this->render('Sphere/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }
}
