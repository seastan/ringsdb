<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\Decklist;
use App\Helper\DeckValidationHelper;
use App\Model\DecklistFactory;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublishFormDecklistController extends AbstractController
{
    public function __construct(private readonly DeckRepository $deckRepository, private readonly DecklistRepository $decklistRepository, private readonly DecklistFactory $decklistFactory, private readonly DeckValidationHelper $deckValidationHelper)
    {
    }

    /**
     * Checks to see if a deck can be published in its current saved state
     * If it is, displays the decklist edit form for initial publication of a deck.
     *
     * @Route("/deck/publish/{deck_id}", name="deck_publish_form", methods={"GET"})
     */
    public function __invoke(int $deck_id): Response
    {
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in for this operation.');
        }

        $deck = $this->deckRepository->find($deck_id);
        if (!$deck || !$deck->getUser()->isEqualTo($user)) {
            throw $this->createAccessDeniedException("You don't have access to this decklist.");
        }

        $problem = $this->deckValidationHelper->findProblem($deck, true);
        if ($problem) {
            $this->get('session')->getFlashBag()->set('error', 'This deck cannot be published because it is invalid.');

            return $this->redirect($this->generateUrl('deck_view', ['deck_id' => $deck->getId()]));
        }

        $content = ['main' => $deck->getSlots()->getContent(), 'side' => $deck->getSideslots()->getContent()];
        $new_content = (string) json_encode($content);
        $new_signature = md5($new_content);
        $old_decklists = $this->decklistRepository->findBy(['signature' => $new_signature]);
        /* @var $decklist \App\Entity\Decklist */
        foreach ($old_decklists as $decklist) {
            $deck_content = ['main' => $decklist->getSlots()->getContent(), 'side' => $decklist->getSideslots()->getContent()];
            if (json_encode($deck_content) == $new_content) {
                $url = $this->generateUrl('decklist_detail', ['decklist_id' => $decklist->getId(), 'decklist_name' => $decklist->getNameCanonical()]);
                $this->get('session')->getFlashBag()->set('warning', "This deck <a href=\"{$url}\">has already been published</a> before. You are going to create a duplicate.");
            }
        }

        // decklist for the form ; won't be persisted
        $decklist = $this->decklistFactory->createDecklistFromDeck($deck, $deck->getName(), $deck->getDescriptionMd());

        return $this->render('Decklist/decklist_edit.html.twig', ['url' => $this->generateUrl('decklist_create'), 'deck' => $deck, 'decklist' => $decklist]);
    }
}
