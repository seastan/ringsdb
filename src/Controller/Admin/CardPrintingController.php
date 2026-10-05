<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CardPrinting;
use App\Entity\Pack;
use App\Form\CardPrintingType;
use App\Repository\CardPrintingRepository;
use App\Repository\PackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    private EntityManagerInterface $entityManager;

    public function __construct(
        CardPrintingRepository $cardPrintingRepository,
        PackRepository $packRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->cardPrintingRepository = $cardPrintingRepository;
        $this->packRepository = $packRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/admin/card-printing/", name="admin_card_printing")
     */
    public function indexAction(Request $request): Response
    {
        $packId = $request->query->get('pack');
        $cardName = $request->query->get('card');
        $qb = $this->entityManager->createQueryBuilder()->select('cp', 'c', 'p')->from('App:CardPrinting', 'cp')->join('cp.card', 'c')->join('cp.pack', 'p')->orderBy('p.dateRelease', 'ASC')->addOrderBy('p.name', 'ASC')->addOrderBy('cp.position', 'ASC');
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
    public function showAction(int $id): Response
    {
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
    public function newAction(Request $request): Response
    {
        $filterPack = $this->resolveFilterPack($request);
        $entity = new CardPrinting();
        $form = $this->createForm(CardPrintingType::class, $entity, ['filter_pack' => $filterPack]);

        return $this->render('CardPrinting/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
            'packs' => $this->packRepository->findBy([], ['name' => 'ASC']),
            'filter_pack' => $filterPack ? $filterPack->getId() : null,
        ]);
    }

    /**
     * @Route("/admin/card-printing/create", name="admin_card_printing_create", methods={"POST"})
     */
    public function createAction(Request $request): Response
    {
        $filterPack = $this->resolveFilterPack($request);
        $entity = new CardPrinting();
        $form = $this->createForm(CardPrintingType::class, $entity, ['filter_pack' => $filterPack]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_card_printing_show', ['id' => $entity->getId()]));
        }

        return $this->render('CardPrinting/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
            'packs' => $this->packRepository->findBy([], ['name' => 'ASC']),
            'filter_pack' => $filterPack ? $filterPack->getId() : null,
        ]);
    }

    /**
     * @Route("/admin/card-printing/{id}/edit", name="admin_card_printing_edit")
     */
    public function editAction(Request $request, int $id): Response
    {
        $entity = $this->cardPrintingRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CardPrinting entity.');
        }
        $filterPack = $this->resolveFilterPack($request);
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
    public function updateAction(Request $request, int $id): Response
    {
        $entity = $this->cardPrintingRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CardPrinting entity.');
        }
        $filterPack = $this->resolveFilterPack($request);
        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(CardPrintingType::class, $entity, ['filter_pack' => $filterPack, 'method' => 'PUT']);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

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
    public function deleteAction(Request $request, int $id): RedirectResponse
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $this->cardPrintingRepository->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find CardPrinting entity.');
            }
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_card_printing'));
    }

    private function resolveFilterPack(Request $request): ?Pack
    {
        $id = $request->query->get('filter_pack');
        if (!$id) {
            return null;
        }

        return $this->packRepository->find($id);
    }

    /**
     * @return FormInterface<mixed>
     */
    private function createDeleteForm(int $id): FormInterface
    {
        return $this->createFormBuilder(['id' => $id])->add('id', HiddenType::class)->setMethod('DELETE')->getForm();
    }
}
