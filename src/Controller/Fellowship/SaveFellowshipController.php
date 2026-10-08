<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Controller\CurrentUserTrait;
use App\Entity\Deck;
use App\Entity\Decklist;
use App\Entity\Fellowship;
use App\Entity\FellowshipDeck;
use App\Entity\FellowshipDecklist;
use App\Entity\User;
use App\Helper\StringSanitizer;
use App\Model\SaveFellowshipDto;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use App\Repository\FellowshipRepository;
use App\Services\DeckSaver;
use App\Services\Texts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;

class SaveFellowshipController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Texts $texts,
        private DeckSaver $deckSaver,
        private DeckRepository $deckRepository,
        private DecklistRepository $decklistRepository,
        private FellowshipRepository $fellowshipRepository
    ) {
    }

    #[Route(path: '/fellowship/save', name: 'fellowship_save', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] SaveFellowshipDto $payload = new SaveFellowshipDto()): RedirectResponse
    {
        /* @var $user User */
        $user = $this->currentUser();
        $fellowship_id = intval(filter_var($payload->fellowshipId, FILTER_SANITIZE_NUMBER_INT));
        if ($fellowship_id) {
            /* @var $fellowship \App\Entity\Fellowship */
            $fellowship = $this->fellowshipRepository->find($fellowship_id);
            if (!$fellowship instanceof Fellowship) {
                throw new NotFoundHttpException('This fellowship does not exists.');
            }

            if (!$fellowship->getUser()->isEqualTo($user)) {
                throw new AccessDeniedHttpException('Access denied to this object.');
            }
        } else {
            $fellowship = new Fellowship($user);
        }

        $name = trim(StringSanitizer::sanitize($payload->name, false));
        $name = substr($name, 0, 60);
        if (empty($name)) {
            $name = 'Untitled Fellowship';
        }

        $auto_publish = boolval(filter_var($payload->autoPublish, FILTER_SANITIZE_NUMBER_INT));
        $descriptionMd = trim((string) $payload->descriptionMd);
        $descriptionHtml = $this->texts->markdown($descriptionMd);
        $fellowship->setName($name);
        $fellowship->setNameCanonical($this->texts->slugify($name));
        $fellowship->setDescriptionMd($descriptionMd);
        $fellowship->setDescriptionHtml($descriptionHtml);

        $is_public = $fellowship->getIsPublic();
        if (!$is_public) {
            // Allow deck changing
            foreach ($fellowship->getDecks() as $deck) {
                $fellowship->removeDeck($deck);
                $this->entityManager->remove($deck);
            }

            foreach ($fellowship->getDecklists() as $deck) {
                $fellowship->removeDecklist($deck);
                $this->entityManager->remove($deck);
            }

            $nb_decks = 0;
            $skip = 0;
            for ($i = 1; $i <= 4; ++$i) {
                $deck_id = intval(filter_var($payload->deckId($i), FILTER_SANITIZE_NUMBER_INT));
                $is_decklist = 'true' === StringSanitizer::sanitize($payload->deckIsDecklist($i));
                if ($deck_id) {
                    if (!$is_decklist) {
                        /* @var $deck Deck */
                        $deck = $this->deckRepository->find($deck_id);
                        if (!$deck instanceof Deck) {
                            throw new NotFoundHttpException('One of the selected decks does not exists.');
                        }

                        $is_owner = $deck->getUser()->isEqualTo($user);
                        if (!$is_owner && !$deck->getUser()->getIsShareDecks()) {
                            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
                        }

                        if (!$is_owner) {
                            $deck = $this->deckSaver->cloneDeck($user, $deck);
                        }

                        $fellowship_deck = new FellowshipDeck($fellowship, $deck, $i - $skip);
                        $fellowship->addDeck($fellowship_deck);
                    } else {
                        /* @var $decklist Decklist */
                        $decklist = $this->decklistRepository->find($deck_id);
                        if (!$decklist instanceof Decklist) {
                            throw new NotFoundHttpException('One of the selected decks does not exists.');
                        }

                        $fellowship_decklist = new FellowshipDecklist($decklist, $fellowship);
                        $fellowship_decklist->setDeckNumber($i - $skip);
                        $fellowship->addDecklist($fellowship_decklist);
                    }

                    ++$nb_decks;
                } else {
                    ++$skip;
                }
            }

            if (0 === $nb_decks) {
                throw new UnprocessableEntityHttpException("You can't save an empty fellowship.");
            }

            $fellowship->setNbDecks($nb_decks);
        }

        if ($auto_publish && $fellowship->getDecks()->isEmpty()) {
            $fellowship->setIsPublic(true);
            $fellowship->setDatePublish(new \DateTime());
        }

        $this->entityManager->persist($fellowship);
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship->getId()]));
    }
}
