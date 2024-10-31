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

/**
 * Class Institution
 */
class InstitutionServiceType extends AbstractType
{
    /**
     * Build the form
     *
     * @param FormBuilderInterface $builder
     * @param array<string, mixed> $options
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($builder->getData()->getAuthzType() == 'user_id') {
            $authzType = 'User IDs';
            $authzTypeHelp = 'The specific IdP User IDs that are authorized to access this service. (Each value should be added separately.)';
        } elseif ($builder->getData()->getAuthzType() == 'user_role') {
            $authzType = 'Roles';
            $authzTypeHelp = 'The specific Alma Roles (by ID number, not label) that are authorized to access this service. (Each value should be added separately.)';
        } elseif ($builder->getData()->getAuthzType() == 'user_group') {
            $authzType = 'Groups';
            $authzTypeHelp = 'The specific Alma Groups (by name, not label) that are authorized to access this service. (Each value should be added separately.)';
        } else {
            $authzType = '';
            $authzTypeHelp = '';
        }

        $authzMemberLabel = 'Authorized ' . $authzType;
        $authzMembersHelp = $authzTypeHelp;

        $institution = $options['institution'];
        $service = $options['service'];
        $type = $options['type'];

        if ($type == 'add') {
            $label = 'Add Institutional Service';
        } else {
            $label = 'Update Institutional Service';
        }
        $builder
            ->add('institution', EntityType::class, [
                'label' => 'Institution',
                'label_attr' => ['class' => 'form-label'],
                'required' => true,
                'class' => Institution::class,
                'choice_label' => 'name',
                'data' => $institution,
            ])
            ->add('service', EntityType::class, [
                'label' => 'Service',
                'label_attr' => ['class' => 'form-label'],
                'required' => true,
                'class' => Service::class,
                'choice_label' => 'name',
                'data' => $service,
            ])
            ->add('authz_type', ChoiceType::class, [
                'attr' => ['class' => 'form-select'],
                'label' => 'Authorization Type',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'The type of authorization required to access this service (e.g., any authenticated users, any Alma IZ users, only certain user IDs, members of specific Alma roles or groups, etc.).',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'choices' => [
                    'None (any user authenticated by IdP)' => 'none',
                    'IdP User ID (only users with matching User ID)' => 'user_id',
                    'Alma Role (only users with matching user_role)' => 'user_role',
                    'Alma Group (only users with matching user_group)' => 'user_group',
                    'Any Alma User (any user in Alma IZ)' => 'any_alma',
                ],
                'placeholder' => '-- Select Authorization Type --',
                'required' => true
            ])
            ->add('authz_members', CollectionType::class, [
                'label' => $authzMemberLabel,
                'label_attr' => ['class' => 'mb-1 form-label'],
                'help' => $authzMembersHelp,
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'entry_type' => AuthzMemberType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_options' => [
                    'label' => false,
                    'attr' => ['class' => 'mb-3 col col-11'],
                ]
            ])
            ->add('id_attribute', ChoiceType::class, [
                'attr' => ['class' => 'form-select'],
                'label' => 'ID Attribute',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'The IdP metadata attribute that will be transmitted to the service as the username.',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'required' => true,
                'choices' => [
                    'Email' => 'mail_attribute',
                    'User ID' => 'id_attribute'
                ],
                'placeholder' => '-- Select ID Attribute --',
            ])
            ->add('save', SubmitType::class, ['label' => $label, 'attr' => ['class' => 'btn btn-primary']])
        ;
    }

    /**
     * Configure the form options
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
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
