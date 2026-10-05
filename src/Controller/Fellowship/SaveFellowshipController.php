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
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use App\Repository\FellowshipRepository;
use App\Services\Decks;
use App\Services\Texts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;

class SaveFellowshipController extends AbstractController
{
    use CurrentUserTrait;

    private EntityManagerInterface $entityManager;

    private Texts $texts;

    private Decks $decks;

    private DeckRepository $deckRepository;

    private DecklistRepository $decklistRepository;

    private FellowshipRepository $fellowshipRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        Texts $texts,
        Decks $decks,
        DeckRepository $deckRepository,
        DecklistRepository $decklistRepository,
        FellowshipRepository $fellowshipRepository
    ) {
        $this->entityManager = $entityManager;
        $this->texts = $texts;
        $this->decks = $decks;
        $this->deckRepository = $deckRepository;
        $this->decklistRepository = $decklistRepository;
        $this->fellowshipRepository = $fellowshipRepository;
    }

    /**
     * @Route("/fellowship/save", name="fellowship_save", methods={"POST"})
     */
    public function __invoke(Request $request): RedirectResponse
    {
        /* @var $user User */
        $user = $this->currentUser();
        $fellowship_id = intval(filter_var($request->request->get('fellowship_id'), FILTER_SANITIZE_NUMBER_INT));
        if ($fellowship_id) {
            /* @var $fellowship \App\Entity\Fellowship */
            $fellowship = $this->fellowshipRepository->find($fellowship_id);
            if (!$fellowship) {
                throw new NotFoundHttpException('This fellowship does not exists.');
            }

            if (!$fellowship->getUser()->isEqualTo($user)) {
                throw new AccessDeniedHttpException('Access denied to this object.');
            }
        } else {
            $fellowship = new Fellowship();
            $fellowship->setIsPublic(false);
            $fellowship->setNbVotes(0);
            $fellowship->setNbComments(0);
            $fellowship->setNbFavorites(0);
            $fellowship->setNbDecks(0);
        }

        $name = trim((string) filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $name = substr($name, 0, 60);
        if (empty($name)) {
            $name = 'Untitled Fellowship';
        }

        $auto_publish = boolval(filter_var($request->request->get('auto_publish'), FILTER_SANITIZE_NUMBER_INT));
        $descriptionMd = trim((string) $request->request->get('descriptionMd'));
        $descriptionHtml = $this->texts->markdown($descriptionMd);
        $fellowship->setUser($user);
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
                $deck_id = intval(filter_var($request->request->get('deck'.$i.'_id'), FILTER_SANITIZE_NUMBER_INT));
                $is_decklist = 'true' == filter_var($request->get('deck'.$i.'_is_decklist'), FILTER_SANITIZE_STRING);
                if ($deck_id) {
                    if (!$is_decklist) {
                        /* @var $deck Deck */
                        $deck = $this->deckRepository->find($deck_id);
                        if (!$deck) {
                            throw new NotFoundHttpException('One of the selected decks does not exists.');
                        }

                        $is_owner = $deck->getUser()->isEqualTo($user);
                        if (!$is_owner && !$deck->getUser()->getIsShareDecks()) {
                            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
                        }

                        if (!$is_owner) {
                            $deck = $this->decks->cloneDeck($deck, $user);
                        }

                        $fellowship_deck = new FellowshipDeck();
                        $fellowship_deck->setDeck($deck);
                        $fellowship_deck->setDeckNumber($i - $skip);
                        $fellowship_deck->setFellowship($fellowship);
                        $fellowship->addDeck($fellowship_deck);
                    } else {
                        /* @var $decklist Decklist */
                        $decklist = $this->decklistRepository->find($deck_id);
                        if (!$decklist) {
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
