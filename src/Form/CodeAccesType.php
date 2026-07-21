<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CodeAccesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('codeAcces', TextType::class, [
            'label' => 'Code d\'accès de l\'équipe',
            'constraints' => [
                new NotBlank(message: 'Merci de saisir un code d\'accès.'),
            ],
            'attr' => [
                'autocomplete' => 'off',
                'autocapitalize' => 'characters',
                'placeholder' => 'Ex. BDX2026',
            ],
        ]);
    }
}