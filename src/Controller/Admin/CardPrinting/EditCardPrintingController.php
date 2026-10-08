<?php

declare(strict_types=1);

namespace App\Controller\Admin\CardPrinting;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\CardPrinting;
use App\Entity\Pack;
use App\Form\CardPrintingType;
use App\Repository\CardPrintingRepository;
use App\Repository\PackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EditCardPrintingController extends AbstractController
{
    use FilterPackTrait;
    use DeleteFormTrait;

    public function __construct(
        private readonly CardPrintingRepository $cardPrintingRepository,
        private readonly PackRepository $packRepository
    ) {
    }

    #[Route(path: '/admin/card-printing/{id}/edit', name: 'admin_card_printing_edit')]
    public function __invoke(Request $request, int $id): Response
    {
        $entity = $this->cardPrintingRepository->find($id);
        if (!$entity instanceof CardPrinting) {
            throw $this->createNotFoundException('Unable to find CardPrinting entity.');
        }

        $filterPack = $this->resolveFilterPack($request);
        $editForm = $this->createForm(CardPrintingType::class, $entity, ['filter_pack' => $filterPack, 'method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('CardPrinting/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView(), 'packs' => $this->packRepository->findBy([], ['name' => 'ASC']), 'filter_pack' => $filterPack instanceof Pack ? $filterPack->getId() : null]);
    }
}
