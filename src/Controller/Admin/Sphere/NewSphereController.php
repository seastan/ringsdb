<?php

declare(strict_types=1);

namespace App\Controller\Admin\Sphere;

use App\Entity\Sphere;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewSphereController extends AbstractController
{
    use SphereFormsTrait;

    /**
     * Displays a form to create a new Sphere entity.
     */
    #[Route(path: '/admin/sphere/new', name: 'admin_sphere_new')]
    public function __invoke(): Response
    {
        $entity = new Sphere();
        $form = $this->createCreateForm($entity);

        return $this->render('Sphere/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }
}
