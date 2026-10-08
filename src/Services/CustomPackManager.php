<?php

namespace App\Services;

use App\Entity\UserCustomPack;
use App\Entity\UserCustomPackCard;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;

class CustomPackManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CardRepository $cardRepository
    ) {
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
            if (!$card instanceof \App\Entity\Card) {
                continue;
            }

            $seen[$code] = true;
            $packCard = new UserCustomPackCard($pack, $card, $qty);
            $pack->addCard($packCard);
            $this->entityManager->persist($packCard);
        }
    }
}
