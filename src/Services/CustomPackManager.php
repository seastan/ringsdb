<?php

namespace App\Services;

use App\Entity\User;
use App\Entity\UserCustomPack;
use App\Entity\UserCustomPackCard;
use App\Repository\CardRepository;
use App\Repository\UserCustomPackRepository;
use Doctrine\ORM\EntityManagerInterface;

class CustomPackManager
{
    public function __construct(private EntityManagerInterface $entityManager, private CardRepository $cardRepository, private UserCustomPackRepository $userCustomPackRepository)
    {
    }

    /**
     * @param array<int|string, mixed> $cardEntries
     */
    public function attachCards(UserCustomPack $pack, array $cardEntries): void
    {
        $cardRepo = $this->cardRepository;
        $seen = [];
        foreach ($cardEntries as $entry) {
            $code = isset($entry['card_code']) ? (string) preg_replace('/[^0-9]/', '', $entry['card_code']) : '';
            $qty = isset($entry['quantity']) ? (int) $entry['quantity'] : 1;
            if ('' === $code || $qty < 1 || $qty > 9 || isset($seen[$code])) {
                continue;
            }

            $card = $cardRepo->findOneBy(['code' => $code]);
            if (!$card) {
                continue;
            }

            $seen[$code] = true;
            $packCard = new UserCustomPackCard($pack, $card, $qty);
            $pack->addCard($packCard);
            $this->entityManager->persist($packCard);
        }
    }

    public function loadOwnedPack(User $user, int $id): ?UserCustomPack
    {
        $pack = $this->userCustomPackRepository->find($id);
        if (!$pack || !$pack->getUser()->isEqualTo($user)) {
            return null;
        }

        return $pack;
    }
}
