<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\Entity\Card;
use AppBundle\Form\CardType;

/**
 * Card controller.
 *
 */
class CardController extends Controller {
    /**
     * Lists all Card entities.
     *
     */
    public function indexAction() {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:Card')->findAll();

        return $this->render('AppBundle:Card:index.html.twig', [
            'entities' => $entities,
        ]);
    }

    /**
     * Creates a new Card entity.
     *
     */
    public function createAction(Request $request) {
        $entity = new Card();
        $form = $this->createForm(new CardType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_card_show', ['id' => $entity->getId()]));
        }

        return $this->render('AppBundle:Card:new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to create a new Card entity.
     *
     */
    public function newAction() {
        $entity = new Card();
        $form = $this->createForm(new CardType(), $entity);

        return $this->render('AppBundle:Card:new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Card entity.
     *
     */
    public function showAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Card')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Card entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Card:show.html.twig', [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Card entity.
     *
     */
    public function editAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Card')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Card entity.');
        }

        $editForm = $this->createForm(new CardType(), $entity);
        $deleteForm = $this->createDeleteForm($id);
        $forceDeleteForm = $this->createForceDeleteForm($id);

        return $this->render('AppBundle:Card:edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'force_delete_form' => $forceDeleteForm->createView(),
        ]);
    }

    /**
     * Edits an existing Card entity.
     *
     */
    public function updateAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Card')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Card entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $forceDeleteForm = $this->createForceDeleteForm($id);
        $editForm = $this->createForm(new CardType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            /* @var $file \Symfony\Component\HttpFoundation\File\UploadedFile */
            $file = $editForm['file']->getData();
            if ($file) {
                $imagedirurl = $this->get('templating.helper.assets')->getUrl('/bundles/app/images/cards');
                $imagedirpath = $this->get('kernel')->getRootDir() . '/../web' . preg_replace('/\?.*/', '', $imagedirurl);
                $imagefilename = $entity->getCode() . '.png';
                $file->move($imagedirpath, $imagefilename);
            }

            return $this->redirect($this->generateUrl('admin_card_edit', ['id' => $id]));
        }

        return $this->render('AppBundle:Card:edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'force_delete_form' => $forceDeleteForm->createView(),
        ]);
    }

    /**
     * Deletes a Card entity.
     *
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:Card')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Card entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_card'));
    }

    /**
     * Forcibly deletes a Card entity.
     *
     */
    public function forceDeleteAction(Request $request, $id) {
        $form = $this->createForceDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:Card')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Card entity.');
            }

            /* @var $dbh \Doctrine\DBAL\Connection */
            $dbh = $this->getDoctrine()->getConnection();
            $query = "DELETE FROM deckslot WHERE card_id = " . $id;
            $dbh->executeQuery($query, []);
            $query = "DELETE FROM decksideslot WHERE card_id = " . $id;
            $dbh->executeQuery($query, []);
            $query = "DELETE FROM decklistslot WHERE card_id = " . $id;
            $dbh->executeQuery($query, []);
            $query = "DELETE FROM decklistsideslot WHERE card_id = " . $id;
            $dbh->executeQuery($query, []);

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_card'));
    }

    /**
     * Creates a form to delete a Card entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder(['id' => $id])->add('id', 'hidden')->getForm();
    }

    /**
     * Creates a form to forcibly delete a Card entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createForceDeleteForm($id) {
        return $this->createFormBuilder(['id' => $id])->add('id', 'hidden')->getForm();
    }
}
