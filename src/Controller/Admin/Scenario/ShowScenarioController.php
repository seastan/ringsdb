<?php

declare(strict_types=1);

namespace App\Controller\Admin\Scenario;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Scenario;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowScenarioController extends AbstractController
{
    use DeleteFormTrait;

    /**
     * Finds and displays a Scenario entity.
     */
    #[Route(path: '/admin/scenario/{id}/show', name: 'admin_scenario_show')]
    public function __invoke(#[MapEntity(message: 'Unable to find Scenario entity.')] Scenario $entity): Response
    {
        $deleteForm = $this->createDeleteForm($entity->getId());

        return $this->render('Scenario/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }
}
