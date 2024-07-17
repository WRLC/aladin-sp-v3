<?php

namespace App\Form\Type;

use App\Entity\Service;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('slug', TextType::class, ['label' => 'Slug', 'required' => true])
            ->add('name', TextType::class, ['label' => 'Name', 'required' => true])
            ->add('url', UrlType::class, ['label' => 'URL', 'required' => true])
            ->add('callback_path', TextType::class, ['label' => 'Callback Path', 'required' => false])
            ->add('legacy_login_path', TextType::class, ['label' => 'Legacy Login Path', 'required' => false])
            ->add('use_wrInstitution', ChoiceType::class, ['label' => 'Use WRLC Flask IdP', 'choices' => ['Yes' => 1, 'No' => 0], 'expanded' => true, 'required' => true])
            ->add('save', SubmitType::class, ['label' => 'Add Service'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}