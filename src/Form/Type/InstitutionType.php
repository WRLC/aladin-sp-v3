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
    /**
     * Builds Idp entity form
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     * @throws Exception
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = $this->getIdpChoices();  // Get IdP choices

        $builder  // Build the form
            ->add('index', TextType::class, ['label' => 'Index', 'required' => true,])
            ->add('name', TextType::class, ['label' => 'Name', 'required' => true])
            ->add('wayf_label', TextType::class, ['label' => 'WAYF Label', 'required' => false, 'help' => 'If different than "Name"'])
            ->add('entity_id', ChoiceType::class, ['label' => 'IdP', 'required' => true, 'choices' => $choices])
            ->add('alma_location_code', TextType::class, ['label' => 'Alma Location Code', 'required' => true])
            ->add('mail_attribute', TextType::class, ['label' => 'Mail Attribute', 'required' => true])
            ->add('name_attribute', TextType::class, ['label' => 'Name Attribute', 'required' => false])
            ->add('first_name_attribute', TextType::class, ['label' => 'First Name Attribute', 'required' => false])
            ->add('id_attribute', TextType::class, ['label' => 'ID Attribute', 'required' => false])
            ->add('save', SubmitType::class, ['label' => 'Add Institution'])
        ;
    }

    /**
     *
     * Links form to Idp entity
     *
     * @param OptionsResolver $resolver
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
     * @return array
     */
    private function getIdpChoices(): array
    {
        $controller = new InstitutionController();
        $metadata = $controller->getIdps();  // Get IdPs from the IdP controller
        $items = [];  // Initialize the items array
        foreach ($metadata as $idp) {  // For each IdP
            if (is_array($idp)) {  // If the IdP is an array
                $entity_id = $idp['entityid'];
                if (array_key_exists('name', $idp)) {
                    $name = $idp['name']['en'];
                }
                else {
                    $name = $idp['entityid'];
                }
                $items[$name] = $entity_id;
            }
        }
        ksort($items);  // Sort the items array
        return $items;  // Return the items array

    }
}