<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Type;
use App\Form\TypeType;
use App\Repository\TypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    private EntityManagerInterface $entityManager;

    public function __construct(
        TypeRepository $typeRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->typeRepository = $typeRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Lists all Type entities.
     *
     * @Route("/admin/type/", name="admin_type")
     */
    public function indexAction(): Response
    {
        $entities = $this->typeRepository->findAll();

        return $this->render('Type/index.html.twig', ['entities' => $entities]);
    }

    /**
     * Creates a new Type entity.
     *
     * @Route("/admin/type/create", name="admin_type_create", methods={"POST"})
     */
    public function createAction(Request $request): Response
    {
        $entity = new Type();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_type_show', ['id' => $entity->getId()]));
        }

        return $this->render('Type/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Creates a form to create a Type entity.
     *
     * @param Type $entity The entity
     *
     * @return FormInterface<Type> The form
     */
    private function createCreateForm(Type $entity): FormInterface
    {
        $form = $this->createForm(TypeType::class, $entity, ['action' => $this->generateUrl('admin_type_create'), 'method' => 'POST']);

        return $form;
    }

    /**
     * Displays a form to create a new Type entity.
     *
     * @Route("/admin/type/new", name="admin_type_new")
     */
    public function newAction(): Response
    {
        $entity = new Type();
        $form = $this->createCreateForm($entity);

        return $this->render('Type/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Finds and displays a Type entity.
     *
     * @Route("/admin/type/{id}/show", name="admin_type_show")
     */
    public function showAction(int $id): Response
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
     * @Route("/admin/type/{id}/edit", name="admin_type_edit")
     */
    public function editAction(int $id): Response
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
     * @return FormInterface<Type> The form
     */
    private function createEditForm(Type $entity): FormInterface
    {
        $form = $this->createForm(TypeType::class, $entity, ['action' => $this->generateUrl('admin_type_update', ['id' => $entity->getId()]), 'method' => 'PUT']);
        $form->add('submit', SubmitType::class, ['label' => 'Update']);

        return $form;
    }

    /**
     * Edits an existing Type entity.
     *
     * @Route("/admin/type/{id}/update", name="admin_type_update", methods={"POST", "PUT"})
     */
    public function updateAction(Request $request, int $id): Response
    {
        $entity = $this->typeRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Type entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_type_edit', ['id' => $id]));
        }

        return $this->render('Type/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Deletes a Type entity.
     *
     * @Route("/admin/type/{id}/delete", name="admin_type_delete", methods={"POST", "DELETE"})
     */
    public function deleteAction(Request $request, int $id): RedirectResponse
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $this->typeRepository->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Type entity.');
            }
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_type'));
    }

    /**
     * Creates a form to delete a Type entity by id.
     *
     * @return FormInterface<mixed> The form
     */
    private function createDeleteForm(int $id): FormInterface
    {
        return $this->createFormBuilder()->setAction($this->generateUrl('admin_type_delete', ['id' => $id]))->setMethod('DELETE')->getForm();
    }
}
