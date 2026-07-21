<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class NouveauJoueurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Choisissez un pseudo',
                'constraints' => [
                    new NotBlank(message: 'Merci de choisir un pseudo.'),
                    new Length(min: 2, max: 100, minMessage: 'Le pseudo doit contenir au moins {{ limit }} caractères.'),
                ],
                'attr' => [
                    'autocomplete' => 'off',
                    'placeholder' => 'Ex. Alice',
                ],
            ])
            ->add('codePin', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Code PIN (4 chiffres)',
                    'attr' => [
                        'inputmode' => 'numeric',
                        'pattern' => '[0-9]{4}',
                        'autocomplete' => 'new-password',
                        'maxlength' => 4,
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmez le code PIN',
                    'attr' => [
                        'inputmode' => 'numeric',
                        'pattern' => '[0-9]{4}',
                        'autocomplete' => 'new-password',
                        'maxlength' => 4,
                    ],
                ],
                'invalid_message' => 'Les deux codes PIN ne correspondent pas.',
                'constraints' => [
                    new Regex(pattern: '/^\d{4}$/', message: 'Le code PIN doit contenir exactement 4 chiffres.'),
                ],
            ]);
    }
}