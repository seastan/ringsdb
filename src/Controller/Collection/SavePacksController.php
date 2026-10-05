<?php

declare(strict_types=1);

namespace App\Controller\Collection;

use App\Controller\CurrentUserTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SavePacksController extends AbstractController
{
    use CurrentUserTrait;

    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/collection/packs/save", name="collection_save_packs", methods={"POST"})
     */
    public function __invoke(Request $request): Response
    {
        $selectedPacks = $request->get('selected-packs');
        // accepts "id" / "id:count" tokens (and legacy "id-2"/"id-3")
        if (preg_match('/[^0-9:,\\-]/', $selectedPacks)) {
            return new Response('Invalid pack selection.');
        }

        $user = $this->currentUser();
        $user->setOwnedPacks($selectedPacks);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->get('session')->getFlashBag()->set('notice', 'Collection saved.');

        return $this->forward(GetPacksController::class, ['reloaduser' => true]);
    }
}
