<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Sphere;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Sphere>
 */
class SphereType extends AbstractType
{
    /**
     * @param FormBuilderInterface<Sphere|null> $builder
     * @param array<string, mixed>              $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code')
            ->add('name')
            ->add('is_primary');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sphere::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'appbundle_sphere';
    }
}
