<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Card;
use App\Form\CardType;
use App\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Card controller.
 */
class CardController extends AbstractController
{
    /**
     * @var string
     */
    private $publicDir;
    /**
     * @var CardRepository
     */
    private $cardRepository;

    public function __construct(string $publicDir, CardRepository $cardRepository)
    {
        $this->publicDir = $publicDir;
        $this->cardRepository = $cardRepository;
    }

    /**
     * Lists all Card entities.
     *
     * @Route("/admin/card/", name="admin_card")
     */
    public function indexAction(): \Symfony\Component\HttpFoundation\Response
    {
        $entities = $this->cardRepository->findAll();

        return $this->render('Card/index.html.twig', ['entities' => $entities]);
    }

    /**
     * Creates a new Card entity.
     *
     * @Route("/admin/card/create", name="admin_card_create", methods={"POST"})
     */
    public function createAction(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $entity = new Card();
        $form = $this->createForm(CardType::class, $entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_card_show', ['id' => $entity->getId()]));
        }

        return $this->render('Card/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Displays a form to create a new Card entity.
     *
     * @Route("/admin/card/new", name="admin_card_new")
     */
    public function newAction(): \Symfony\Component\HttpFoundation\Response
    {
        $entity = new Card();
        $form = $this->createForm(CardType::class, $entity);

        return $this->render('Card/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Finds and displays a Card entity.
     *
     * @Route("/admin/card/{id}/show", name="admin_card_show")
     */
    public function showAction($id): \Symfony\Component\HttpFoundation\Response
    {
        $entity = $this->cardRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Card entity.');
        }
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Card/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * Displays a form to edit an existing Card entity.
     *
     * @Route("/admin/card/{id}/edit", name="admin_card_edit")
     */
    public function editAction($id): \Symfony\Component\HttpFoundation\Response
    {
        $entity = $this->cardRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Card entity.');
        }
        $editForm = $this->createForm(CardType::class, $entity, ['method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($id);
        $forceDeleteForm = $this->createForceDeleteForm($id);

        return $this->render('Card/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView(), 'force_delete_form' => $forceDeleteForm->createView()]);
    }

    /**
     * Edits an existing Card entity.
     *
     * @Route("/admin/card/{id}/update", name="admin_card_update", methods={"POST", "PUT"})
     */
    public function updateAction(Request $request, $id, Packages $packages): \Symfony\Component\HttpFoundation\Response
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $this->cardRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Card entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        $forceDeleteForm = $this->createForceDeleteForm($id);
        $editForm = $this->createForm(CardType::class, $entity, ['method' => 'PUT']);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->persist($entity);
            $em->flush();
            /* @var $file \Symfony\Component\HttpFoundation\File\UploadedFile */
            $file = $editForm->get('file')->getData();
            if ($file) {
                $imagedirurl = $packages->getUrl('/bundles/app/images/cards');
                $imagedirpath = $this->publicDir.preg_replace('/\\?.*/', '', $imagedirurl);
                $imagefilename = $entity->getCode().'.png';
                $file->move($imagedirpath, $imagefilename);
            }

            return $this->redirect($this->generateUrl('admin_card_edit', ['id' => $id]));
        }

        return $this->render('Card/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView(), 'force_delete_form' => $forceDeleteForm->createView()]);
    }

    /**
     * Deletes a Card entity.
     *
     * @Route("/admin/card/{id}/delete", name="admin_card_delete", methods={"POST", "DELETE"})
     */
    public function deleteAction(Request $request, $id): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $this->cardRepository->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Card entity.');
            }
            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_card'));
    }

    /**
     * Forcibly deletes a Card entity and all its deck/decklist slot references.
     *
     * @Route("/admin/card/{id}/force_delete", name="admin_card_force_delete", methods={"POST", "DELETE"})
     */
    public function forceDeleteAction(Request $request, $id): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $form = $this->createForceDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $this->cardRepository->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Card entity.');
            }
            /* @var $dbh \Doctrine\DBAL\Connection */
            $dbh = $this->getDoctrine()->getConnection();
            $query = 'DELETE FROM deckslot WHERE card_id = '.$id;
            $dbh->executeQuery($query, []);
            $query = 'DELETE FROM decksideslot WHERE card_id = '.$id;
            $dbh->executeQuery($query, []);
            $query = 'DELETE FROM decklistslot WHERE card_id = '.$id;
            $dbh->executeQuery($query, []);
            $query = 'DELETE FROM decklistsideslot WHERE card_id = '.$id;
            $dbh->executeQuery($query, []);
            $query = 'DELETE FROM card_printing WHERE card_id = '.$id;
            $dbh->executeQuery($query, []);
            $query = 'DELETE FROM reviewvote WHERE review_id IN (SELECT id FROM review WHERE card_id = '.$id.')';
            $dbh->executeQuery($query, []);
            $query = 'DELETE FROM review WHERE card_id = '.$id;
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
     * @return \Symfony\Component\Form\FormInterface<mixed> The form
     */
    private function createDeleteForm($id): \Symfony\Component\Form\FormInterface
    {
        return $this->createFormBuilder(['id' => $id])->add('id', HiddenType::class)->setMethod('DELETE')->getForm();
    }

    /**
     * Creates a form to forcibly delete a Card entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\FormInterface<mixed> The form
     */
    private function createForceDeleteForm($id): \Symfony\Component\Form\FormInterface
    {
        return $this->createFormBuilder(['id' => $id])->add('id', HiddenType::class)->setMethod('DELETE')->getForm();
    }
}
