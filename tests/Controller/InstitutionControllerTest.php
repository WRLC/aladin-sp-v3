<?php

namespace App\Tests\Controller;

use SimpleSAML\Configuration;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class InstitutionControllerTest
 */
class InstitutionControllerTest extends WebTestCase
{
    /**
     * @return void
     *
     * @SuppressWarnings("PHPMD.StaticAccess")
     */
    public function testSomething(): void
    {
        Configuration::setConfigDir(getenv('ROOT_DIR') . getenv('CONFIG_DIR'));

        $client = static::createClient();
        $client->request('GET', getenv('NGINX_HOST') . '/authZ?institution=wr&service=service_desk&user=' . getenv('TEST_USER'));


        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Access Granted');
    }
}
