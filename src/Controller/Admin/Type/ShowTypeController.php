<?php

declare(strict_types=1);

namespace App\Controller\Admin\Type;

use App\Entity\Type;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowTypeController extends AbstractController
{
    use TypeFormsTrait;

    /**
     * Finds and displays a Type entity.
     */
    #[Route(path: '/admin/type/{id}/show', name: 'admin_type_show')]
    public function __invoke(#[MapEntity(message: 'Unable to find Type entity.')] Type $entity): Response
    {
        $deleteForm = $this->createDeleteForm($entity->getId());

        return $this->render('Type/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }
}
