<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<\AppBundle\Entity\Sphere>
 */
class SphereType extends AbstractType {
    /**
     * @param FormBuilderInterface<\AppBundle\Entity\Sphere|null> $builder
   * @param array<string, mixed> $options
   * @return void
   */
  public function buildForm(FormBuilderInterface $builder, array $options) {
      $builder
          ->add('code')
          ->add('name')
          ->add('is_primary');
  }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Entity\Sphere'
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix() {
        return 'appbundle_sphere';
    }
}
