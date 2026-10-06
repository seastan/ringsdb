<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Cycle;
use App\Form\CycleType;
use App\Repository\CycleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Cycle controller.
 */
class CycleController extends AbstractController
{
    public function __construct(private readonly CycleRepository $cycleRepository, private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Lists all Cycle entities.
     *
     * @Route("/admin/cycle/", name="admin_cycle")
     */
    public function indexAction(): Response
    {
        $entities = $this->cycleRepository->findAll();

        return $this->render('Cycle/index.html.twig', ['entities' => $entities]);
    }

    /**
     * Creates a new Cycle entity.
     *
     * @Route("/admin/cycle/create", name="admin_cycle_create", methods={"POST"})
     */
    public function createAction(Request $request): Response
    {
        $entity = new Cycle();
        $form = $this->createForm(CycleType::class, $entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_cycle_show', ['id' => $entity->getId()]));
        }

        return $this->render('Cycle/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Displays a form to create a new Cycle entity.
     *
     * @Route("/admin/cycle/new", name="admin_cycle_new")
     */
    public function newAction(): Response
    {
        $entity = new Cycle();
        $form = $this->createForm(CycleType::class, $entity);

        return $this->render('Cycle/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Finds and displays a Cycle entity.
     *
     * @Route("/admin/cycle/{id}/show", name="admin_cycle_show")
     */
    public function showAction(int $id): Response
    {
        $entity = $this->cycleRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Cycle entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Cycle/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Displays a form to edit an existing Cycle entity.
     *
     * @Route("/admin/cycle/{id}/edit", name="admin_cycle_edit")
     */
    public function editAction(int $id): Response
    {
        $entity = $this->cycleRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Cycle entity.');
        }

        $editForm = $this->createForm(CycleType::class, $entity, ['method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Cycle/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Edits an existing Cycle entity.
     *
     * @Route("/admin/cycle/{id}/update", name="admin_cycle_update", methods={"POST", "PUT"})
     */
    public function updateAction(Request $request, int $id): Response
    {
        $entity = $this->cycleRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Cycle entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(CycleType::class, $entity, ['method' => 'PUT']);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_cycle_edit', ['id' => $id]));
        }

        return $this->render('Cycle/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Deletes a Cycle entity.
     *
     * @Route("/admin/cycle/{id}/delete", name="admin_cycle_delete", methods={"POST", "DELETE"})
     */
    public function deleteAction(Request $request, int $id): RedirectResponse
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $this->cycleRepository->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Cycle entity.');
            }

            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_cycle'));
    }

    /**
     * Creates a form to delete a Cycle entity by id.
     *
     * @return FormInterface<mixed> The form
     */
    private function createDeleteForm(int $id): FormInterface
    {
        return $this->createFormBuilder(['id' => $id])->add('id', HiddenType::class)->setMethod('DELETE')->getForm();
    }
}
