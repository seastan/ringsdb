<?php

declare(strict_types=1);

namespace App\Controller\Admin\Card;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Card;
use App\Form\CardType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UpdateCardController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly string $publicDir,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Edits an existing Card entity.
     */
    #[Route(path: '/admin/card/{id}/update', name: 'admin_card_update', methods: ['POST', 'PUT'])]
    public function __invoke(Request $request, #[MapEntity(message: 'Unable to find Card entity.')] Card $entity, Packages $packages): Response
    {
        $deleteForm = $this->createDeleteForm($entity->getId());
        $forceDeleteForm = $this->createDeleteForm($entity->getId());
        $editForm = $this->createForm(CardType::class, $entity, ['method' => 'PUT']);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            /* @var $file UploadedFile */
            $file = $editForm->get('file')->getData();
            if ($file) {
                $imagedirurl = $packages->getUrl('/bundles/app/images/cards');
                $imagedirpath = $this->publicDir.preg_replace('/\\?.*/', '', $imagedirurl);
                $imagefilename = $entity->getCode().'.png';
                $file->move($imagedirpath, $imagefilename);
            }

            return $this->redirect($this->generateUrl('admin_card_edit', ['id' => $entity->getId()]));
        }

        return $this->render('Card/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView(), 'force_delete_form' => $forceDeleteForm->createView()]);
    }
}
