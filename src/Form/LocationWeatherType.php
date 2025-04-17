<?php

namespace App\Form;

use App\Entity\LocationWeather;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;

class LocationWeatherType extends AbstractType
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
            ->add('region', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 2, 'max' => 255]),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('latitude', NumberType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Range(['min' => -90, 'max' => 90]),
                ],
                'attr' => ['class' => 'form-control'],
                'scale' => 6,
            ])
            ->add('longitude', NumberType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Range(['min' => -180, 'max' => 180]),
                ],
                'attr' => ['class' => 'form-control'],
                'scale' => 6,
            ])
            ->add('temperature', NumberType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => ['class' => 'form-control'],
                'scale' => 1,
            ])
            ->add('weatherCondition', ChoiceType::class, [
                'choices' => [
                    'Sunny' => 'sunny',
                    'Cloudy' => 'cloudy',
                    'Rainy' => 'rainy',
                    'Snowy' => 'snowy',
                    'Stormy' => 'stormy',
                    'Foggy' => 'foggy',
                    'Other' => 'other',
                ],
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LocationWeather::class,
        ]);
    }
} 