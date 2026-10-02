<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Encounter;
use App\Form\EncounterType;
use App\Repository\EncounterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Encounter controller.
 */
class EncounterController extends AbstractController
{
    /**
     * @var EncounterRepository
     */
    private $encounterRepository;

    public function __construct(EncounterRepository $encounterRepository)
    {
        $this->encounterRepository = $encounterRepository;
    }

    /**
     * Lists all Encounter entities.
     *
     * @Route("/admin/encounter/", name="admin_encounter")
     */
    public function indexAction(): Response
    {
        $entities = $this->encounterRepository->findAll();

        return $this->render('Encounter/index.html.twig', ['entities' => $entities]);
    }

    /**
     * Creates a new Encounter entity.
     *
     * @Route("/admin/encounter/create", name="admin_encounter_create", methods={"POST"})
     */
    public function createAction(Request $request): Response
    {
        $entity = new Encounter();
        $form = $this->createForm(EncounterType::class, $entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_encounter_show', ['id' => $entity->getId()]));
        }

        return $this->render('Encounter/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Displays a form to create a new Encounter entity.
     *
     * @Route("/admin/encounter/new", name="admin_encounter_new")
     */
    public function newAction(): Response
    {
        $entity = new Encounter();
        $form = $this->createForm(EncounterType::class, $entity);

        return $this->render('Encounter/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Finds and displays a Encounter entity.
     *
     * @Route("/admin/encounter/{id}/show", name="admin_encounter_show")
     */
    public function showAction($id): Response
    {
        $entity = $this->encounterRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Encounter entity.');
        }
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Encounter/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Displays a form to edit an existing Encounter entity.
     *
     * @Route("/admin/encounter/{id}/edit", name="admin_encounter_edit")
     */
    public function editAction($id): Response
    {
        $entity = $this->encounterRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Encounter entity.');
        }
        $editForm = $this->createForm(EncounterType::class, $entity, ['method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Encounter/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Edits an existing Encounter entity.
     *
     * @Route("/admin/encounter/{id}/update", name="admin_encounter_update", methods={"POST", "PUT"})
     */
    public function updateAction(Request $request, $id): Response
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $this->encounterRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Encounter entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(EncounterType::class, $entity, ['method' => 'PUT']);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_encounter_edit', ['id' => $id]));
        }

        return $this->render('Encounter/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Deletes a Encounter entity.
     *
     * @Route("/admin/encounter/{id}/delete", name="admin_encounter_delete", methods={"POST", "DELETE"})
     */
    public function deleteAction(Request $request, $id): RedirectResponse
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $this->encounterRepository->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Encounter entity.');
            }
            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_encounter'));
    }

    /**
     * Creates a form to delete a Encounter entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return FormInterface<mixed> The form
     */
    private function createDeleteForm($id): FormInterface
    {
        return $this->createFormBuilder(['id' => $id])->add('id', HiddenType::class)->setMethod('DELETE')->getForm();
    }
}
