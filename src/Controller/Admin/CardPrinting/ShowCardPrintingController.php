<?php

declare(strict_types=1);

namespace App\Controller\Admin\CardPrinting;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\CardPrinting;
use App\Repository\CardPrintingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowCardPrintingController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly CardPrintingRepository $cardPrintingRepository
    ) {
    }

    #[Route(path: '/admin/card-printing/{id}/show', name: 'admin_card_printing_show')]
    public function __invoke(int $id): Response
    {
        $entity = $this->cardPrintingRepository->find($id);
        if (!$entity instanceof CardPrinting) {
            throw $this->createNotFoundException('Unable to find CardPrinting entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('CardPrinting/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }
}
