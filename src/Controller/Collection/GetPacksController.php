<?php

declare(strict_types=1);

namespace App\Controller\Collection;

use App\Controller\CurrentUserTrait;
use App\Repository\CycleRepository;
use App\Repository\UserCustomPackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GetPacksController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private CycleRepository $cycleRepository,
        private UserCustomPackRepository $userCustomPackRepository
    ) {
    }

    /**
     * @param bool $reloaduser
     */
    #[Route(path: '/collection/packs', name: 'collection_packs', methods: ['GET'])]
    public function __invoke($reloaduser = false): Response
    {
        $categories = [];
        $categories[] = ['label' => 'Core / Deluxe', 'packs' => []];
        $repackaged = ['label' => 'Repackaged', 'packs' => []];
        $list_cycles = $this->cycleRepository->findBy([], ['position' => 'ASC']);
        // owned_packs is a per-pack COUNT map encoded as "id" / "id:count" tokens
        // (legacy "id-2"/"id-3" core copies each count as +1).
        $owned_packs = $this->currentUser()->getOwnedPacks();
        $hasCollection = !empty($owned_packs);
        $countById = [];
        if ($hasCollection) {
            foreach (explode(',', $owned_packs) as $token) {
                $token = trim($token);
                if (preg_match('/^(\\d+):(\\d+)$/', $token, $m)) {
                    $countById[$m[1]] = ($countById[$m[1]] ?? 0) + (int) $m[2];
                } elseif (preg_match('/^(\\d+)(?:-\\d+)?$/', $token, $m)) {
                    $countById[$m[1]] = ($countById[$m[1]] ?? 0) + 1;
                }
            }
        }

        $countOf = function ($pack) use ($hasCollection, $countById): int {
            if ($hasCollection) {
                return $countById[$pack->getId()] ?? 0;
            }

            // no collection set => default to owning one of each released pack
            return null !== $pack->getDateRelease() ? 1 : 0;
        };
        $entryOf = fn ($pack): array => ['code' => $pack->getCode(), 'id' => $pack->getId(), 'label' => $pack->getName(), 'count' => $countOf($pack), 'future' => null === $pack->getDateRelease()];
        foreach ($list_cycles as $cycle) {
            $size = count($cycle->getPacks());
            $first_pack = $cycle->getPacks()->first();
            if (0 == $cycle->getPosition() || false === $first_pack) {
                continue;
            }

            if (1 === $size && $first_pack->getName() == $cycle->getName()) {
                if ($first_pack->getIsRepackaged()) {
                    $repackaged['packs'][] = $entryOf($first_pack);
                } else {
                    $categories[0]['packs'][] = $entryOf($first_pack);
                }
            } else {
                $category = ['label' => $cycle->getName(), 'packs' => []];
                foreach ($cycle->getPacks() as $pack) {
                    if ($pack->getIsRepackaged()) {
                        $repackaged['packs'][] = $entryOf($pack);
                    } else {
                        $category['packs'][] = $entryOf($pack);
                    }
                }

                if (count($category['packs'])) {
                    $categories[] = $category;
                }
            }
        }

        // Repackaged products go in their own section at the end.
        if (count($repackaged['packs'])) {
            $categories[] = $repackaged;
        }

        $customPacks = $this->userCustomPackRepository->findBy(['user' => $this->getUser()], ['createdAt' => 'ASC', 'id' => 'ASC']);

        return $this->render('Collection/packs.html.twig', ['pagetitle' => 'My Collection', 'categories' => $categories, 'reloaduser' => $reloaduser, 'customPacks' => $customPacks]);
    }
}
