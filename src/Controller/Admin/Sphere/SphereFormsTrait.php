<?php

declare(strict_types=1);

namespace App\Controller\Admin\Sphere;

use App\Entity\Sphere;
use App\Form\SphereType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;

/**
 * The forms of the Sphere admin pages.
 */
trait SphereFormsTrait
{
    /**
     * Creates a form to create a Sphere entity.
     *
     * @param Sphere $entity The entity
     *
     * @return FormInterface<Sphere> The form
     */
    private function createCreateForm(Sphere $entity): FormInterface
    {
        return $this->createForm(SphereType::class, $entity, ['action' => $this->generateUrl('admin_sphere_create'), 'method' => 'POST']);
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
     * Creates a form to delete a Sphere entity by id.
     *
     * @return FormInterface<mixed> The form
     */
    private function createDeleteForm(int $id): FormInterface
    {
        return $this->createFormBuilder()->setAction($this->generateUrl('admin_sphere_delete', ['id' => $id]))->setMethod('DELETE')->getForm();
    }
}
