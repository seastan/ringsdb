<?php

declare(strict_types=1);

namespace App\Controller\Admin\Card;

use App\Entity\Card;
use App\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexCardController extends AbstractController
{
    public function __construct(
        private readonly CardRepository $cardRepository
    ) {
    }

    /**
     * Lists all Card entities.
     */
    #[Route(path: '/admin/card/', name: 'admin_card')]
    public function __invoke(): Response
    {
        $entities = $this->cardRepository->findAll();

        return $this->render('Card/index.html.twig', ['entities' => $entities]);
    }
}
