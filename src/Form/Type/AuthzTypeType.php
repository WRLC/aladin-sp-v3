<?php

namespace App\Form\Type;

use App\Entity\AuthzType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthzTypeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', TextType::class, ['label' => 'Type', 'required' => true])
            ->add('description', TextType::class, ['label' => 'Description', 'required' => true])
            ->add('save', SubmitType::class, ['label' => 'Add AuthzType'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AuthzType::class,
        ]);
    }
}