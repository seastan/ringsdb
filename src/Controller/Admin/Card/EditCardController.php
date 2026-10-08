<?php

declare(strict_types=1);

namespace App\Controller\Admin\Card;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Card;
use App\Form\CardType;
use App\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EditCardController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly CardRepository $cardRepository
    ) {
    }

    /**
     * Displays a form to edit an existing Card entity.
     */
    #[Route(path: '/admin/card/{id}/edit', name: 'admin_card_edit')]
    public function __invoke(int $id): Response
    {
        $entity = $this->cardRepository->find($id);
        if (!$entity instanceof Card) {
            throw $this->createNotFoundException('Unable to find Card entity.');
        }

        $editForm = $this->createForm(CardType::class, $entity, ['method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($id);
        $forceDeleteForm = $this->createDeleteForm($id);

        return $this->render('Card/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView(), 'force_delete_form' => $forceDeleteForm->createView()]);
    }
}
