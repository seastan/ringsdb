<?php

declare(strict_types=1);

namespace App\Controller\Admin\Cycle;

use App\Entity\Cycle;
use App\Repository\CycleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexCycleController extends AbstractController
{
    public function __construct(
        private readonly CycleRepository $cycleRepository
    ) {
    }

    /**
     * Lists all Cycle entities.
     */
    #[Route(path: '/admin/cycle/', name: 'admin_cycle')]
    public function __invoke(): Response
    {
        $entities = $this->cycleRepository->findAll();

        return $this->render('Cycle/index.html.twig', ['entities' => $entities]);
    }
}
