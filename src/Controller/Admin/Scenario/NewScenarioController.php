<?php

declare(strict_types=1);

namespace App\Controller\Admin\Scenario;

use App\Entity\Scenario;
use App\Form\ScenarioType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewScenarioController extends AbstractController
{
    /**
     * Displays a form to create a new Scenario entity.
     */
    #[Route(path: '/admin/scenario/new', name: 'admin_scenario_new')]
    public function __invoke(): Response
    {
        $entity = new Scenario();
        $form = $this->createForm(ScenarioType::class, $entity);

        return $this->render('Scenario/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }
}
