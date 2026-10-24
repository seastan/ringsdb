<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Repository\UserCustomPackRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Custom Pack')]
class ListPublishedCustomPacksController extends AbstractController
{
    public function __construct(
        private readonly UserCustomPackRepository $userCustomPackRepository
    ) {
    }

    /**
     * All the Published Custom Packs.
     *
     * Get the custom packs published by the users, with their cards, as an array of JSON objects.
     */
    #[Route(path: '/api/public/custom-packs/published', name: 'api_public_custom_packs_published', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $packs = $this->userCustomPackRepository->findBy(['isPublished' => true], ['createdAt' => 'ASC', 'id' => 'ASC']);
        $result = [];
        foreach ($packs as $pack) {
            $cards = [];
            foreach ($pack->getCards() as $entry) {
                $card = $entry->getCard();
                $sphere = $card->getSphere();
                $type = $card->getType();
                $cards[] = [
                    'card_code' => $card->getCode(),
                    'card_name' => $card->getName(),
                    'sphere_code' => $sphere ? $sphere->getCode() : null,
                    'type_name' => $type ? $type->getName() : null,
                    'quantity' => $entry->getQuantity(),
                ];
            }

            $result[] = [
                'id' => $pack->getId(),
                'name' => $pack->getName(),
                'owner_name' => $pack->getUser()->getUsername(),
                'cards' => $cards,
            ];
        }

        return new JsonResponse($result);
    }
}
