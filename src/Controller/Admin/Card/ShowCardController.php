<?php

declare(strict_types=1);

namespace App\Controller\Admin\Card;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Card;
use App\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowCardController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly CardRepository $cardRepository
    ) {
    }

    /**
     * Finds and displays a Card entity.
     */
    #[Route(path: '/admin/card/{id}/show', name: 'admin_card_show')]
    public function __invoke(int $id): Response
    {
        $entity = $this->cardRepository->find($id);
        if (!$entity instanceof Card) {
            throw $this->createNotFoundException('Unable to find Card entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Card/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }
}
