<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * @extends AbstractType<\App\Entity\Scenario>
 */
class ScenarioType extends AbstractType {
    /**
     * @param FormBuilderInterface<\App\Entity\Scenario|null> $builder
     * @param array<string, mixed> $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('code')
            ->add('name')
            ->add('position')
            ->add('pack', EntityType::class, array('class' => 'App:Pack', 'choice_label' => 'name'))
            ->add('encounters', EntityType::class, array('class' => 'App:Encounter', 'choice_label' => 'name', 'expanded' => true, 'multiple' => true));
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\Scenario'
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix() {
        return 'appbundle_scenario';
    }
}
