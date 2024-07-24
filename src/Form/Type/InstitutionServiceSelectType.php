<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstitutionServiceSelectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $services = [];
        foreach($options['services'] as $service) {
            $services[$service->getName()] = $service;
        }
        ksort($services);
        $builder
            ->add('service', ChoiceType::class, [
                'label' => 'Add a Service for this institution',
                'required' => true,
                'choices' => $services,
                'choice_label' => 'name',
                'choice_value' => 'slug',
                'placeholder' => 'Select a service',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('save', SubmitType::class, ['label' => 'Add Service', 'attr' => ['class' => 'btn btn-primary']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'services' => [],
        ]);
    }

}