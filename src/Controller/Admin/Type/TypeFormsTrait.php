<?php

declare(strict_types=1);

namespace App\Controller\Admin\Type;

use App\Entity\Type;
use App\Form\TypeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;

/**
 * The forms of the Type admin pages.
 */
trait TypeFormsTrait
{
    /**
     * Creates a form to create a Type entity.
     *
     * @param Type $entity The entity
     *
     * @return FormInterface<Type> The form
     */
    private function createCreateForm(Type $entity): FormInterface
    {
        return $this->createForm(TypeType::class, $entity, ['action' => $this->generateUrl('admin_type_create'), 'method' => 'POST']);
    }

    /**
     * Creates a form to edit a Type entity.
     *
     * @param Type $entity The entity
     *
     * @return FormInterface<Type> The form
     */
    private function createEditForm(Type $entity): FormInterface
    {
        $form = $this->createForm(TypeType::class, $entity, ['action' => $this->generateUrl('admin_type_update', ['id' => $entity->getId()]), 'method' => 'PUT']);
        $form->add('submit', SubmitType::class, ['label' => 'Update']);

        return $form;
    }

    /**
     * Creates a form to delete a Type entity by id.
     *
     * @return FormInterface<mixed> The form
     */
    private function createDeleteForm(?int $id): FormInterface
    {
        return $this->createFormBuilder()->setAction($this->generateUrl('admin_type_delete', ['id' => $id]))->setMethod('DELETE')->getForm();
    }
}
