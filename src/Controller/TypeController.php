<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Type;
use App\Form\TypeType;
use App\Repository\TypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Type controller.
 */
class TypeController extends AbstractController
{
    /**
     * @var TypeRepository
     */
    private $typeRepository;

    public function __construct(TypeRepository $typeRepository)
    {
        $this->typeRepository = $typeRepository;
    }

    /**
     * Lists all Type entities.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/type/", name="admin_type")
     */
    public function indexAction()
    {
        $entities = $this->typeRepository->findAll();

        return $this->render('Type/index.html.twig', ['entities' => $entities]);
    }

    /**
     * Creates a new Type entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/type/create", name="admin_type_create", methods={"POST"})
     */
    public function createAction(Request $request)
    {
        $entity = new Type();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_type_show', ['id' => $entity->getId()]));
        }

        return $this->render('Type/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Creates a form to create a Type entity.
     *
     * @param Type $entity The entity
     *
     * @return \Symfony\Component\Form\FormInterface<Type> The form
     */
    private function createCreateForm(Type $entity)
    {
        $form = $this->createForm(TypeType::class, $entity, ['action' => $this->generateUrl('admin_type_create'), 'method' => 'POST']);

        return $form;
    }

    /**
     * Displays a form to create a new Type entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/type/new", name="admin_type_new")
     */
    public function newAction()
    {
        $entity = new Type();
        $form = $this->createCreateForm($entity);

        return $this->render('Type/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Finds and displays a Type entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/type/{id}/show", name="admin_type_show")
     */
    public function showAction($id)
    {
        $entity = $this->typeRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Type entity.');
        }
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Type/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Displays a form to edit an existing Type entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/type/{id}/edit", name="admin_type_edit")
     */
    public function editAction($id)
    {
        $entity = $this->typeRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Type entity.');
        }
        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Type/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Creates a form to edit a Type entity.
     *
     * @param Type $entity The entity
     *
     * @return \Symfony\Component\Form\FormInterface<Type> The form
     */
    private function createEditForm(Type $entity)
    {
        $form = $this->createForm(TypeType::class, $entity, ['action' => $this->generateUrl('admin_type_update', ['id' => $entity->getId()]), 'method' => 'PUT']);
        $form->add('submit', SubmitType::class, ['label' => 'Update']);

        return $form;
    }

    /**
     * Edits an existing Type entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/type/{id}/update", name="admin_type_update", methods={"POST", "PUT"})
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $this->typeRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Type entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('admin_type_edit', ['id' => $id]));
        }

        return $this->render('Type/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Deletes a Type entity.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/admin/type/{id}/delete", name="admin_type_delete", methods={"POST", "DELETE"})
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $this->typeRepository->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Type entity.');
            }
            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_type'));
    }

    /**
     * Creates a form to delete a Type entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\FormInterface<mixed> The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()->setAction($this->generateUrl('admin_type_delete', ['id' => $id]))->setMethod('DELETE')->getForm();
    }
}
