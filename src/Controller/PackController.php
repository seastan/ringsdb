<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Pack;
use App\Form\PackType;
use App\Repository\PackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Pack controller.
 */
class PackController extends AbstractController
{
    /**
     * @var PackRepository
     */
    private $packRepository;

    public function __construct(PackRepository $packRepository)
    {
        $this->packRepository = $packRepository;
    }

    /**
     * Lists all Pack entities.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/pack/", name="admin_pack")
     */
    public function indexAction()
    {
        $entities = $this->packRepository->findAll();

        return $this->render('Pack/index.html.twig', ['entities' => $entities]);
    }

    /**
     * Creates a new Pack entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/pack/create", name="admin_pack_create", methods={"POST"})
     */
    public function createAction(Request $request)
    {
        $entity = new Pack();
        $form = $this->createForm(PackType::class, $entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_pack_show', ['id' => $entity->getId()]));
        }

        return $this->render('Pack/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Displays a form to create a new Pack entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/pack/new", name="admin_pack_new")
     */
    public function newAction()
    {
        $entity = new Pack();
        $form = $this->createForm(PackType::class, $entity);

        return $this->render('Pack/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Finds and displays a Pack entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/pack/{id}/show", name="admin_pack_show")
     */
    public function showAction($id)
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
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/pack/{id}/edit", name="admin_pack_edit")
     */
    public function editAction($id)
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
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/pack/{id}/update", name="admin_pack_update", methods={"POST", "PUT"})
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $this->packRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Pack entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(PackType::class, $entity, ['method' => 'PUT']);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_pack_edit', ['id' => $id]));
        }

        return $this->render('Pack/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Deletes a Pack entity.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/admin/pack/{id}/delete", name="admin_pack_delete", methods={"POST", "DELETE"})
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $this->packRepository->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Pack entity.');
            }
            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_pack'));
    }

    /**
     * Creates a form to delete a Pack entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\FormInterface<mixed> The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(['id' => $id])->add('id', HiddenType::class)->setMethod('DELETE')->getForm();
    }
}
