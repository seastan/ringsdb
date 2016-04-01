<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\Entity\Encounter;
use AppBundle\Form\EncounterType;

/**
 * Encounter controller.
 *
 */
class EncounterController extends Controller {
    /**
     * Lists all Encounter entities.
     *
     */
    public function indexAction() {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:Encounter')->findAll();

        return $this->render('AppBundle:Encounter:index.html.twig', [
            'entities' => $entities,
        ]);
    }

    /**
     * Creates a new Encounter entity.
     *
     */
    public function createAction(Request $request) {
        $entity = new Encounter();
        $form = $this->createForm(new EncounterType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_encounter_show', ['id' => $entity->getId()]));
        }

        return $this->render('AppBundle:Encounter:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new Encounter entity.
     *
     */
    public function newAction() {
        $entity = new Encounter();
        $form = $this->createForm(new EncounterType(), $entity);

        return $this->render('AppBundle:Encounter:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Encounter entity.
     *
     */
    public function showAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Encounter')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Encounter entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Encounter:show.html.twig', [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Encounter entity.
     *
     */
    public function editAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Encounter')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Encounter entity.');
        }

        $editForm = $this->createForm(new EncounterType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Encounter:edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Edits an existing Encounter entity.
     *
     */
    public function updateAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Encounter')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Encounter entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new EncounterType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_encounter_edit', ['id' => $id]));
        }

        return $this->render('AppBundle:Encounter:edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a Encounter entity.
     *
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:Encounter')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Encounter entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_encounter'));
    }

    /**
     * Creates a form to delete a Encounter entity by id.
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
