<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Controller\CurrentUserTrait;
use App\Entity\Deck;
use App\Helper\StringSanitizer;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use App\Services\DecklistFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

class CreateDecklistController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DeckRepository $deckRepository,
        private DecklistRepository $decklistRepository,
        private DecklistFactory $decklistFactory
    ) {
    }

    /**
     * creates a new decklist from a deck (publish action).
     */
    #[Route(path: '/decklist/create', name: 'decklist_create', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        /* @var $user \App\Entity\User */
        $user = $this->currentUser();

        $deck_id = intval(filter_var($request->request->get('deck_id'), FILTER_SANITIZE_NUMBER_INT));
        /* @var $deck \App\Entity\Deck */
        $deck = $this->deckRepository->find($deck_id);
        if (!$deck instanceof Deck) {
            throw new BadRequestHttpException('Invalid deck_id.');
        }

        if (!$deck->getUser()->isEqualTo($user)) {
            throw $this->createAccessDeniedException('Access denied to this object.');
        }

        $name = StringSanitizer::sanitize($request->request->get('name'), false);
        $descriptionMd = trim((string) $request->request->get('descriptionMd'));
        $precedent_id = trim((string) $request->request->get('precedent'));
        if (!preg_match('/^\\d+$/', $precedent_id)) {
            // route decklist_detail hard-coded
            if (preg_match('/view\\/(\\d+)/', $precedent_id, $matches)) {
                $precedent_id = $matches[1];
            } else {
                $precedent_id = null;
            }
        }

        $precedent = $precedent_id ? $this->decklistRepository->find($precedent_id) : null;
        try {
            /* @var $decklist \App\Entity\Decklist */
            $decklist = $this->decklistFactory->createDecklistFromDeck($deck, $name, $descriptionMd);
        } catch (\Exception $exception) {
            return $this->render('Default/error.html.twig', ['pagetitle' => 'Error', 'error' => $exception]);
        }

        $decklist->setPrecedent($precedent);
        $this->entityManager->persist($decklist);
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('decklist_detail', ['decklist_id' => $decklist->getId(), 'decklist_name' => $decklist->getNameCanonical()]));
    }
}
