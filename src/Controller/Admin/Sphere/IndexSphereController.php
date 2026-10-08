<?php

declare(strict_types=1);

namespace App\Controller\Admin\Sphere;

use App\Entity\Sphere;
use App\Repository\SphereRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexSphereController extends AbstractController
{
    public function __construct(
        private readonly SphereRepository $sphereRepository
    ) {
    }

    /**
     * Lists all Sphere entities.
     */
    #[Route(path: '/admin/sphere/', name: 'admin_sphere')]
    public function __invoke(): Response
    {
        $entities = $this->sphereRepository->findAll();

        return $this->render('Sphere/index.html.twig', ['entities' => $entities]);
    }
}
