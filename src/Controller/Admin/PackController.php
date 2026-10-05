<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Pack;
use App\Form\PackType;
use App\Repository\PackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Pack controller.
 */
class PackController extends AbstractController
{
    private PackRepository $packRepository;

    private EntityManagerInterface $entityManager;

    public function __construct(
        PackRepository $packRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->packRepository = $packRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Lists all Pack entities.
     *
     * @Route("/admin/pack/", name="admin_pack")
     */
    public function indexAction(): Response
    {
        $entities = $this->packRepository->findAll();

        return $this->render('Pack/index.html.twig', ['entities' => $entities]);
    }

    /**
     * Creates a new Pack entity.
     *
     * @Route("/admin/pack/create", name="admin_pack_create", methods={"POST"})
     */
    public function createAction(Request $request): Response
    {
        $entity = new Pack();
        $form = $this->createForm(PackType::class, $entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_pack_show', ['id' => $entity->getId()]));
        }

        return $this->render('Pack/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Displays a form to create a new Pack entity.
     *
     * @Route("/admin/pack/new", name="admin_pack_new")
     */
    public function newAction(): Response
    {
        $entity = new Pack();
        $form = $this->createForm(PackType::class, $entity);

        return $this->render('Pack/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Finds and displays a Pack entity.
     *
     * @Route("/admin/pack/{id}/show", name="admin_pack_show")
     */
    public function showAction(int $id): Response
    {
        $entity = $this->packRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Pack entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Pack/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Displays a form to edit an existing Pack entity.
     *
     * @Route("/admin/pack/{id}/edit", name="admin_pack_edit")
     */
    public function editAction(int $id): Response
    {
        $entity = $this->packRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Pack entity.');
        }

        $editForm = $this->createForm(PackType::class, $entity, ['method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Pack/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Edits an existing Pack entity.
     *
     * @Route("/admin/pack/{id}/update", name="admin_pack_update", methods={"POST", "PUT"})
     */
    public function updateAction(Request $request, int $id): Response
    {
        $entity = $this->packRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Pack entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(PackType::class, $entity, ['method' => 'PUT']);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_pack_edit', ['id' => $id]));
        }

        return $this->render('Pack/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Deletes a Pack entity.
     *
     * @Route("/admin/pack/{id}/delete", name="admin_pack_delete", methods={"POST", "DELETE"})
     */
    public function deleteAction(Request $request, int $id): RedirectResponse
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $this->packRepository->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Pack entity.');
            }

            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_pack'));
    }

    /**
     * Creates a form to delete a Pack entity by id.
     *
     * @return FormInterface<mixed> The form
     */
    private function createDeleteForm(int $id): FormInterface
    {
        return $this->createFormBuilder(['id' => $id])->add('id', HiddenType::class)->setMethod('DELETE')->getForm();
    }
}
