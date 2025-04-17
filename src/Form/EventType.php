<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\LocationWeather;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 2, 'max' => 255]),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('eventDate', DateTimeType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 10]),
                ],
                'attr' => ['class' => 'form-control', 'rows' => 5],
            ])
            ->add('locationWeather', EntityType::class, [
                'class' => LocationWeather::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a location',
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
} 