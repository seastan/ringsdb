<?php

namespace App\Controller;

use App\Repository\ScenarioRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Entity\Scenario;
use App\Form\ScenarioType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Scenario controller.
 *
 */
class ScenarioController extends AbstractController {
    /**
     * @var ScenarioRepository
     */
    private $scenarioRepository;

    public function __construct(ScenarioRepository $scenarioRepository) {
        $this->scenarioRepository = $scenarioRepository;
    }

    /**
     * Lists all Scenario entities.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {

        $entities = $this->scenarioRepository->findAll();

        return $this->render('Scenario/index.html.twig', [
            'entities' => $entities,
        ]);
    }

    /**
     * Creates a new Scenario entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request) {
        $entity = new Scenario();
        $form = $this->createForm(ScenarioType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
#            $texts = $this->getContainer()->get('texts');
#            $entity->setCanonicalName($texts->slugify($entity->getName()));
            # Set defaults
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

            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_scenario_show', ['id' => $entity->getId()]));
        }

        return $this->render('Scenario/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new Scenario entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction() {
        $entity = new Scenario();
        $form = $this->createForm(ScenarioType::class, $entity);

        return $this->render('Scenario/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Scenario entity.
     *
     * @param mixed $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($id) {

        $entity = $this->scenarioRepository->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Scenario entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Scenario/show.html.twig', [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Scenario entity.
     *
     * @param mixed $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction($id) {

        $entity = $this->scenarioRepository->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Scenario entity.');
        }

        $editForm = $this->createForm(ScenarioType::class, $entity, ['method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Scenario/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Edits an existing Scenario entity.
     *
     * @param mixed $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $this->scenarioRepository->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Scenario entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(ScenarioType::class, $entity, ['method' => 'PUT']);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
#            $texts = $this->getContainer()->get('texts');
#            $entity->setCanonicalName($texts->slugify($entity->getName()));

            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_scenario_edit', ['id' => $id]));
        }

        return $this->render('Scenario/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a Scenario entity.
     *
     * @param mixed $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $this->scenarioRepository->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Scenario entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_scenario'));
    }

    /**
     * Creates a form to delete a Scenario entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\FormInterface<mixed> The form
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', HiddenType::class)
            ->setMethod('DELETE')
            ->getForm();
    }
}
