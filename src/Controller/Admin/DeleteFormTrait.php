<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;

/**
 * The form that deletes an entity of the admin CRUD pages, by id.
 */
trait DeleteFormTrait
{
    /**
     * Creates a form to delete an entity by id.
     *
     * @return FormInterface<mixed> The form
     */
    private function createDeleteForm(?int $id): FormInterface
    {
        return $this->createFormBuilder(['id' => $id])->add('id', HiddenType::class)->setMethod('DELETE')->getForm();
    }
}
