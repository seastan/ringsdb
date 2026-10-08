<?php

declare(strict_types=1);

namespace App\Controller\Admin\Type;

use App\Entity\Type;
use App\Repository\TypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowTypeController extends AbstractController
{
    use TypeFormsTrait;

    public function __construct(
        private readonly TypeRepository $typeRepository
    ) {
    }

    /**
     * Finds and displays a Type entity.
     */
    #[Route(path: '/admin/type/{id}/show', name: 'admin_type_show')]
    public function __invoke(int $id): Response
    {
        $entity = $this->typeRepository->find($id);
        if (!$entity instanceof Type) {
            throw $this->createNotFoundException('Unable to find Type entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Type/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }
}
