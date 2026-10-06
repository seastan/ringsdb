<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Entity\Decklist;
use App\Entity\User;
use App\Helper\FellowshipValidationHelper;
use App\Repository\DecklistRepository;
use App\Repository\FellowshipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class PublishFormFellowshipController extends AbstractController
{
    public function __construct(private FellowshipValidationHelper $fellowshipValidationHelper, private DecklistRepository $decklistRepository, private FellowshipRepository $fellowshipRepository)
    {
    }

    /**
     * @Route(
     *     "/fellowship/publish/{fellowship_id}",
     *     name="fellowship_publish_form",
     *     methods={"GET"},
     *     requirements={"fellowship_id"="\d+"}
     * )
     */
    public function __invoke(int $fellowship_id): Response
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }

        /* @var $fellowship \App\Entity\Fellowship */
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship || !$fellowship->getUser()->isEqualTo($user)) {
            throw new AccessDeniedHttpException("You don't have access to this fellowship.");
        }

        $problem = $this->fellowshipValidationHelper->findProblem($fellowship);
        if ($problem) {
            $this->get('session')->getFlashBag()->set('error', 'This fellowship cannot be published because it is invalid.');

            return $this->redirect($this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship->getId()]));
        }

        if ($fellowship->getIsPublic()) {
            $this->get('session')->getFlashBag()->set('error', 'This fellowship is already published.');

            return $this->redirect($this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship->getId()]));
        }

        /** @var array<string, mixed> $data */
        $data = ['pagetitle' => 'Publish Fellowship', 'deck1' => null, 'deck2' => null, 'deck3' => null, 'deck4' => null, 'deck1_duplicates' => [], 'deck2_duplicates' => [], 'deck3_duplicates' => [], 'deck4_duplicates' => [], 'deck1_match' => null, 'deck2_match' => null, 'deck3_match' => null, 'deck4_match' => null, 'deck1_signature' => null, 'deck2_signature' => null, 'deck3_signature' => null, 'deck4_signature' => null, 'fellowship' => $fellowship];
        /* @var $fellowship_decks \App\Entity\FellowshipDeck[] */
        $fellowship_decks = $fellowship->getDecks();
        foreach ($fellowship_decks as &$fellowship_deck) {
            $deck = $fellowship_deck->getDeck();
            if ($deck->getMajorVersion() > 0 && 1 == $deck->getMinorVersion()) {
                // There may be a perfect copy published
                /* @var $pub Decklist */
                $pub = $deck->getChildren()->first();
                if ($pub) {
                    $data['deck'.$fellowship_deck->getDeckNumber().'_match'] = $pub->getId();
                }
            }

            // Finding duplicates
            $content = ['main' => $deck->getSlots()->getContent(), 'side' => $deck->getSideslots()->getContent()];
            $this_content = json_encode($content);
            $this_signature = md5((string) $this_content);
            $old_decklists = $this->decklistRepository->findBy(['signature' => $this_signature]);
            foreach ($old_decklists as $decklist) {
                /* @var $decklist Decklist */
                if ($decklist->getParent() && $decklist->getParent()->getId() == $deck->getId()) {
                    continue;
                }

                $deck_content = ['main' => $decklist->getSlots()->getContent(), 'side' => $decklist->getSideslots()->getContent()];
                if (json_encode($deck_content) == $this_content) {
                    $data['deck'.$fellowship_deck->getDeckNumber().'_duplicates'][] = $decklist;
                }
            }

            $data['deck'.$fellowship_deck->getDeckNumber()] = $fellowship_deck->getDeck();
            $data['deck'.$fellowship_deck->getDeckNumber().'_signature'] = $this_signature;
        }

        /* @var $fellowship_decks \App\Entity\FellowshipDecklist[] */
        $fellowship_decklists = $fellowship->getDecklists();
        foreach ($fellowship_decklists as &$fellowship_decklist) {
            $data['deck'.$fellowship_decklist->getDeckNumber()] = $fellowship_decklist->getDecklist();
        }

        return $this->render('Fellowship/publish.html.twig', $data);
    }
}
