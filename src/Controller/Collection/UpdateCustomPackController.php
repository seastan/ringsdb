<?php

declare(strict_types=1);

namespace App\Controller\Collection;

use App\Controller\CurrentUserTrait;
use App\Entity\UserCustomPack;
use App\Model\CustomPackDto;
use App\Services\CustomPackManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class UpdateCustomPackController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CustomPackManager $customPackManager
    ) {
    }

    #[Route(path: '/collection/custom-pack/{id}/update', name: 'collection_custom_pack_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function __invoke(UserCustomPack $pack, #[MapRequestPayload] CustomPackDto $payload = new CustomPackDto()): RedirectResponse
    {
        if (!$pack->getUser()->isEqualTo($this->currentUser())) {
            throw $this->createNotFoundException();
        }

        $name = trim($payload->name);
        if ('' === $name) {
            $this->addFlash('error', 'Pack name is required.');

            return $this->redirectToRoute('collection_custom_pack_edit', ['id' => $pack->getId()]);
        }

        $cardsJson = $payload->cardsJson;
        $cardEntries = json_decode($cardsJson, true);
        if (!is_array($cardEntries)) {
            $cardEntries = [];
        }

        $pack->setName($name);
        $pack->setUpdatedAt(new \DateTime());
        $pack->clearCards();

        $this->entityManager->flush();
        // delete old cards before inserting new ones
        $this->customPackManager->attachCards($pack, $cardEntries);
        $this->entityManager->persist($pack);
        $this->entityManager->flush();
        $this->addFlash('notice', 'Custom pack updated.');

        return $this->redirectToRoute('collection_packs');
    }
}
