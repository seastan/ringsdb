<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Entity\FellowshipDecklist;
use App\Entity\User;
use App\Helper\FellowshipValidationHelper;
use App\Helper\StringSanitizer;
use App\Model\DecklistFactory;
use App\Repository\DecklistRepository;
use App\Repository\FellowshipRepository;
use App\Services\Texts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class PublishFellowshipController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FellowshipValidationHelper $fellowshipValidationHelper,
        private readonly Texts $texts,
        private readonly DecklistFactory $decklistFactory,
        private readonly DecklistRepository $decklistRepository,
        private readonly FellowshipRepository $fellowshipRepository
    ) {
    }

    /**
     * @Route("/fellowship/publish", name="fellowship_publish", methods={"POST"})
     */
    public function __invoke(Request $request): RedirectResponse
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }

        $fellowship_id = intval(filter_var($request->request->get('fellowship_id'), FILTER_SANITIZE_NUMBER_INT));
        /* @var $fellowship \App\Entity\Fellowship */
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship || !$fellowship->getUser()->isEqualTo($user)) {
            throw new AccessDeniedHttpException("You don't have access to this fellowship.");
        }

        if ($fellowship->getIsPublic()) {
            $this->addFlash('error', 'This fellowship is already published.');

            return $this->redirect($this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship->getId()]));
        }

        $name = trim(StringSanitizer::sanitize($request->request->get('name'), false));
        $name = substr($name, 0, 60);
        if (empty($name)) {
            $name = 'Untitled Fellowship';
        }

        $descriptionMd = trim((string) $request->request->get('descriptionMd'));
        $descriptionHtml = $this->texts->markdown($descriptionMd);
        $fellowship->setName($name);
        $fellowship->setNameCanonical($this->texts->slugify($name));
        $fellowship->setDescriptionMd($descriptionMd);
        $fellowship->setDescriptionHtml($descriptionHtml);
        $fellowship->setDateUpdate(new \DateTime());
        $fellowship->setIsPublic(true);
        $fellowship->setDatePublish(new \DateTime());
        foreach ($fellowship->getDecks() as &$fellowship_deck) {
            /* @var $fellowship_deck \App\Entity\FellowshipDeck */
            $new_id = intval(filter_var($request->request->get('deck_selection_'.$fellowship_deck->getDeckNumber()), FILTER_SANITIZE_NUMBER_INT));
            if ($new_id) {
                $decklist = $this->decklistRepository->find($new_id);
                if (!$decklist) {
                    throw new NotFoundHttpException('One of the selected decks does not exists.');
                }
            } else {
                $deck = $fellowship_deck->getDeck();
                $decklist = $this->decklistFactory->createDecklistFromDeck($deck, $deck->getName(), $deck->getDescriptionMd());
                $this->entityManager->persist($decklist);
            }

            $fellowship_decklist = new FellowshipDecklist($decklist, $fellowship);
            $fellowship_decklist->setDeckNumber($fellowship_deck->getDeckNumber());
            $this->entityManager->remove($fellowship_deck);
            $fellowship->removeDeck($fellowship_deck);
            $fellowship->addDecklist($fellowship_decklist);
        }

        // Validate fellowship
        $problem = $this->fellowshipValidationHelper->findProblem($fellowship);
        if ($problem) {
            $this->addFlash('error', 'This fellowship cannot be published because it is invalid.');

            return $this->redirect($this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship->getId()]));
        }

        $this->entityManager->persist($fellowship);
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship->getId(), 'fellowship_name' => $fellowship->getNameCanonical()]));
    }
}
