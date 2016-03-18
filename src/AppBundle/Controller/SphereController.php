<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\Entity\Sphere;
use AppBundle\Form\SphereType;

/**
 * Sphere controller.
 *
 */
class SphereController extends Controller {

    /**
     * Lists all Sphere entities.
     *
     */
    public function indexAction() {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:Sphere')->findAll();

        return $this->render('AppBundle:Sphere:index.html.twig', array(
            'entities' => $entities,
        ));
    }

    /**
     * Creates a new Sphere entity.
     *
     */
    public function createAction(Request $request) {
        $entity = new Sphere();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_sphere_show', array('id' => $entity->getId())));
        }

        return $this->render('AppBundle:Sphere:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Sphere entity.
     *
     * @param Sphere $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Sphere $entity) {
        $form = $this->createForm(new SphereType(), $entity, array(
            'action' => $this->generateUrl('admin_sphere_create'),
            'method' => 'POST',
        ));

        return $form;
    }

    /**
     * Displays a form to create a new Sphere entity.
     *
     */
    public function newAction() {
        $entity = new Sphere();
        $form   = $this->createCreateForm($entity);

        return $this->render('AppBundle:Sphere:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Sphere entity.
     *
     */
    public function showAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Sphere')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Sphere entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Sphere:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Sphere entity.
     *
     */
    public function editAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Sphere')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Sphere entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Sphere:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Sphere entity.
    *
    * @param Sphere $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Sphere $entity) {
        $form = $this->createForm(new SphereType(), $entity, array(
            'action' => $this->generateUrl('admin_sphere_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing Sphere entity.
     *
     */
    public function updateAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Sphere')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Sphere entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('admin_sphere_edit', array('id' => $id)));
        }

        return $this->render('AppBundle:Sphere:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Sphere entity.
     *
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:Sphere')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Sphere entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_sphere'));
    }

    /**
     * Creates a form to delete a Sphere entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_sphere_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
