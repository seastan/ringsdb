<?php

declare(strict_types=1);

namespace App\Controller\Collection;

use App\Controller\CurrentUserTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SaveArtController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Save the user's preferred art (printing) for a card.
     * POST card_code + pack_code; pack_code empty/"default" clears the preference.
     */
    #[Route(path: '/collection/art/save', name: 'collection_save_art', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            return new JsonResponse(['success' => false, 'error' => 'not logged in'], 403);
        }

        $cardCode = (string) preg_replace('/[^0-9]/', '', $request->request->getString('card_code'));
        $packCode = (string) preg_replace('/[^A-Za-z0-9_-]/', '', $request->request->getString('pack_code'));
        if (!$cardCode) {
            return new JsonResponse(['success' => false, 'error' => 'missing card_code'], 400);
        }

        $prefs = json_decode($user->getArtPreferences() ?: '{}', true);
        if (!is_array($prefs)) {
            $prefs = [];
        }

        if ('' === $packCode || 'default' === $packCode) {
            unset($prefs[$cardCode]);
        } else {
            $prefs[$cardCode] = $packCode;
        }

        $user->setArtPreferences([] === $prefs ? null : (string) json_encode($prefs));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
