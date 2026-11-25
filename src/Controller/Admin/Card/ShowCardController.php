<?php

declare(strict_types=1);

namespace App\Controller\Admin\Card;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Card;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowCardController extends AbstractController
{
    use DeleteFormTrait;

    /**
     * Finds and displays a Card entity.
     */
    #[Route(path: '/admin/card/{id}/show', name: 'admin_card_show')]
    public function __invoke(#[MapEntity(message: 'Unable to find Card entity.')] Card $entity): Response
    {
        $deleteForm = $this->createDeleteForm($entity->getId());

        return $this->render('Card/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }
}
