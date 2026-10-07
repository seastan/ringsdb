<?php

declare(strict_types=1);

namespace App\Controller\Collection;

use App\Controller\CurrentUserTrait;
use App\Entity\UserCustomPack;
use App\Repository\UserCustomPackRepository;
use App\Services\CustomPackManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CopyCustomPackController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserCustomPackRepository $userCustomPackRepository,
        private CustomPackManager $customPackManager
    ) {
    }

    /**
     * @Route(
     *     "/collection/custom-pack/{id}/copy",
     *     name="collection_custom_pack_copy",
     *     methods={"POST"},
     *     requirements={"id"="\d+"}
     * )
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $user = $this->currentUser();

        $source = $this->userCustomPackRepository->findOneBy(['id' => $id, 'isPublished' => true]);
        if (!$source) {
            return new JsonResponse(['error' => 'Pack not found'], 404);
        }

        $copy = new UserCustomPack($user, $source->getName(), 'tmp');

        $this->entityManager->persist($copy);
        $this->entityManager->flush();

        $copy->setCode('custom_'.$copy->getId().'_'.substr(md5(uniqid('', true)), 0, 6));
        $cardEntries = [];
        foreach ($source->getCards() as $entry) {
            $cardEntries[] = ['card_code' => $entry->getCard()->getCode(), 'quantity' => $entry->getQuantity()];
        }

        $this->customPackManager->attachCards($copy, $cardEntries);
        $this->entityManager->persist($copy);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'name' => $copy->getName()]);
    }
}
