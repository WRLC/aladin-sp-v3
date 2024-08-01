<?php

namespace App\Form\Type;

use App\Entity\Institution;
use App\Entity\InstitutionService;
use App\Entity\Service;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstitutionServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $institution = $options['institution'];
        $service = $options['service'];
        $type = $options['type'];

        if ($type == 'add') {
            $label = 'Add Institutional Service';
        }
        else {
            $label = 'Update Institutional Service';
        }
        $builder
            ->add('institution', EntityType::class, ['label' => 'Institution', 'required' => true, 'class' => Institution::class, 'choice_label' => 'name', 'data' => $institution])
            ->add('service', EntityType::class, ['label' => 'Service', 'required' => true, 'class' => Service::class, 'choice_label' => 'name', 'data' => $service])
            ->add('authz_type', ChoiceType::class, [
                'label' => 'Authorization Type',
                'choices' => [
                    'None (any user authenticated by IdP)' => 'none',
                    'IdP User ID (only users with matching User ID)' => 'user_id',
                    'Alma Role (only users with matching user_role)' => 'user_role',
                    'Alma Group (only users with matching user_group)' => 'user_group',
                    'Any Alma User (any user in Alma IZ)' => 'any_alma',
                ],
                'required' => true
            ])
            ->add('authz_members', CollectionType::class, [
                'label' => 'Authorized Users/Roles/Groups',
                'label_attr' => ['class' => 'mb-1 form-label'],
                'help' => '<small><em>These values are ignored if Authorization Type is "None" or "Any Alma User."</em></small>',
                'help_attr' => ['class' => 'mb-3 text-secondary'],
                'help_html' => true,
                'entry_type' => AuthzMemberType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_options' => [
                    'label' => false,
                    'attr' => ['class' => 'mb-3 col col-11'],
                ]
            ])
            ->add('id_attribute', ChoiceType::class, ['label' => 'ID Attribute', 'required' => true, 'choices' =>['Email' => 'mail_attribute', 'User ID' => 'id_attribute']])
            ->add('save', SubmitType::class, ['label' => $label, 'attr' => ['class' => 'btn btn-primary']])
        ;
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InstitutionService::class,
            'institution' => null,
            'service' => null,
            'type' => null,
        ]);
    }
}