<?php

namespace App\Form\Type;

use App\Entity\Institution;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WaygType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        dump($options);
        $builder

            // Add the service field
            ->add('service', EntityType::class, [
                'attr' => ['class' => 'form-select mb-3'],
                'label' => ' Select a service to log into for<br /><strong>' . $options['institution'] . '</strong>',
                'label_attr' => ['class' => 'form-label'],
                'label_html' => true,
                'class' => Institution::class,
                'choices' => $options['services'],
                'choice_value' => 'slug',
                'choice_label' => 'name',
                'placeholder' => '-- Select service --',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'institution' => null,  // Allow institutions as $options parameter
            'services' => null,  // Allow service as $options parameter
        ]);
    }
}