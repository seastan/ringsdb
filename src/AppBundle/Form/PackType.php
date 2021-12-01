<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PackType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('code')
            ->add('name')
            ->add('dateRelease', DateType::class,
                array('years' => array('2010', '2011', '2012', '2013', '2014', '2015', '2016', '2017', '2018', '2019', '2020', '2021', '2022', '2023', '2024', '2025', '2026', '2027', '2028', '2029', '2030')))
            ->add('size')
            ->add('cycle', 'entity', array('class' => 'AppBundle:Cycle', 'property' => 'name'))
            ->add('position');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Entity\Pack'
        ]);
    }

    public function getName() {
        return 'appbundle_packtype';
    }
}
