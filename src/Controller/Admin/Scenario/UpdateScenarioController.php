<?php

declare(strict_types=1);

namespace App\Controller\Admin\Scenario;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Scenario;
use App\Form\ScenarioType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UpdateScenarioController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Edits an existing Scenario entity.
     */
    #[Route(path: '/admin/scenario/{id}/update', name: 'admin_scenario_update', methods: ['POST', 'PUT'])]
    public function __invoke(Request $request, #[MapEntity(message: 'Unable to find Scenario entity.')] Scenario $entity): Response
    {
        $deleteForm = $this->createDeleteForm($entity->getId());
        $editForm = $this->createForm(ScenarioType::class, $entity, ['method' => 'PUT']);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            //            $texts = $this->getContainer()->get('texts');
            //            $entity->setCanonicalName($texts->slugify($entity->getName()));
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_scenario_edit', ['id' => $entity->getId()]));
        }

        return $this->render('Scenario/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
