<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CardPrinting;
use App\Form\CardPrintingType;
use App\Repository\CardPrintingRepository;
use App\Repository\PackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CardPrintingController extends AbstractController
{
    /**
     * @var CardPrintingRepository
     */
    private $cardPrintingRepository;
    /**
     * @var PackRepository
     */
    private $packRepository;

    public function __construct(CardPrintingRepository $cardPrintingRepository, PackRepository $packRepository)
    {
        $this->cardPrintingRepository = $cardPrintingRepository;
        $this->packRepository = $packRepository;
    }

    /**
     * @Route("/admin/card-printing/", name="admin_card_printing")
     */
    public function indexAction(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $em = $this->getDoctrine()->getManager();
        $packId = $request->query->get('pack');
        $cardName = $request->query->get('card');
        $qb = $em->createQueryBuilder()->select('cp', 'c', 'p')->from('App:CardPrinting', 'cp')->join('cp.card', 'c')->join('cp.pack', 'p')->orderBy('p.dateRelease', 'ASC')->addOrderBy('p.name', 'ASC')->addOrderBy('cp.position', 'ASC');
        if ($packId) {
            $qb->andWhere('p.id = :pack')->setParameter('pack', $packId);
        }
        if ($cardName) {
            $qb->andWhere('c.name LIKE :card')->setParameter('card', '%'.$cardName.'%');
        }
        $entities = $qb->getQuery()->getResult();
        $packs = $this->packRepository->findBy([], ['name' => 'ASC']);

        return $this->render('CardPrinting/index.html.twig', ['entities' => $entities, 'packs' => $packs, 'pack_filter' => $packId, 'card_filter' => $cardName]);
    }

    /**
     * @Route("/admin/card-printing/{id}/show", name="admin_card_printing_show")
     */
    public function showAction($id): \Symfony\Component\HttpFoundation\Response
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $this->cardPrintingRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CardPrinting entity.');
        }
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('CardPrinting/show.html.twig', ['entity' => $entity, 'delete_form' => $deleteForm->createView()]);
    }

    /**
     * @Route("/admin/card-printing/new", name="admin_card_printing_new")
     */
    public function newAction(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $em = $this->getDoctrine()->getManager();
        $filterPack = $this->resolveFilterPack($request, $em);
        $entity = new CardPrinting();
        $form = $this->createForm(CardPrintingType::class, $entity, ['filter_pack' => $filterPack]);

        return $this->render('CardPrinting/new.html.twig', ['entity' => $entity, 'form' => $form->createView(), 'packs' => $this->packRepository->findBy([], ['name' => 'ASC']), 'filter_pack' => $filterPack ? $filterPack->getId() : null]);
    }

    /**
     * @Route("/admin/card-printing/create", name="admin_card_printing_create", methods={"POST"})
     */
    public function createAction(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $em = $this->getDoctrine()->getManager();
        $filterPack = $this->resolveFilterPack($request, $em);
        $entity = new CardPrinting();
        $form = $this->createForm(CardPrintingType::class, $entity, ['filter_pack' => $filterPack]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_card_printing_show', ['id' => $entity->getId()]));
        }

        return $this->render('CardPrinting/new.html.twig', ['entity' => $entity, 'form' => $form->createView(), 'packs' => $this->packRepository->findBy([], ['name' => 'ASC']), 'filter_pack' => $filterPack ? $filterPack->getId() : null]);
    }

    /**
     * @Route("/admin/card-printing/{id}/edit", name="admin_card_printing_edit")
     */
    public function editAction(Request $request, $id): \Symfony\Component\HttpFoundation\Response
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $this->cardPrintingRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CardPrinting entity.');
        }
        $filterPack = $this->resolveFilterPack($request, $em);
        $editForm = $this->createForm(CardPrintingType::class, $entity, ['filter_pack' => $filterPack, 'method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('CardPrinting/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView(), 'packs' => $this->packRepository->findBy([], ['name' => 'ASC']), 'filter_pack' => $filterPack ? $filterPack->getId() : null]);
    }

    /**
     * @Route(
     *     "/admin/card-printing/{id}/update",
     *     name="admin_card_printing_update",
     *     methods={"POST", "PUT"}
     * )
     */
    public function updateAction(Request $request, $id): \Symfony\Component\HttpFoundation\Response
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $this->cardPrintingRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CardPrinting entity.');
        }
        $filterPack = $this->resolveFilterPack($request, $em);
        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(CardPrintingType::class, $entity, ['filter_pack' => $filterPack, 'method' => 'PUT']);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_card_printing_edit', ['id' => $id]));
        }

        return $this->render('CardPrinting/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView(), 'packs' => $this->packRepository->findBy([], ['name' => 'ASC']), 'filter_pack' => $filterPack ? $filterPack->getId() : null]);
    }

    /**
     * @Route(
     *     "/admin/card-printing/{id}/delete",
     *     name="admin_card_printing_delete",
     *     methods={"POST", "DELETE"}
     * )
     */
    public function deleteAction(Request $request, $id): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $this->cardPrintingRepository->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find CardPrinting entity.');
            }
            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_card_printing'));
    }

    private function resolveFilterPack(Request $request, $em)
    {
        $id = $request->query->get('filter_pack');
        if (!$id) {
            return null;
        }

        return $this->packRepository->find($id);
    }

    /**
     * @return \Symfony\Component\Form\FormInterface<mixed>
     */
    private function createDeleteForm($id): \Symfony\Component\Form\FormInterface
    {
        return $this->createFormBuilder(['id' => $id])->add('id', HiddenType::class)->setMethod('DELETE')->getForm();
    }
}
