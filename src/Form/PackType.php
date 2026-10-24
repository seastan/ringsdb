<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Cycle;
use App\Entity\Pack;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Pack>
 */
class PackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code')
            ->add('name')
            ->add(
                'dateRelease',
                DateType::class,
                [
                    'widget' => 'choice',
                    'years' => ['2010', '2011', '2012', '2013', '2014', '2015', '2016', '2017', '2018', '2019', '2020', '2021', '2022', '2023', '2024', '2025', '2026', '2027', '2028', '2029', '2030']
                ]
            )
            ->add('size')
            ->add('cycle', EntityType::class, ['class' => Cycle::class, 'choice_label' => 'name'])
            ->add('position');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pack::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'appbundle_packtype';
    }
}
