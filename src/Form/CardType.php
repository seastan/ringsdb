<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Card;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Card>
 */
class CardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('position')
            ->add('deck_limit')
            ->add('code')
            ->add('type', EntityType::class, ['class' => 'App:Type', 'choice_label' => 'name'])
            ->add('sphere', EntityType::class, ['class' => 'App:Sphere', 'choice_label' => 'name'])
            ->add('name')
            ->add('traits')
            ->add('text', TextareaType::class, ['required' => false])
            ->add('flavor', TextareaType::class, ['required' => false])
            ->add('cost')
            ->add('threat')
            ->add('willpower')
            ->add('attack')
            ->add('defense')
            ->add('health')
            ->add('victory')
            ->add('quest')
            ->add('is_unique', CheckboxType::class, ['required' => false])
            ->add('has_errata', CheckboxType::class, ['required' => false])
            ->add('file', FileType::class, ['label' => 'Image File', 'mapped' => false, 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\Card',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'appbundle_cardtype';
    }
}
