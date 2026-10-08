<?php

declare(strict_types=1);

namespace App\Controller\Tag;

use App\Controller\CurrentUserTrait;
use App\Entity\Deck;
use App\Model\TagDto;
use App\Repository\DeckRepository;
use App\Services\Decks;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class RemoveTagController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private Decks $decks,
        private DeckRepository $deckRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/tag/remove', name: 'tag_remove', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] TagDto $payload = new TagDto()): Response
    {
        $list_id = $payload->ids;
        $list_tag = $this->decks->normalizeTags($payload->tags);
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

            $tags = array_values(array_diff($this->decks->normalizeTags($deck->getTags()), $list_tag));
            $response['tags'][$deck->getId()] = $tags;
            $deck->setTags(implode(' ', $tags));
        }

        $this->entityManager->flush();

        return new JsonResponse($response);
    }
}
