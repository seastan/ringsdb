<?php

declare(strict_types=1);

namespace App\Controller\Admin\Type;

use App\Entity\Type;
use App\Repository\TypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexTypeController extends AbstractController
{
    public function __construct(
        private readonly TypeRepository $typeRepository
    ) {
    }

    /**
     * Lists all Type entities.
     */
    #[Route(path: '/admin/type/', name: 'admin_type')]
    public function __invoke(): Response
    {
        $entities = $this->typeRepository->findAll();

        return $this->render('Type/index.html.twig', ['entities' => $entities]);
    }
}
