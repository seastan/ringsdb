<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * @extends AbstractType<\AppBundle\Entity\Card>
 */
class CardType extends AbstractType {
    /**
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('position')
            ->add('deck_limit')
            ->add('code')
            ->add('type', EntityType::class, array('class' => 'AppBundle:Type', 'choice_label' => 'name'))
            ->add('sphere', EntityType::class, array('class' => 'AppBundle:Sphere', 'choice_label' => 'name'))
            ->add('name')
            ->add('traits')
            ->add('text', TextareaType::class, array('required' => false))
            ->add('flavor', TextareaType::class, array('required' => false))
            ->add('cost')
            ->add('threat')
            ->add('willpower')
            ->add('attack')
            ->add('defense')
            ->add('health')
            ->add('victory')
            ->add('quest')
            ->add('is_unique', CheckboxType::class, array('required' => false))
            ->add('has_errata', CheckboxType::class, array('required' => false))
            ->add('file', FileType::class, array('label' => 'Image File', 'mapped' => false, 'required' => false));
    }

    /**
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Entity\Card'
        ]);
    }

    public function getBlockPrefix() {
        return 'appbundle_cardtype';
    }
}
