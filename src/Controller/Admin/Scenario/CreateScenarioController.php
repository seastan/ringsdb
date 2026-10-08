<?php

declare(strict_types=1);

namespace App\Controller\Admin\Scenario;

use App\Entity\Scenario;
use App\Form\ScenarioType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreateScenarioController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Creates a new Scenario entity.
     */
    #[Route(path: '/admin/scenario/create', name: 'admin_scenario_create', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $entity = new Scenario();
        $form = $this->createForm(ScenarioType::class, $entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //            $texts = $this->getContainer()->get('texts');
            //            $entity->setCanonicalName($texts->slugify($entity->getName()));
            // Set defaults
            $entity->setNameCanonical('');
            $entity->setHasEasy(true);
            $entity->setHasNightmare(false);
            $entity->setEasyCards(0);
            $entity->setEasyEnemies(0);
            $entity->setEasyLocations(0);
            $entity->setEasyTreacheries(0);
            $entity->setEasyShadows(0);
            $entity->setEasyObjectives(0);
            $entity->setEasyObjectiveAllies(0);
            $entity->setEasyObjectiveLocations(0);
            $entity->setEasySurges(0);
            $entity->setEasyEncounterSideQuests(0);
            $entity->setNormalCards(0);
            $entity->setNormalEnemies(0);
            $entity->setNormalLocations(0);
            $entity->setNormalTreacheries(0);
            $entity->setNormalShadows(0);
            $entity->setNormalObjectives(0);
            $entity->setNormalObjectiveAllies(0);
            $entity->setNormalObjectiveLocations(0);
            $entity->setNormalSurges(0);
            $entity->setNormalEncounterSideQuests(0);
            $entity->setNightmareCards(0);
            $entity->setNightmareEnemies(0);
            $entity->setNightmareLocations(0);
            $entity->setNightmareTreacheries(0);
            $entity->setNightmareShadows(0);
            $entity->setNightmareObjectives(0);
            $entity->setNightmareObjectiveAllies(0);
            $entity->setNightmareObjectiveLocations(0);
            $entity->setNightmareSurges(0);
            $entity->setNightmareEncounterSideQuests(0);
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_scenario_show', ['id' => $entity->getId()]));
        }

        return $this->render('Scenario/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }
}
