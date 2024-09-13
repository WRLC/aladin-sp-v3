<?php

namespace App\Tests\Controller;

use SimpleSAML\Configuration;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InstitutionControllerTest extends WebTestCase
{

    public function testSomething(): void
    {
        Configuration::setConfigDir($_ENV['CONFIG_DIR']);

        $client = static::createClient();
        $client->request('GET', $_ENV['NGINX_HOST'] . '/authZ?institution=wr&service=service_desk&user=' . $_ENV['TEST_USER']);


        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Access Granted');
    }
}
