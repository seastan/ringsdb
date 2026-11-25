<?php

declare(strict_types=1);

namespace App\Controller\Collection;

use App\Controller\CurrentUserTrait;
use App\Model\SavePacksDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class SavePacksController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/collection/packs/save', name: 'collection_save_packs', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] SavePacksDto $payload = new SavePacksDto()): Response
    {
        $selectedPacks = $payload->selectedPacks;
        // accepts "id" / "id:count" tokens (and legacy "id-2"/"id-3")
        if (preg_match('/[^0-9:,\\-]/', $selectedPacks)) {
            return new Response('Invalid pack selection.');
        }

        $user = $this->currentUser();
        $user->setOwnedPacks($selectedPacks);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->addFlash('notice', 'Collection saved.');

        return $this->forward(GetPacksController::class, ['reloaduser' => true]);
    }
}
