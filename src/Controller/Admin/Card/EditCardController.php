<?php

declare(strict_types=1);

namespace App\Controller\Admin\Card;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Card;
use App\Form\CardType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EditCardController extends AbstractController
{
    use DeleteFormTrait;

    /**
     * Displays a form to edit an existing Card entity.
     */
    #[Route(path: '/admin/card/{id}/edit', name: 'admin_card_edit')]
    public function __invoke(#[MapEntity(message: 'Unable to find Card entity.')] Card $entity): Response
    {
        $editForm = $this->createForm(CardType::class, $entity, ['method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($entity->getId());
        $forceDeleteForm = $this->createDeleteForm($entity->getId());

        return $this->render('Card/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView(), 'force_delete_form' => $forceDeleteForm->createView()]);
    }
}
