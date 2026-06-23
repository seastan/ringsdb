<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\Entity\CardPrinting;
use AppBundle\Form\CardPrintingType;

class CardPrintingController extends Controller {

    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();

        $packId   = $request->query->get('pack');
        $cardName = $request->query->get('card');

        $qb = $em->createQueryBuilder()
            ->select('cp', 'c', 'p')
            ->from('AppBundle:CardPrinting', 'cp')
            ->join('cp.card', 'c')
            ->join('cp.pack', 'p')
            ->orderBy('p.dateRelease', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->addOrderBy('cp.position', 'ASC');

        if ($packId) {
            $qb->andWhere('p.id = :pack')->setParameter('pack', $packId);
        }
        if ($cardName) {
            $qb->andWhere('c.name LIKE :card')->setParameter('card', '%' . $cardName . '%');
        }

        $entities = $qb->getQuery()->getResult();
        $packs    = $em->getRepository('AppBundle:Pack')->findBy([], ['name' => 'ASC']);

        return $this->render('AppBundle:CardPrinting:index.html.twig', [
            'entities'    => $entities,
            'packs'       => $packs,
            'pack_filter' => $packId,
            'card_filter' => $cardName,
        ]);
    }

    public function showAction($id) {
        $em     = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AppBundle:CardPrinting')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CardPrinting entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:CardPrinting:show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    public function newAction(Request $request) {
        $em         = $this->getDoctrine()->getManager();
        $filterPack = $this->resolveFilterPack($request, $em);
        $entity     = new CardPrinting();
        $form       = $this->createForm(new CardPrintingType(), $entity, ['filter_pack' => $filterPack]);

        return $this->render('AppBundle:CardPrinting:new.html.twig', [
            'entity'      => $entity,
            'form'        => $form->createView(),
            'packs'       => $em->getRepository('AppBundle:Pack')->findBy([], ['name' => 'ASC']),
            'filter_pack' => $filterPack ? $filterPack->getId() : null,
        ]);
    }

    public function createAction(Request $request) {
        $em         = $this->getDoctrine()->getManager();
        $filterPack = $this->resolveFilterPack($request, $em);
        $entity     = new CardPrinting();
        $form       = $this->createForm(new CardPrintingType(), $entity, ['filter_pack' => $filterPack]);
        $form->bind($request);

        if ($form->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_card_printing_show', ['id' => $entity->getId()]));
        }

        return $this->render('AppBundle:CardPrinting:new.html.twig', [
            'entity'      => $entity,
            'form'        => $form->createView(),
            'packs'       => $em->getRepository('AppBundle:Pack')->findBy([], ['name' => 'ASC']),
            'filter_pack' => $filterPack ? $filterPack->getId() : null,
        ]);
    }

    public function editAction(Request $request, $id) {
        $em         = $this->getDoctrine()->getManager();
        $entity     = $em->getRepository('AppBundle:CardPrinting')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CardPrinting entity.');
        }

        $filterPack = $this->resolveFilterPack($request, $em);
        $editForm   = $this->createForm(new CardPrintingType(), $entity, ['filter_pack' => $filterPack]);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:CardPrinting:edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'packs'       => $em->getRepository('AppBundle:Pack')->findBy([], ['name' => 'ASC']),
            'filter_pack' => $filterPack ? $filterPack->getId() : null,
        ]);
    }

    public function updateAction(Request $request, $id) {
        $em     = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AppBundle:CardPrinting')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CardPrinting entity.');
        }

        $filterPack = $this->resolveFilterPack($request, $em);
        $deleteForm = $this->createDeleteForm($id);
        $editForm   = $this->createForm(new CardPrintingType(), $entity, ['filter_pack' => $filterPack]);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_card_printing_edit', ['id' => $id]));
        }

        return $this->render('AppBundle:CardPrinting:edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'packs'       => $em->getRepository('AppBundle:Pack')->findBy([], ['name' => 'ASC']),
            'filter_pack' => $filterPack ? $filterPack->getId() : null,
        ]);
    }

    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em     = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:CardPrinting')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find CardPrinting entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_card_printing'));
    }

    private function resolveFilterPack(Request $request, $em) {
        $id = $request->query->get('filter_pack');
        if (!$id) {
            return null;
        }
        return $em->getRepository('AppBundle:Pack')->find($id);
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(['id' => $id])
            ->add('id', 'hidden')
            ->getForm();
    }
}
