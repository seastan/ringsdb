<?php

declare(strict_types=1);

namespace App\Controller\Collection;

use App\Controller\CurrentUserTrait;
use App\Entity\UserCustomPack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class PublishCustomPackController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/collection/custom-pack/{id}/publish', name: 'collection_custom_pack_publish', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function __invoke(UserCustomPack $pack): RedirectResponse
    {
        if (!$pack->getUser()->isEqualTo($this->currentUser())) {
            throw $this->createNotFoundException();
        }

        $pack->setIsPublished(!$pack->getIsPublished());
        $pack->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($pack);
        $this->entityManager->flush();

        return $this->redirectToRoute('collection_packs');
    }
}
