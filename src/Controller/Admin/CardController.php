<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Card;
use App\Form\CardType;
use App\Repository\CardRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    private EntityManagerInterface $entityManager;

    public function __construct(
        string $publicDir,
        CardRepository $cardRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->publicDir = $publicDir;
        $this->cardRepository = $cardRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Lists all Card entities.
     *
     * @Route("/admin/card/", name="admin_card")
     */
    public function indexAction(): Response
    {
        $entities = $this->cardRepository->findAll();

        return $this->render('Card/index.html.twig', ['entities' => $entities]);
    }

    /**
     * Creates a new Card entity.
     *
     * @Route("/admin/card/create", name="admin_card_create", methods={"POST"})
     */
    public function createAction(Request $request): Response
    {
        $entity = new Card();
        $form = $this->createForm(CardType::class, $entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_card_show', ['id' => $entity->getId()]));
        }

        return $this->render('Card/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }

    /**
     * Displays a form to create a new Card entity.
     *
     * @Route("/admin/card/new", name="admin_card_new")
     */
    public function newAction(): Response
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
    public function showAction(int $id): Response
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
    public function editAction(int $id): Response
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
    public function updateAction(Request $request, int $id, Packages $packages): Response
    {
        $entity = $this->cardRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Card entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        $forceDeleteForm = $this->createForceDeleteForm($id);
        $editForm = $this->createForm(CardType::class, $entity, ['method' => 'PUT']);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            /* @var $file UploadedFile */
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
    public function deleteAction(Request $request, int $id): RedirectResponse
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $this->cardRepository->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Card entity.');
            }
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_card'));
    }

    /**
     * Forcibly deletes a Card entity and all its deck/decklist slot references.
     *
     * @Route("/admin/card/{id}/force_delete", name="admin_card_force_delete", methods={"POST", "DELETE"})
     */
    public function forceDeleteAction(Request $request, int $id): RedirectResponse
    {
        $form = $this->createForceDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $this->cardRepository->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Card entity.');
            }
            /* @var $dbh Connection */
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
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_card'));
    }

    /**
     * Creates a form to delete a Card entity by id.
     *
     * @return FormInterface<mixed> The form
     */
    private function createDeleteForm(int $id): FormInterface
    {
        return $this->createFormBuilder(['id' => $id])->add('id', HiddenType::class)->setMethod('DELETE')->getForm();
    }

    /**
     * Creates a form to forcibly delete a Card entity by id.
     *
     * @return FormInterface<mixed> The form
     */
    private function createForceDeleteForm(int $id): FormInterface
    {
        return $this->createFormBuilder(['id' => $id])->add('id', HiddenType::class)->setMethod('DELETE')->getForm();
    }
}
