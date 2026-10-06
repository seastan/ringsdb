<?php

namespace App\Controller;

use App\Repository\SphereRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Entity\Sphere;
use App\Form\SphereType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Sphere controller.
 *
 */
class SphereController extends AbstractController {

    /**
     * @var SphereRepository
     */
    private $sphereRepository;

    public function __construct(SphereRepository $sphereRepository) {
        $this->sphereRepository = $sphereRepository;
    }

    /**
     * Lists all Sphere entities.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {

        $entities = $this->sphereRepository->findAll();

        return $this->render('Sphere/index.html.twig', array(
            'entities' => $entities,
        ));
    }

    /**
     * Creates a new Sphere entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request) {
        $entity = new Sphere();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_sphere_show', array('id' => $entity->getId())));
        }

        return $this->render('Sphere/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Sphere entity.
     *
     * @param Sphere $entity The entity
     *
     * @return \Symfony\Component\Form\FormInterface<Sphere> The form
     */
    private function createCreateForm(Sphere $entity) {
        $form = $this->createForm(SphereType::class, $entity, array(
            'action' => $this->generateUrl('admin_sphere_create'),
            'method' => 'POST',
        ));

        return $form;
    }

    /**
     * Displays a form to create a new Sphere entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction() {
        $entity = new Sphere();
        $form   = $this->createCreateForm($entity);

        return $this->render('Sphere/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Sphere entity.
     *
     * @param mixed $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($id) {

        $entity = $this->sphereRepository->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Sphere entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Sphere/show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Sphere entity.
     *
     * @param mixed $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction($id) {

        $entity = $this->sphereRepository->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Sphere entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Sphere/edit.html.twig', array(
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
    * @return \Symfony\Component\Form\FormInterface<Sphere> The form
    */
    private function createEditForm(Sphere $entity) {
        $form = $this->createForm(SphereType::class, $entity, array(
            'action' => $this->generateUrl('admin_sphere_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing Sphere entity.
     *
     * @param mixed $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $this->sphereRepository->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Sphere entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('admin_sphere_edit', array('id' => $id)));
        }

        return $this->render('Sphere/edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Sphere entity.
     *
     * @param mixed $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $this->sphereRepository->find($id);

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
     * @return \Symfony\Component\Form\FormInterface<mixed> The form
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_sphere_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
