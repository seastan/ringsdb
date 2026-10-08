<?php

declare(strict_types=1);

namespace App\Controller\Admin\Pack;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Pack;
use App\Repository\PackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowPackController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly PackRepository $packRepository
    ) {
    }

    /**
     * Finds and displays a Pack entity.
     */
    #[Route(path: '/admin/pack/{id}/show', name: 'admin_pack_show')]
    public function __invoke(int $id): Response
    {
        $entity = $this->packRepository->find($id);
        if (!$entity instanceof Pack) {
            throw $this->createNotFoundException('Unable to find Pack entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Pack/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }
}
