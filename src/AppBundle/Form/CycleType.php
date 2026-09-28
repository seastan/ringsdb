<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class CycleType extends AbstractType {
    /**
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('code')
            ->add('name')
            ->add('position')
            ->add('isBox', CheckboxType::class, array('required'  => false))
            ->add('isSaga', CheckboxType::class, array('required'  => false))
        ;
    }

    /**
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Entity\Cycle'
        ]);
    }

    public function getBlockPrefix() {
        return 'appbundle_cycletype';
    }
}
