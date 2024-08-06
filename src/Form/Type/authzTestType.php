<?php

namespace App\Form\Type;

use App\Entity\Institution;
use App\Entity\Service;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class authzTestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('institution', EntityType::class, [
                'label' => 'Institution',
                'required' => true,
                'class' => Institution::class,
                'choice_label' => 'name',
                'choice_value' => 'index',
                'data' => $options['institution'],
            ])
            ->add('service', EntityType::class, [
                'label' => 'Service',
                'required' => true,
                'class' => Service::class,
                'choice_label' => 'name',
                'choice_value' => 'slug',
                'data' => $options['service'],
            ])
            ->add('user', TextType::class, [
                'label' => 'User ID',
                'required' => true,
            ])
            ->add('submit', SubmitType::class, ['label' => 'Test', 'attr' => ['class' => 'btn btn-primary']])
        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'institution' => [],
            'service' => [],
        ]);
    }

}