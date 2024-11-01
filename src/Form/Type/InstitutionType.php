<?php

namespace App\Form\Type;

use App\Entity\Institution;
use App\Controller\InstitutionController;
use Exception;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This class defines a form for adding an IdP entity
 */

class InstitutionType extends AbstractType
{
    private string $memcachedHost;

    private string $memcachedPort;

    /**
     * InstitutionType constructor
     *
     * @param string $memcachedHost
     * @param string $memcachedPort
     */
    public function __construct(string $memcachedHost, string $memcachedPort)
    {
        $this->memcachedHost = $memcachedHost;
        $this->memcachedPort = $memcachedPort;
    }
    /**
     * Builds Idp entity form
     *
     * @param FormBuilderInterface $builder
     * @param array<string, mixed> $options
     *
     * @throws Exception
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = $this->getIdpChoices();  // Get IdP choices

        $builder  // Build the form
            ->add('index', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Index',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'Unique identifier for the institution (e.g., WRLC = "wr").',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'required' => true,
            ])
            ->add('name', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Name',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'Full name of the institution. (This will display in the WAYF menu unless an alternate label is provided below.)',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'required' => true,
            ])
            ->add('wayf_label', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'WAYF Label',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'Alternate institution name to display in the WAYF menu, if different than "Name."',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'required' => false,
            ])
            ->add('entity_id', ChoiceType::class, [
                'attr' => ['class' => 'form-select'],
                'label' => 'IdP',
                'label_attr' => ['class' => 'form-label'],
                'choices' => $choices,
                'placeholder' => '-- Select IdP --',
                'help' => 'Select the trusted SimpleSAMLPHP IdP for the institution.',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'required' => true,
            ])
            ->add('alma_location_code', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Alma Location Code',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'The Alma location code for the institution\'s IZ. This will be the portion of the Alma IZ subdomain following "wrlc-" (e.g., "cua" for Catholic University of America\'s "wrlc-cua" subdomain.).',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'required' => true,
            ])
            ->add('mail_attribute', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Mail Attribute',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'The attribute in the IdP metadata that contains the user\'s email address (e.g., upn, mail, http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress).',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'required' => true,
            ])
            ->add('name_attribute', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Name Attribute',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'The attribute in the IdP metadata that contains the user\'s full  or last name (e.g., surname, sn, http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname).',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'required' => false,
            ])
            ->add('first_name_attribute', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'First Name Attribute',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'The attribute in the IdP metadata that contains the user\'s first name (e.g., givenname, http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname).',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'required' => false,
            ])
            ->add('id_attribute', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'ID Attribute',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'The attribute in the IdP metadata that contains the user\'s unique identifier (e.g., cid, uid, http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name).',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'required' => false,
            ])
            ->add('special_transform', ChoiceType::class, [
                'attr' => ['class' => 'form-select'],
                'label' => 'Special Transform for UserID?',
                'label_attr' => ['class' => 'form-label'],
                'choices' => [
                    'No' => false,
                    'Yes' => true,
                ],
                'placeholder' => '-- Select Yes or No --',
                'help' => 'Whether the special transform function (splitting the IdP User ID at the "@" symbol and using the first part as the User ID) should be used for this institution.',
                'help_attr' => ['class' => 'mb-3 text-secondary form-text'],
                'required' => true,
            ])
            ->add('save', SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary'],
                'label' => 'Add Institution'
            ])
        ;
    }

    /**
     *
     * Links form to Idp entity
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Institution::class,
        ]);
    }

    /**
     *
     * Gets IdP choices from SSP configuration
     *
     * SSP stores IdP metadata in PHP files in the metadata directory. This method reads the metadata files and returns the entity IDs as choices for the form.
     *
     * @throws Exception
     * @return array<string, string>
     */
    private function getIdpChoices(): array
    {
        $controller = new InstitutionController($this->memcachedHost, $this->memcachedPort);
        $metadata = $controller->getIdps();  // Get IdPs from the IdP controller
        $items = [];  // Initialize the items array
        foreach ($metadata as $idp) {  // For each IdP
            if (is_array($idp)) {  // If the IdP is an array
                $entity_id = $idp['entityid'];
                $name = $idp['entityid'];
                if (array_key_exists('name', $idp)) {
                    $name = $idp['name']['en'] . ' - ' . $idp['entityid'];
                }
                $items[$name] = $entity_id;
            }
        }
        ksort($items);  // Sort the items array
        return $items;  // Return the items array
    }
}
