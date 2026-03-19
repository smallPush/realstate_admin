<?php

namespace App\Form;

use App\Infrastructure\Persistence\Doctrine\Entity\ApartmentGroup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApartmentGroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre del Grupo',
                'attr' => ['placeholder' => 'Ej: España, Madrid, etc.']
            ])
            ->add('parent', EntityType::class, [
                'class' => ApartmentGroup::class,
                'choice_label' => 'name',
                'label' => 'Grupo Padre',
                'required' => false,
                'placeholder' => 'Ninguno (Grupo Raíz)',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApartmentGroup::class,
        ]);
    }
}
