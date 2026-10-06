<?php

declare(strict_types=1);

namespace App\Controller\Tag;

use App\Controller\CurrentUserTrait;
use App\Entity\Deck;
use App\Repository\DeckRepository;
use App\Services\Decks;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TagController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(private Decks $decks, private DeckRepository $deckRepository, private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @Route("/tag/add", name="tag_add", methods={"POST"})
     */
    public function addAction(Request $request): Response
    {
        $list_id = $request->get('ids');
        $list_tag = $this->decks->normalizeTags((array) $request->get('tags'));
        /* @var $em EntityManager */
        $response = ['success' => true];
        foreach ($list_id as $id) {
            /* @var $deck Deck */
            $deck = $this->deckRepository->find($id);
            if (!$deck) {
                continue;
            }

            if ($this->currentUser()->getId() != $deck->getUser()->getId()) {
                continue;
            }

            $tags = $this->decks->normalizeTags(array_merge($this->decks->normalizeTags($deck->getTags()), $list_tag));
            $response['tags'][$deck->getId()] = $tags;
            $deck->setTags(implode(' ', $tags));
        }

        $this->entityManager->flush();

        return new JsonResponse($response);
    }

    /**
     * @Route("/tag/remove", name="tag_remove", methods={"POST"})
     */
    public function removeAction(Request $request): Response
    {
        $list_id = $request->get('ids');
        $list_tag = $this->decks->normalizeTags((array) $request->get('tags'));
        /* @var $em EntityManager */
        $response = ['success' => true];
        foreach ($list_id as $id) {
            /* @var $deck Deck */
            $deck = $this->deckRepository->find($id);
            if (!$deck) {
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

    /**
     * @Route("/tag/clear", name="tag_clear", methods={"POST"})
     */
    public function clearAction(Request $request): Response
    {
        $list_id = $request->get('ids');
        /* @var $em EntityManager */
        $response = ['success' => true];
        foreach ($list_id as $id) {
            /* @var $deck Deck */
            $deck = $this->deckRepository->find($id);
            if (!$deck) {
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
