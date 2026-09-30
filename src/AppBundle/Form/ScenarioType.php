<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * @extends AbstractType<\AppBundle\Entity\Scenario>
 */
class ScenarioType extends AbstractType {
    /**
     * @param FormBuilderInterface<\AppBundle\Entity\Scenario|null> $builder
     * @param array<string, mixed> $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('code')
            ->add('name')
            ->add('position')
            ->add('pack', EntityType::class, array('class' => 'AppBundle:Pack', 'choice_label' => 'name'))
            ->add('encounters', EntityType::class, array('class' => 'AppBundle:Encounter', 'choice_label' => 'name', 'expanded' => true, 'multiple' => true));
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Entity\Scenario'
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix() {
        return 'appbundle_scenario';
    }
}
