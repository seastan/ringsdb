<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Sphere;
use App\Form\SphereType;
use App\Repository\SphereRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    private EntityManagerInterface $entityManager;

    public function __construct(
        SphereRepository $sphereRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->sphereRepository = $sphereRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Lists all Sphere entities.
     *
     * @Route("/admin/sphere/", name="admin_sphere")
     */
    public function indexAction(): Response
    {
        $entities = $this->sphereRepository->findAll();

        return $this->render('Sphere/index.html.twig', ['entities' => $entities]);
    }

    /**
     * Creates a new Sphere entity.
     *
     * @Route("/admin/sphere/create", name="admin_sphere_create", methods={"POST"})
     */
    public function createAction(Request $request): Response
    {
        $entity = new Sphere();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_sphere_show', ['id' => $entity->getId()]));
        }

        return $this->render('Sphere/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Creates a form to create a Sphere entity.
     *
     * @param Sphere $entity The entity
     *
     * @return FormInterface<Sphere> The form
     */
    private function createCreateForm(Sphere $entity): FormInterface
    {
        $form = $this->createForm(SphereType::class, $entity, ['action' => $this->generateUrl('admin_sphere_create'), 'method' => 'POST']);

        return $form;
    }

    /**
     * Displays a form to create a new Sphere entity.
     *
     * @Route("/admin/sphere/new", name="admin_sphere_new")
     */
    public function newAction(): Response
    {
        $entity = new Sphere();
        $form = $this->createCreateForm($entity);

        return $this->render('Sphere/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Finds and displays a Sphere entity.
     *
     * @Route("/admin/sphere/{id}/show", name="admin_sphere_show")
     */
    public function showAction(int $id): Response
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
     * @Route("/admin/sphere/{id}/edit", name="admin_sphere_edit")
     */
    public function editAction(int $id): Response
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
     * @return FormInterface<Sphere> The form
     */
    private function createEditForm(Sphere $entity): FormInterface
    {
        $form = $this->createForm(SphereType::class, $entity, ['action' => $this->generateUrl('admin_sphere_update', ['id' => $entity->getId()]), 'method' => 'PUT']);
        $form->add('submit', SubmitType::class, ['label' => 'Update']);

        return $form;
    }

    /**
     * Edits an existing Sphere entity.
     *
     * @Route("/admin/sphere/{id}/update", name="admin_sphere_update", methods={"POST", "PUT"})
     */
    public function updateAction(Request $request, int $id): Response
    {
        $entity = $this->sphereRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Sphere entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_sphere_edit', ['id' => $id]));
        }

        return $this->render('Sphere/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Deletes a Sphere entity.
     *
     * @Route("/admin/sphere/{id}/delete", name="admin_sphere_delete", methods={"POST", "DELETE"})
     */
    public function deleteAction(Request $request, int $id): RedirectResponse
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $this->sphereRepository->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Sphere entity.');
            }
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_sphere'));
    }

    /**
     * Creates a form to delete a Sphere entity by id.
     *
     * @return FormInterface<mixed> The form
     */
    private function createDeleteForm(int $id): FormInterface
    {
        return $this->createFormBuilder()->setAction($this->generateUrl('admin_sphere_delete', ['id' => $id]))->setMethod('DELETE')->getForm();
    }
}
