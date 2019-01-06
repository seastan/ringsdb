<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ScenarioType extends AbstractType {
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('code')
            ->add('name')
            ->add('position')
            ->add('pack', 'entity', array('class' => 'AppBundle:Pack', 'property' => 'name'))
            ->add('encounters', 'entity', array('class' => 'AppBundle:Encounter', 'property' => 'name', 'expanded' => true, 'multiple' => true));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Entity\Scenario'
        ]);
    }

    /**
     * @return string
     */
    public function getName() {
        return 'appbundle_scenario';
    }
}
