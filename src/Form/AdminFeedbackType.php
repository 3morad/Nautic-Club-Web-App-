<?php

namespace App\Form;

use App\Entity\Feedback;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class AdminFeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('eventName', TextType::class, [
                'label' => 'Event Name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter an event name',
                    ]),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                    ]),
                ],
            ])
            ->add('username', TextType::class, [
                'label' => 'Customer Name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a customer name',
                    ]),
                ],
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'required' => true,
                'label' => false,
                'attr' => ['class' => 'hidden'],
            ])
            ->add('rating', ChoiceType::class, [
                'choices' => [
                    '⭐' => 1,
                    '⭐⭐' => 2,
                    '⭐⭐⭐' => 3,
                    '⭐⭐⭐⭐' => 4,
                    '⭐⭐⭐⭐⭐' => 5,
                ],
                'expanded' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a rating',
                    ]),
                ],
            ])
            ->add('comment', TextareaType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a comment',
                    ]),
                ],
                'attr' => [
                    'rows' => 4,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Feedback::class,
        ]);
    }
} 