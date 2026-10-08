<?php

declare(strict_types=1);

namespace App\Controller\Admin\Pack;

use App\Entity\Pack;
use App\Repository\PackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexPackController extends AbstractController
{
    public function __construct(
        private readonly PackRepository $packRepository
    ) {
    }

    /**
     * Lists all Pack entities.
     */
    #[Route(path: '/admin/pack/', name: 'admin_pack')]
    public function __invoke(): Response
    {
        $entities = $this->packRepository->findAll();

        return $this->render('Pack/index.html.twig', ['entities' => $entities]);
    }
}
