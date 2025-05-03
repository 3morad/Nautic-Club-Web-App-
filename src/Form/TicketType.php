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
        // Determine if we should force support tickets only
        $forceSupport = $options['force_support_ticket'] ?? false;
        
        // If force support is enabled, only show Support Ticket option
        if ($forceSupport) {
            $builder
                ->add('isSupport', ChoiceType::class, [
                    'label' => 'Ticket Type',
                    'choices' => [
                        'Support Ticket' => true,
                    ],
                    'expanded' => true,
                    'multiple' => false,
                    'required' => true,
                    'empty_data' => true,
                    'data' => true,
                    'disabled' => true, // Make it non-editable
                ]);
        } else {
            // For admin, show both options
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
                ]);
        }
        
        $builder
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
                $defaultDate = new \DateTime('+2 weeks');
                if ($ticket && !$ticket->getEventDate()) {
                    $ticket->setEventDate($defaultDate);
                }
                $this->addEventTicketFields($form, $defaultDate);
            }
        });

        // Update form based on form submission data
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            
            if (isset($data['isSupport']) && $data['isSupport'] === '0') {
                // Convert the submitted date string to DateTime if present
                $eventDate = isset($data['eventDate']) && $data['eventDate'] 
                    ? new \DateTime($data['eventDate']) 
                    : new \DateTime('+2 weeks');
                    
                $this->addEventTicketFields($form, $eventDate);
            }
        });
    }

    private function addEventTicketFields($form, \DateTime $defaultDate): void
    {
        $form->add('ticketType', ChoiceType::class, [
            'label' => 'Event Ticket Type',
            'choices' => [
                'Regular Ticket' => 'Regular Ticket',
                'VIP Experience' => 'VIP Experience',
                'Exclusive Ticket' => 'Exclusive Ticket',
            ],
            'required' => true,
        ])
        ->add('price', MoneyType::class, [
            'currency' => 'USD',
            'required' => true,
        ])
        ->add('eventName', TextType::class, [
            'label' => 'Event Name',
            'required' => true,
        ])
        ->add('eventDate', DateTimeType::class, [
            'label' => 'Event Date',
            'widget' => 'single_text',
            'required' => true,
            'data' => $defaultDate,
            'html5' => true,
            'input' => 'datetime',
            'attr' => [
                'min' => (new \DateTime())->format('Y-m-d\TH:i'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
            'allow_extra_fields' => true,
            'force_support_ticket' => false, // Default to false, allowing both ticket types
        ]);
        
        $resolver->setAllowedTypes('force_support_ticket', 'bool');
    }
} 