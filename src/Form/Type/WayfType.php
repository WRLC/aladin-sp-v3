<?php

namespace App\Form\Type;

use App\Entity\Institution;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WayfType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // Add the institution field
            ->add('institution', EntityType::class, [
                'attr' => ['class' => 'form-select mb-3'],
                'label' => ' Select your affiliation to log into the <strong>' . $options['service'] . '</strong>',
                'label_attr' => ['class' => 'form-label'],
                'label_html' => true,
                'class' => Institution::class,
                'choices' => $options['institutions'],
                'choice_value' => 'index',
                'choice_label' => function ($choice) {
                    if ($choice->getWayfLabel() !== null and $choice->getWayfLabel() !== '') {
                        return $choice->getWayfLabel();
                    }
                    return $choice->getName();
                },
                'placeholder' => '-- Select institution --',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'institutions' => null,  // Allow institutions as $options parameter
            'service' => null,  // Allow service as $options parameter
        ]);
    }
}