<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\Entity\Scenario;
use AppBundle\Form\ScenarioType;

/**
 * Scenario controller.
 *
 */
class ScenarioController extends Controller {
    /**
     * Lists all Scenario entities.
     *
     */
    public function indexAction() {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:Scenario')->findAll();

        return $this->render('AppBundle:Scenario:index.html.twig', [
            'entities' => $entities,
        ]);
    }

    /**
     * Creates a new Scenario entity.
     *
     */
    public function createAction(Request $request) {
        $entity = new Scenario();
        $form = $this->createForm(new ScenarioType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
#            $texts = $this->getContainer()->get('texts');
#            $entity->setCanonicalName($texts->slugify($entity->getName()));

            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_scenario_show', ['id' => $entity->getId()]));
        }

        return $this->render('AppBundle:Scenario:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new Scenario entity.
     *
     */
    public function newAction() {
        $entity = new Scenario();
        $form = $this->createForm(new ScenarioType(), $entity);

        return $this->render('AppBundle:Scenario:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Scenario entity.
     *
     */
    public function showAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Scenario')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Scenario entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Scenario:show.html.twig', [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Scenario entity.
     *
     */
    public function editAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Scenario')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Scenario entity.');
        }

        $editForm = $this->createForm(new ScenarioType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Scenario:edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Edits an existing Scenario entity.
     *
     */
    public function updateAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Scenario')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Scenario entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new ScenarioType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
#            $texts = $this->getContainer()->get('texts');
#            $entity->setCanonicalName($texts->slugify($entity->getName()));

            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_scenario_edit', ['id' => $id]));
        }

        return $this->render('AppBundle:Scenario:edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a Scenario entity.
     *
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:Scenario')->find($id);

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
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm();
    }
}
