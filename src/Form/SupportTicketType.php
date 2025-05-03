<?php

namespace App\Form;

use App\Entity\Ticket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class SupportTicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a title',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Brief summary of your issue'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a description',
                    ]),
                ],
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Please describe your issue in detail'
                ]
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'choices' => [
                    'General' => 'general',
                    'Technical' => 'technical',
                    'Billing' => 'billing',
                    'Support' => 'support',
                ],
                'placeholder' => 'Select a category'
            ])
            // Hidden fields with defaults
            ->add('isSupport', HiddenType::class, [
                'data' => true,
            ])
            ->add('status', HiddenType::class, [
                'data' => 'open',
            ])
            ->add('priority', HiddenType::class, [
                'data' => 'high',
            ])
            ->add('ticketType', HiddenType::class, [
                'data' => 'Support Ticket',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
} 