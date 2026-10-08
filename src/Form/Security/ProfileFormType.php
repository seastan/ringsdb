<?php

declare(strict_types=1);

namespace App\Form\Security;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * The account form (/profile/edit): username and email, confirmed by the current password.
 *
 * @extends AbstractType<User>
 */
class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', null, ['label' => 'Username'])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('current_password', PasswordType::class, [
                'label' => 'Current password',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(groups: ['Profile']),
                    new UserPassword(['message' => 'The entered password is invalid.', 'groups' => ['Profile']]),
                ],
                'attr' => ['autocomplete' => 'current-password'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_token_id' => 'profile',
            'validation_groups' => ['Profile', 'Default'],
        ]);
    }

    /**
     * The FOSUserBundle form names, kept so the field names do not change.
     */
    public function getBlockPrefix(): string
    {
        return 'fos_user_profile_form';
    }
}
