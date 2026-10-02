<?php

declare(strict_types=1);

namespace App\Controller\Collection;

use App\Controller\CurrentUserTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SaveArtController extends AbstractController
{
    use CurrentUserTrait;

    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * Save the user's preferred art (printing) for a card.
     * POST card_code + pack_code; pack_code empty/"default" clears the preference.
     *
     * @Route("/collection/art/save", name="collection_save_art", methods={"POST"})
     */
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return new Response(json_encode(['success' => false, 'error' => 'not logged in']), 403, ['Content-Type' => 'application/json']);
        }
        $cardCode = (string) preg_replace('/[^0-9]/', '', $request->get('card_code'));
        $packCode = (string) preg_replace('/[^A-Za-z0-9_-]/', '', $request->get('pack_code'));
        if (!$cardCode) {
            return new Response(json_encode(['success' => false, 'error' => 'missing card_code']), 400, ['Content-Type' => 'application/json']);
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
        $this->entityManager = $this->getDoctrine()->getManager();
        $user->setArtPreferences(empty($prefs) ? null : (string) json_encode($prefs));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new Response(json_encode(['success' => true]), 200, ['Content-Type' => 'application/json']);
    }
}
