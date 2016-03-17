<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CardType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('pack', 'entity', array('class' => 'AppBundle:Pack', 'property' => 'name'))
            ->add('position')
            ->add('quantity')
            ->add('deck_limit')
            ->add('code')
            ->add('type', 'entity', array('class' => 'AppBundle:Type', 'property' => 'name'))
            ->add('sphere', 'entity', array('class' => 'AppBundle:Sphere', 'property' => 'name'))
            ->add('name')
            ->add('traits')
            ->add('text', 'textarea', array('required' => false))
            ->add('flavor', 'textarea', array('required' => false))
            ->add('cost')
            ->add('threat')
            ->add('willpower')
            ->add('attack')
            ->add('defense')
            ->add('health')
            ->add('victory')
            ->add('quest')
            ->add('illustrator')
            ->add('octgnid')
            ->add('is_unique', 'checkbox', array('required' => false))
            ->add('file', 'file', array('label' => 'Image File', 'mapped' => false, 'required' => false));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Entity\Card'
        ]);
    }

    public function getName() {
        return 'appbundle_cardtype';
    }
}
