<?php

declare(strict_types=1);

namespace App\Controller\Admin\CardPrinting;

use App\Entity\CardPrinting;
use App\Entity\Pack;
use App\Form\CardPrintingType;
use App\Model\FilterPackDto;
use App\Repository\PackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

class NewCardPrintingController extends AbstractController
{
    use FilterPackTrait;

    public function __construct(
        private readonly PackRepository $packRepository
    ) {
    }

    #[Route(path: '/admin/card-printing/new', name: 'admin_card_printing_new')]
    public function __invoke(#[MapQueryString] FilterPackDto $query = new FilterPackDto()): Response
    {
        $filterPack = $this->resolveFilterPack($query);
        $entity = new CardPrinting();
        $form = $this->createForm(CardPrintingType::class, $entity, ['filter_pack' => $filterPack]);

        return $this->render('CardPrinting/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
            'packs' => $this->packRepository->findBy([], ['name' => 'ASC']),
            'filter_pack' => $filterPack instanceof Pack ? $filterPack->getId() : null,
        ]);
    }
}
