<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use AppBundle\Entity\Cycle;
use AppBundle\Form\CycleType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Cycle controller.
 *
 */
class CycleController extends AbstractController {
    /**
     * Lists all Cycle entities.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:Cycle')->findAll();

        return $this->render('AppBundle:Cycle:index.html.twig', [
            'entities' => $entities,
        ]);
    }

    /**
     * Creates a new Cycle entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request) {
        $entity = new Cycle();
        $form = $this->createForm(CycleType::class, $entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_cycle_show', ['id' => $entity->getId()]));
        }

        return $this->render('AppBundle:Cycle:new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to create a new Cycle entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction() {
        $entity = new Cycle();
        $form = $this->createForm(CycleType::class, $entity);

        return $this->render('AppBundle:Cycle:new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Cycle entity.
     *
     * @param mixed $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Cycle')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Cycle entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Cycle:show.html.twig', [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Cycle entity.
     *
     * @param mixed $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Cycle')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Cycle entity.');
        }

        $editForm = $this->createForm(CycleType::class, $entity, ['method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Cycle:edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Edits an existing Cycle entity.
     *
     * @param mixed $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Cycle')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Cycle entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(CycleType::class, $entity, ['method' => 'PUT']);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_cycle_edit', ['id' => $id]));
        }

        return $this->render('AppBundle:Cycle:edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a Cycle entity.
     *
     * @param mixed $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:Cycle')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Cycle entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_cycle'));
    }

    /**
     * Creates a form to delete a Cycle entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\FormInterface The form
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder(['id' => $id])->add('id', HiddenType::class)->setMethod('DELETE')->getForm();
    }
}
