<?php

namespace App\Form\Type;

use App\Entity\Service;
use Symfony\Component\Form\AbstractType;
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
            ->add('slug', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Slug',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'Unique identifier for the service (e.g., "service_desk").',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'required' => true,
            ])
            ->add('name', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Name',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'Full name of the service (to be displayed in header for WAYF menu).',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'required' => true,
                ])
            ->add('url', UrlType::class, [
                'attr' =>['class' => 'form-control'],
                'label' => 'URL',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'Base URL of the service (e.g., "https://service.example.com"). On login, the user will be redirected to this URL (plus any callback path set below).',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'required' => true,
            ])
            ->add('callback_path', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Callback Path',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'Path to append to the end of the redirect URL after login, if any (e.g., "/login/callback").',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'required' => false,
            ])
            ->add('legacy_login_path', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Legacy Login Path',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'Path to the service\'s internal login form (e.g., "/user/login"). If set, this will be available in the WAYF menu as "Other (Legacy Login)."',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary'],
                'label' => 'Add Service',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}