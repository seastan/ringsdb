<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Repository\UserCustomPackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class GetCustomPacksController extends AbstractController
{
    private UserCustomPackRepository $userCustomPackRepository;

    public function __construct(
        UserCustomPackRepository $userCustomPackRepository
    ) {
        $this->userCustomPackRepository = $userCustomPackRepository;
    }

    /**
     * @Route("/api/private/custom-packs", name="api_private_custom_packs", methods={"GET"})
     */
    public function __invoke(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([], 401);
        }
        $packs = $this->userCustomPackRepository->findBy(['user' => $user], ['createdAt' => 'ASC', 'id' => 'ASC']);
        $result = [];
        foreach ($packs as $pack) {
            $cards = [];
            foreach ($pack->getCards() as $entry) {
                $card = $entry->getCard();
                $cards[] = ['card_code' => $card->getCode(), 'card_name' => $card->getName(), 'type_code' => $card->getType()->getCode(), 'quantity' => $entry->getQuantity()];
            }
            $result[] = ['id' => $pack->getId(), 'code' => $pack->getCode(), 'name' => $pack->getName(), 'is_enabled' => $pack->getIsEnabled(), 'cards' => $cards];
        }

        return new JsonResponse($result);
    }
}
