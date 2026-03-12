<?php

namespace App\Form;

use App\Infrastructure\Persistence\Doctrine\Entity\Apartment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApartmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre',
                'attr' => ['placeholder' => 'Nombre del apartamento'],
            ])
            ->add('address', TextType::class, [
                'label' => 'Dirección',
                'attr' => ['placeholder' => 'Dirección completa'],
            ])
            ->add('price', IntegerType::class, [
                'label' => 'Precio (€/mes)',
                'attr' => ['placeholder' => 'Precio mensual'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descripción',
                'attr' => ['placeholder' => 'Descripción detallada del apartamento', 'rows' => 5],
                'required' => false,
            ])
            ->add('isAvailable', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Apartment::class,
        ]);
    }
}
