<?php

namespace App\Controller;

use App\Entity\Institution;
use Exception;
use SimpleSAML\Auth\Simple;
use SimpleSAML\Configuration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AuthnController extends AbstractController
{
    /**
     * Authenticate the user
     *
     * @param Institution $institution
     *
     * @return array|Response
     *
     * @throws Exception
     */
    public function authn_user(Institution $institution): array | Exception
    {
        // Get the service provider name
        $sp = $_ENV['SERVICE_PROVIDER_NAME'];  // Get the service provider name

        $auth_source = new Simple($sp, Configuration::getConfig());  // Create a new SimpleSAML_Auth_Simple object
        try {
            $auth_source->requireAuth(['saml:idp' => $institution->getEntityId(),]);  // Require authentication
        } catch (Exception $e) {
            return $e;
        }
        return $auth_source->getAttributes();  // Return the user attributes
    }

}