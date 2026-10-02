<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\DomCrawler\Form;

trait FormFieldTrait
{
    /**
     * A field of a form that has a single field of that name ($form[$name] can also be a list).
     */
    private static function field(Form $form, string $name): FormField
    {
        $field = $form[$name];
        if (!$field instanceof FormField) {
            throw new \UnexpectedValueException("$name is not a single field");
        }

        return $field;
    }
}
