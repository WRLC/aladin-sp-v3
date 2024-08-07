<?php

namespace App\Form\Type;

use App\Entity\Config;
use SimpleSAML\Configuration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigType extends AbstractType
{
    /**
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('patron_authorization_url', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => $this->getConfigByName($options['configs'], 'patron_authorization_url')->getLabel(),
                'label_attr' => ['class' => 'form-label'],
                'data' => $this->getConfigByName($options['configs'], 'patron_authorization_url')->getValue(),
                'help' => '<small><em>The URL of the Alma Patron Authorization Service API, including full path to endpoint (not including query parameters).</em></small>',
                'help_attr' => ['class' => 'mb-3 text-secondary'],
                'help_html' => true,
            ])
            ->add('service_provider_name', ChoiceType::class, [
                'attr' => ['class' => 'form-select'],
                'label' => $this->getConfigByName($options['configs'], 'service_provider_name')->getLabel(),
                'label_attr' => ['class' => 'form-label'],
                'choices' => Configuration::getConfig('authsources.php')->getOptions(),
                'choice_label' => fn($choice) => $choice,
                'data' => $this->getConfigByName($options['configs'], 'service_provider_name')->getValue(),
                'help' => '<small><em>The name of the SimpleSAMLphp Service Provider to use for authentication (e.g., default-sp).</em></small>',
                'help_attr' => ['class' => 'mb-3 text-secondary'],
                'help_html' => true,
            ])
            ->add('memcached_host', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => $this->getConfigByName($options['configs'], 'memcached_host')->getLabel(),
                'label_attr' => ['class' => 'form-label'],
                'data' => $this->getConfigByName($options['configs'], 'memcached_host')->getValue(),
                'help' => '<small><em>The hostname, URL, or IP address of the Memcached server.</em></small>',
                'help_attr' => ['class' => 'mb-3 text-secondary'],
                'help_html' => true,
            ])
            ->add('memcached_port', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => $this->getConfigByName($options['configs'], 'memcached_port')->getLabel(),
                'label_attr' => ['class' => 'form-label'],
                'data' => $this->getConfigByName($options['configs'], 'memcached_port')->getValue(),
                'help' => '<small><em>The server port for the Memcached service.</em></small>',
                'help_attr' => ['class' => 'mb-3 text-secondary'],
                'help_html' => true,
            ])
            ->add('cookie_prefix', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => $this->getConfigByName($options['configs'], 'cookie_prefix')->getLabel(),
                'label_attr' => ['class' => 'form-label'],
                'data' => $this->getConfigByName($options['configs'], 'cookie_prefix')->getValue(),
                'help' => '<small><em>The prefix to use for service cookie names. This will be prepended to the service slug.</em></small>',
                'help_attr' => ['class' => 'mb-3 text-secondary'],
                'help_html' => true,
            ])
            ->add('submit', SubmitType::class, ['label' => 'Save', 'attr' => ['class' => 'btn btn-primary']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'configs' => [],
        ]);
    }

    public function getConfigByName(array $configs, string $name): ?Config
    {
        foreach ($configs as $config) {
            if ($config->getName() === $name) {
                return $config;
            }
        }

        return null;
    }

}