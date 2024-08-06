<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AllConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($options['configs'] as $config) {
            $builder->add($config->getName(), ConfigType::class, [
                'label' => $config->getLabel(),
                'data' => $config,
            ]);
        }
        $builder->add('submit', SubmitType::class, ['label' => 'Save', 'attr' => ['class' => 'btn btn-primary']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'configs' => [],
        ]);
    }

}