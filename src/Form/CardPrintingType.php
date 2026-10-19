<?php

namespace App\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * @extends AbstractType<\App\Entity\CardPrinting>
 */
class CardPrintingType extends AbstractType {
    /**
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $filterPack = $options['filter_pack'];

        $builder
            ->add('card', EntityType::class, array(
                'class'         => 'App:Card',
                'choice_label'  => 'adminLabel',
                'query_builder' => function(EntityRepository $er) use ($filterPack) {
                    $qb = $er->createQueryBuilder('c')
                        ->join('c.sphere', 's')
                        ->join('c.type', 't')
                        ->orderBy('c.name')
                        // tie-breaker: several cards share a name (a hero and its ally, reprints)
                        ->addOrderBy('c.id');
                    if ($filterPack) {
                        $qb->join('c.printings', 'cp')
                            ->andWhere('cp.pack = :pack')
                            ->setParameter('pack', $filterPack);
                    }
                    return $qb;
                },
            ))
            ->add('pack', EntityType::class, array('class' => 'App:Pack', 'choice_label' => 'name'))
            ->add('position')
            ->add('quantity')
            ->add('imageCode')
            ->add('illustrator', null, array('required' => false))
            ->add('octgnid', null, array('required' => false))
            ->add('traits', null, array('required' => false, 'label' => 'Traits override (leave blank = use card value)'))
            ->add('text', TextareaType::class, array('required' => false, 'label' => 'Text override (leave blank = use card value)'))
            ->add('cost', null, array('required' => false, 'label' => 'Cost override'))
            ->add('threat', null, array('required' => false, 'label' => 'Threat override'))
            ->add('willpower', null, array('required' => false, 'label' => 'Willpower override'))
            ->add('attack', null, array('required' => false, 'label' => 'Attack override'))
            ->add('defense', null, array('required' => false, 'label' => 'Defense override'))
            ->add('health', null, array('required' => false, 'label' => 'Health override'))
            ->add('victory', null, array('required' => false, 'label' => 'Victory override'))
            ->add('quest', null, array('required' => false, 'label' => 'Quest override'));
    }

    /**
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class'  => 'App\Entity\CardPrinting',
            'filter_pack' => null,
        ]);
    }

    public function getBlockPrefix() {
        return 'appbundle_cardprintingtype';
    }
}
