<?php

declare(strict_types=1);

namespace App\Form\Security;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The new password form of the password reset (/resetting/reset/{token}).
 *
 * @extends AbstractType<User>
 */
class ResettingFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'options' => ['attr' => ['autocomplete' => 'new-password']],
            'first_options' => ['label' => 'New password'],
            'second_options' => ['label' => 'Repeat new password'],
            'invalid_message' => "The entered passwords don't match.",
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_token_id' => 'resetting',
            'validation_groups' => ['ResetPassword', 'Default'],
        ]);
    }

    /**
     * The FOSUserBundle form names, kept so the field names do not change.
     */
    public function getBlockPrefix(): string
    {
        return 'fos_user_resetting_form';
    }
}
