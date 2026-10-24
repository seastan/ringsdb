<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Encounter;
use App\Entity\Pack;
use App\Entity\Scenario;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Scenario>
 */
class ScenarioType extends AbstractType
{
    /**
     * @param FormBuilderInterface<Scenario|null> $builder
     * @param array<string, mixed>                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code')
            ->add('name')
            ->add('position')
            ->add('pack', EntityType::class, ['class' => Pack::class, 'choice_label' => 'name'])
            ->add('encounters', EntityType::class, ['class' => Encounter::class, 'choice_label' => 'name', 'expanded' => true, 'multiple' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Scenario::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'appbundle_scenario';
    }
}
