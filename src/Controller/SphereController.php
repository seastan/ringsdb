<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Sphere;
use App\Form\SphereType;
use App\Repository\SphereRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Sphere controller.
 */
class SphereController extends AbstractController
{
    /**
     * @var SphereRepository
     */
    private $sphereRepository;

    public function __construct(SphereRepository $sphereRepository)
    {
        $this->sphereRepository = $sphereRepository;
    }

    /**
     * Lists all Sphere entities.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/sphere/", name="admin_sphere")
     */
    public function indexAction()
    {
        $entities = $this->sphereRepository->findAll();

        return $this->render('Sphere/index.html.twig', ['entities' => $entities]);
    }

    /**
     * Creates a new Sphere entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/sphere/create", name="admin_sphere_create", methods={"POST"})
     */
    public function createAction(Request $request)
    {
        $entity = new Sphere();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_sphere_show', ['id' => $entity->getId()]));
        }

        return $this->render('Sphere/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Creates a form to create a Sphere entity.
     *
     * @param Sphere $entity The entity
     *
     * @return \Symfony\Component\Form\FormInterface<Sphere> The form
     */
    private function createCreateForm(Sphere $entity)
    {
        $form = $this->createForm(SphereType::class, $entity, ['action' => $this->generateUrl('admin_sphere_create'), 'method' => 'POST']);

        return $form;
    }

    /**
     * Displays a form to create a new Sphere entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/sphere/new", name="admin_sphere_new")
     */
    public function newAction()
    {
        $entity = new Sphere();
        $form = $this->createCreateForm($entity);

        return $this->render('Sphere/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Finds and displays a Sphere entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/sphere/{id}/show", name="admin_sphere_show")
     */
    public function showAction($id)
    {
        $entity = $this->sphereRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Sphere entity.');
        }
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Sphere/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Displays a form to edit an existing Sphere entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/sphere/{id}/edit", name="admin_sphere_edit")
     */
    public function editAction($id)
    {
        $entity = $this->sphereRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Sphere entity.');
        }
        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Sphere/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Creates a form to edit a Sphere entity.
     *
     * @param Sphere $entity The entity
     *
     * @return \Symfony\Component\Form\FormInterface<Sphere> The form
     */
    private function createEditForm(Sphere $entity)
    {
        $form = $this->createForm(SphereType::class, $entity, ['action' => $this->generateUrl('admin_sphere_update', ['id' => $entity->getId()]), 'method' => 'PUT']);
        $form->add('submit', SubmitType::class, ['label' => 'Update']);

        return $form;
    }

    /**
     * Edits an existing Sphere entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/sphere/{id}/update", name="admin_sphere_update", methods={"POST", "PUT"})
     */
    public function updateAction(Request $request, $id)
    {
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

            return $this->redirect($this->generateUrl('admin_sphere_edit', ['id' => $id]));
        }

        return $this->render('Sphere/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Deletes a Sphere entity.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/admin/sphere/{id}/delete", name="admin_sphere_delete", methods={"POST", "DELETE"})
     */
    public function deleteAction(Request $request, $id)
    {
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
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()->setAction($this->generateUrl('admin_sphere_delete', ['id' => $id]))->setMethod('DELETE')->getForm();
    }
}
