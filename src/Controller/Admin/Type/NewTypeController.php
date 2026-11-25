<?php

declare(strict_types=1);

namespace App\Controller\Admin\Type;

use App\Entity\Type;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewTypeController extends AbstractController
{
    use TypeFormsTrait;

    /**
     * Displays a form to create a new Type entity.
     */
    #[Route(path: '/admin/type/new', name: 'admin_type_new')]
    public function __invoke(): Response
    {
        $entity = new Type();
        $form = $this->createCreateForm($entity);

        return $this->render('Type/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }
}
