<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class UserType extends AbstractType
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Handles both registration and profile editing
        $isRegistration = $options['is_registration'];
        $isProfileEdit = $options['is_profile_edit'];
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        $builder
            ->add('username', null, [
                'attr' => ['autocomplete' => 'off'],
                'label' => 'Username',
            ])
            ->add('first_name', null, [
                'attr' => ['autocomplete' => 'off'],
                'label' => 'First Name',
            ])
            ->add('last_name', null, [
                'attr' => ['autocomplete' => 'off'],
                'label' => 'Last Name',
            ])
            ->add('password', PasswordType::class, [
                'attr' => ['autocomplete' => 'new-password'],
                'label' => $isProfileEdit ? 'New Password (optional)' : 'Password',
                'required' => $isRegistration, // password is required on register, not on edit
            ]);

        if ($isRegistration) {
            $builder
                ->add('email', null, [
                    'attr' => ['autocomplete' => 'off'],
                    'label' => 'Email',
                ]);
        }

        // Only allow admins to change status and user_type
        if ($isAdmin && !$isRegistration && !$isProfileEdit) {
            // Only show these fields in admin dashboard
            $builder
                ->add('email')
                ->add('status')
                ->add('user_type');
        } elseif (!$isRegistration && !$isProfileEdit) {
            // For regular users viewing admin pages, show fields as disabled
            $builder
                ->add('email', null, [
                    'disabled' => true
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_registration' => false,
            'is_profile_edit' => false,
        ]);
    }
}
