<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * @extends AbstractType<\App\Entity\Encounter>
 */
class EncounterType extends AbstractType {
    /**
     * @param FormBuilderInterface<\App\Entity\Encounter|null> $builder
     * @param array<string, mixed> $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('code')
            ->add('name')
            ->add('pack', EntityType::class, array('class' => 'App:Pack', 'choice_label' => 'name'));
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\Encounter'
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix() {
        return 'appbundle_encounter';
    }
}
