<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CycleType extends AbstractType {
    /**
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('code')
            ->add('name')
            ->add('position')
            ->add('isBox', 'checkbox', array('required'  => false))
            ->add('isSaga', 'checkbox', array('required'  => false))
        ;
    }

    /**
     * @return void
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Entity\Cycle'
        ]);
    }

    public function getName() {
        return 'appbundle_cycletype';
    }
}
