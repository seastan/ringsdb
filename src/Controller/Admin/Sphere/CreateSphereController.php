<?php

declare(strict_types=1);

namespace App\Controller\Admin\Sphere;

use App\Entity\Sphere;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreateSphereController extends AbstractController
{
    use SphereFormsTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Creates a new Sphere entity.
     */
    #[Route(path: '/admin/sphere/create', name: 'admin_sphere_create', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $entity = new Sphere();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_sphere_show', ['id' => $entity->getId()]));
        }

        return $this->render('Sphere/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }
}
