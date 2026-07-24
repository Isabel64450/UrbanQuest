<?php

namespace App\Form;

use App\Entity\Etape;
use App\Entity\Parcours;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EtapeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle')
            ->add('consigne')
            ->add('ordre')
            ->add('latitude')
            ->add('longitude')
            ->add('rayonValidationMetres')
            ->add('reponseAttendue')
            ->add('points')
            ->add('nombreEchecsAvantIndice')
            ->add('indice')
            ->add('parcours', EntityType::class, [
                'class' => Parcours::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Etape::class,
        ]);
    }
}
