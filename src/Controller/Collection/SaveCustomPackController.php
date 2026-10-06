<?php

declare(strict_types=1);

namespace App\Controller\Collection;

use App\Controller\CurrentUserTrait;
use App\Entity\UserCustomPack;
use App\Services\CustomPackManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SaveCustomPackController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(private EntityManagerInterface $entityManager, private CustomPackManager $customPackManager)
    {
    }

    /**
     * @Route("/collection/custom-pack/save", name="collection_custom_pack_save", methods={"POST"})
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $this->currentUser();
        $name = trim($request->get('name', ''));
        if ('' === $name) {
            $this->get('session')->getFlashBag()->set('error', 'Pack name is required.');

            return $this->redirectToRoute('collection_custom_pack_new');
        }

        $cardsJson = $request->get('cards_json', '[]');
        $cardEntries = json_decode($cardsJson, true);
        if (!is_array($cardEntries)) {
            $cardEntries = [];
        }

        $pack = new UserCustomPack($user, $name, 'tmp');

        $this->entityManager->persist($pack);
        $this->entityManager->flush();

        $pack->setCode('custom_'.$pack->getId().'_'.substr(md5(uniqid('', true)), 0, 6));
        $this->customPackManager->attachCards($pack, $cardEntries);
        $this->entityManager->persist($pack);
        $this->entityManager->flush();
        $this->get('session')->getFlashBag()->set('notice', 'Custom pack "'.$name.'" created.');

        return $this->redirectToRoute('collection_packs');
    }
}
