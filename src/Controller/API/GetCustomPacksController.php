<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Repository\UserCustomPackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class GetCustomPacksController extends AbstractController
{
    public function __construct(
        private readonly UserCustomPackRepository $userCustomPackRepository
    ) {
    }

    #[Route(path: '/api/private/custom-packs', name: 'api_private_custom_packs', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            return new JsonResponse([], 401);
        }

        $packs = $this->userCustomPackRepository->findBy(['user' => $user], ['createdAt' => 'ASC', 'id' => 'ASC']);
        $result = [];
        foreach ($packs as $pack) {
            $cards = [];
            foreach ($pack->getCards() as $entry) {
                $card = $entry->getCard();
                $cards[] = [
                    'card_code' => $card->getCode(),
                    'card_name' => $card->getName(),
                    'type_code' => $card->getType() ? $card->getType()->getCode() : null,
                    'quantity' => $entry->getQuantity(),
                ];
            }

            $result[] = [
                'id' => $pack->getId(),
                'code' => $pack->getCode(),
                'name' => $pack->getName(),
                'is_enabled' => $pack->getIsEnabled(),
                'cards' => $cards,
            ];
        }

        return new JsonResponse($result);
    }
}
