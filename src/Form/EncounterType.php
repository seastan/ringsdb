<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Encounter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Encounter>
 */
class EncounterType extends AbstractType
{
    /**
     * @param FormBuilderInterface<Encounter|null> $builder
     * @param array<string, mixed>                 $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code')
            ->add('name')
            ->add('pack', EntityType::class, ['class' => 'App:Pack', 'choice_label' => 'name']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\Encounter',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'appbundle_encounter';
    }
}
