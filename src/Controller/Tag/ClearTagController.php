<?php

declare(strict_types=1);

namespace App\Controller\Tag;

use App\Controller\CurrentUserTrait;
use App\Entity\Deck;
use App\Model\TagDto;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class ClearTagController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private DeckRepository $deckRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/tag/clear', name: 'tag_clear', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] TagDto $payload = new TagDto()): Response
    {
        $list_id = $payload->ids;
        /* @var $em EntityManager */
        $response = ['success' => true];
        foreach ($list_id as $id) {
            /* @var $deck Deck */
            $deck = $this->deckRepository->find($id);
            if (!$deck instanceof Deck) {
                continue;
            }

            if ($this->currentUser()->getId() != $deck->getUser()->getId()) {
                continue;
            }

            $response['tags'][$deck->getId()] = [];
            $deck->setTags('');
        }

        $this->entityManager->flush();

        return new JsonResponse($response);
    }
}
