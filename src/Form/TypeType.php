<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<\App\Entity\Type>
 */
class TypeType extends AbstractType {
    /**
     * @param FormBuilderInterface<\App\Entity\Type|null> $builder
     * @param array<string, mixed> $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('code')->add('name');
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\Type'
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix() {
        return 'appbundle_type';
    }
}
