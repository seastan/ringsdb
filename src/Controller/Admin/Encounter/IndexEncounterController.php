<?php

declare(strict_types=1);

namespace App\Controller\Admin\Encounter;

use App\Entity\Encounter;
use App\Repository\EncounterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexEncounterController extends AbstractController
{
    public function __construct(
        private readonly EncounterRepository $encounterRepository
    ) {
    }

    /**
     * Lists all Encounter entities.
     */
    #[Route(path: '/admin/encounter/', name: 'admin_encounter')]
    public function __invoke(): Response
    {
        $entities = $this->encounterRepository->findAll();

        return $this->render('Encounter/index.html.twig', ['entities' => $entities]);
    }
}
