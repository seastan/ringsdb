<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Entity\User;
use App\Repository\FellowshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class DeleteListFellowshipController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private FellowshipRepository $fellowshipRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        FellowshipRepository $fellowshipRepository
    ) {
        $this->entityManager = $entityManager;
        $this->fellowshipRepository = $fellowshipRepository;
    }

    /**
     * @Route("/fellowship/delete_list", name="fellowship_delete_list", methods={"POST"})
     */
    public function __invoke(Request $request): RedirectResponse
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }
        $list_id = explode('-', $request->get('ids'));
        $message = null;
        foreach ($list_id as $id) {
            /* @var $fellowship \App\Entity\Fellowship */
            $fellowship = $this->fellowshipRepository->find($id);
            if (!$fellowship) {
                continue;
            }
            if ($user->getId() != $fellowship->getUser()->getId()) {
                continue;
            }
            if ($fellowship->getNbVotes() || $fellowship->getNbfavorites() || $fellowship->getNbcomments()) {
                $message = "You can't delete a published fellowship. Unpublished selected fellowships were deleted.";
            } else {
                /* @var $decks \App\Entity\FellowshipDeck[] */
                $decks = $fellowship->getDecks();
                foreach ($decks as $deck) {
                    $this->entityManager->remove($deck);
                }
                /* @var $decks \App\Entity\FellowshipDecklist[] */
                $decklists = $fellowship->getDecklists();
                foreach ($decklists as $decklist) {
                    $this->entityManager->remove($decklist);
                }
                $this->entityManager->remove($fellowship);
            }
        }
        $this->entityManager->flush();
        $this->get('session')->getFlashBag()->set('notice', $message ?: 'Fellowships deleted.');

        return $this->redirect($this->generateUrl('myfellowships_list'));
    }
}
