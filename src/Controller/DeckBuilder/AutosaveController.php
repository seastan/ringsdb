<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Entity\Deckchange;
use App\Entity\User;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;

class AutosaveController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DeckRepository $deckRepository,
        private LoggerInterface $logger
    ) {
    }

    #[Route(path: '/deck/autosave', name: 'deck_autosave', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        /* @var $user User */
        $user = $this->currentUser();
        $deck_id = $request->get('deck_id');
        /* @var $deck \App\Entity\Deck */
        $deck = $this->deckRepository->find($deck_id);
        if (!$deck) {
            throw new UnprocessableEntityHttpException('Cannot find deck '.$deck_id);
        }

        if (!$deck->getUser()->isEqualTo($user)) {
            throw new AccessDeniedHttpException("You don't have access to this deck.");
        }

        // decoded as arrays: count() of an object is a warning since PHP 7.2
        $diff = json_decode((string) $request->get('diff'), true);
        if (!is_array($diff) || 4 !== count($diff) && 2 !== count($diff)) {
            $this->logger->error('cannot use diff', (array) $diff);
            throw new UnprocessableEntityHttpException('Wrong content '.json_encode($diff));
        }

        // [main added, main removed, side added, side removed], the side parts may be missing
        $parts = array_map(fn ($part): int => is_array($part) ? count($part) : 0, $diff);
        if (array_sum($parts) > 0) {
            /* @var $change \App\Entity\Deckchange */
            $change = new Deckchange($deck);
            $change->setVariation((string) json_encode($diff));
            $change->setIsSaved(false);
            $this->entityManager->persist($change);
            $this->entityManager->flush();

            return new Response($change->getDatecreation()->format('c'));
        }

        return new Response();
    }
}
