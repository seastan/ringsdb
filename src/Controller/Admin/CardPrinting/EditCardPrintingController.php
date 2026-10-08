<?php

declare(strict_types=1);

namespace App\Controller\Admin\CardPrinting;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\CardPrinting;
use App\Entity\Pack;
use App\Form\CardPrintingType;
use App\Model\FilterPackDto;
use App\Repository\PackRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

class EditCardPrintingController extends AbstractController
{
    use FilterPackTrait;
    use DeleteFormTrait;

    public function __construct(
        private readonly PackRepository $packRepository
    ) {
    }

    #[Route(path: '/admin/card-printing/{id}/edit', name: 'admin_card_printing_edit')]
    public function __invoke(#[MapEntity(message: 'Unable to find CardPrinting entity.')] CardPrinting $entity, #[MapQueryString] FilterPackDto $query = new FilterPackDto()): Response
    {
        $filterPack = $this->resolveFilterPack($query);
        $editForm = $this->createForm(CardPrintingType::class, $entity, ['filter_pack' => $filterPack, 'method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($entity->getId());

        return $this->render('CardPrinting/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView(), 'packs' => $this->packRepository->findBy([], ['name' => 'ASC']), 'filter_pack' => $filterPack instanceof Pack ? $filterPack->getId() : null]);
    }
}
