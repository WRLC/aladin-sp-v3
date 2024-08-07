<?php

namespace App\DataFixtures;

use App\Entity\Config;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $config_names = [
            [
                'name' => 'patron_authorization_url',
                'label' => 'Patron Authorization URL',
            ],
            [
                'name' => 'memcached_host',
                'label' => 'Memcached Host',
            ],
            [
                'name' => 'memcached_port',
                'label' => 'Memcached Port',
            ],
            [
                'name' => 'service_provider_name',
                'label' => 'SSP Service Provider',
            ],
            [
                'name' => 'cookie_prefix',
                'label' => 'Cookie Prefix',
            ],
        ];

        foreach ($config_names as $config_name) {
            $config = new Config();
            $config->setName($config_name['name']);
            $config->setLabel($config_name['label']);
            $manager->persist($config);
        }

        $manager->flush();
    }
}
