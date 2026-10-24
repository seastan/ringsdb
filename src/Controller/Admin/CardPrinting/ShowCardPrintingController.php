<?php

declare(strict_types=1);

namespace App\Controller\Admin\CardPrinting;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\CardPrinting;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowCardPrintingController extends AbstractController
{
    use DeleteFormTrait;

    #[Route(path: '/admin/card-printing/{id}/show', name: 'admin_card_printing_show')]
    public function __invoke(#[MapEntity(message: 'Unable to find CardPrinting entity.')] CardPrinting $entity): Response
    {
        $deleteForm = $this->createDeleteForm($entity->getId());

        return $this->render('CardPrinting/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }
}
