<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CardPrintingType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('card', 'entity', array('class' => 'AppBundle:Card', 'property' => 'name'))
            ->add('pack', 'entity', array('class' => 'AppBundle:Pack', 'property' => 'name'))
            ->add('position')
            ->add('quantity')
            ->add('imageCode')
            ->add('illustrator', null, array('required' => false))
            ->add('octgnid', null, array('required' => false))
            ->add('traits', null, array('required' => false, 'label' => 'Traits override (leave blank = use card value)'))
            ->add('text', 'textarea', array('required' => false, 'label' => 'Text override (leave blank = use card value)'))
            ->add('cost', null, array('required' => false, 'label' => 'Cost override'))
            ->add('threat', null, array('required' => false, 'label' => 'Threat override'))
            ->add('willpower', null, array('required' => false, 'label' => 'Willpower override'))
            ->add('attack', null, array('required' => false, 'label' => 'Attack override'))
            ->add('defense', null, array('required' => false, 'label' => 'Defense override'))
            ->add('health', null, array('required' => false, 'label' => 'Health override'))
            ->add('victory', null, array('required' => false, 'label' => 'Victory override'))
            ->add('quest', null, array('required' => false, 'label' => 'Quest override'));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Entity\CardPrinting'
        ]);
    }

    public function getName() {
        return 'appbundle_cardprintingtype';
    }
}
