<?php

namespace App\Form;

use App\Entity\Ticket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('isSupport', ChoiceType::class, [
                'label' => 'Ticket Type',
                'choices' => [
                    'Support Ticket' => true,
                    'Event Ticket' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => true,
                'empty_data' => false,
                'data' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a ticket type',
                    ]),
                ],
            ])
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a title',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a description',
                    ]),
                ],
            ])
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'General' => 'general',
                    'Technical' => 'technical',
                    'Billing' => 'billing',
                    'Support' => 'support',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Open' => 'open',
                    'In Progress' => 'in_progress',
                    'Closed' => 'closed',
                ],
            ])
            ->add('priority', ChoiceType::class, [
                'choices' => [
                    'Low' => 'low',
                    'Medium' => 'medium',
                    'High' => 'high',
                    'Urgent' => 'urgent',
                ],
            ]);

        // Add event ticket fields dynamically based on form data
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $ticket = $event->getData();
            $form = $event->getForm();

            // If creating a new ticket or editing an event ticket
            if (!$ticket || !$ticket->isSupport()) {
                $form->add('ticketType', ChoiceType::class, [
                    'label' => 'Event Ticket Type',
                    'choices' => [
                        'Regular Ticket' => 'Regular Ticket',
                        'VIP Experience' => 'VIP Experience',
                        'Exclusive Ticket' => 'Exclusive Ticket',
                    ],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please select an event ticket type',
                        ]),
                    ],
                ])
                ->add('price', MoneyType::class, [
                    'currency' => 'USD',
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please enter a price',
                        ]),
                    ],
                ])
                ->add('eventName', TextType::class, [
                    'label' => 'Event Name',
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please enter the event name',
                        ]),
                    ],
                ])
                ->add('eventDate', DateTimeType::class, [
                    'label' => 'Event Date',
                    'widget' => 'single_text',
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please enter the event date',
                        ]),
                    ],
                ]);
            }
        });

        // Update form based on form submission data
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            
            if (isset($data['isSupport']) && $data['isSupport'] === '0') {
                // Event ticket fields
                if (!$form->has('ticketType')) {
                    $form->add('ticketType', ChoiceType::class, [
                        'label' => 'Event Ticket Type',
                        'choices' => [
                            'Regular Ticket' => 'Regular Ticket',
                            'VIP Experience' => 'VIP Experience',
                            'Exclusive Ticket' => 'Exclusive Ticket',
                        ],
                        'constraints' => [
                            new NotBlank([
                                'message' => 'Please select an event ticket type',
                            ]),
                        ],
                    ])
                    ->add('price', MoneyType::class, [
                        'currency' => 'USD',
                        'constraints' => [
                            new NotBlank([
                                'message' => 'Please enter a price',
                            ]),
                        ],
                    ])
                    ->add('eventName', TextType::class, [
                        'label' => 'Event Name',
                        'constraints' => [
                            new NotBlank([
                                'message' => 'Please enter the event name',
                            ]),
                        ],
                    ])
                    ->add('eventDate', DateTimeType::class, [
                        'label' => 'Event Date',
                        'widget' => 'single_text',
                        'constraints' => [
                            new NotBlank([
                                'message' => 'Please enter the event date',
                            ]),
                        ],
                    ]);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
} 