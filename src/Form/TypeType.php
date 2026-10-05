<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Type>
 */
class TypeType extends AbstractType
{
    /**
     * @param FormBuilderInterface<Type|null> $builder
     * @param array<string, mixed>            $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code')->add('name');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Type::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'appbundle_type';
    }
}
