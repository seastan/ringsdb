<?php

declare(strict_types=1);

namespace App\Controller\Admin\CardPrinting;

use App\Entity\CardPrinting;
use App\Repository\PackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexCardPrintingController extends AbstractController
{
    public function __construct(
        private readonly PackRepository $packRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/admin/card-printing/', name: 'admin_card_printing')]
    public function __invoke(Request $request): Response
    {
        $packId = $request->query->get('pack');
        $cardName = $request->query->get('card');
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->select('cp', 'c', 'p')
            ->from(CardPrinting::class, 'cp')
            ->join('cp.card', 'c')
            ->join('cp.pack', 'p')
            ->orderBy('p.dateRelease', \SortDirection::Ascending)
            ->addOrderBy('p.name', \SortDirection::Ascending)
            ->addOrderBy('cp.position', \SortDirection::Ascending);
        if ($packId) {
            $qb->andWhere('p.id = :pack')->setParameter('pack', $packId);
        }

        if ($cardName) {
            $qb->andWhere('c.name LIKE :card')->setParameter('card', '%'.$cardName.'%');
        }

        $entities = $qb->getQuery()->getResult();
        $packs = $this->packRepository->findBy([], ['name' => 'ASC']);

        return $this->render('CardPrinting/index.html.twig', ['entities' => $entities, 'packs' => $packs, 'pack_filter' => $packId, 'card_filter' => $cardName]);
    }
}
