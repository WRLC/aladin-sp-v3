<?php

namespace App\Controller;

use App\Entity\Institution;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use SimpleSAML\Auth\Simple;
use SimpleSAML\Configuration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class AuthnController
 */
class AuthnController extends AbstractController
{
    private string $svcProvider;

    /**
     * AuthnController constructor.
     *
     * @param string $svcProvider
     */
    public function __construct(string $svcProvider)
    {
        $this->svcProvider = $svcProvider;
    }
    /**
     * Authenticate the user
     *
     * @param Institution $institution
     *
     * @return array<string, mixed>|Exception|ContainerExceptionInterface
     *
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function authnUser(Institution $institution): array | Exception | ContainerExceptionInterface
    {
        dump($this->svcProvider);
        // Get the service provider name
        $svcp = $this->svcProvider;  // Get the service provider name

        $authSource = new Simple($svcp, Configuration::getConfig());  // Create a new SimpleSAML_Auth_Simple object
        try {
            $authSource->requireAuth(['saml:idp' => $institution->getEntityId(),]);  // Require authentication
        } catch (Exception $e) {
            return $e;
        }
        return $authSource->getAttributes();  // Return the user attributes
    }
}
