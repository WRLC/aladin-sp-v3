<?php

$dotenv = Dotenv\Dotenv::createImmutable( __DIR__ . '/../../../', '.env.local');
$dotenv->safeLoad();

$config = array(
    // This is a authentication source which handles admin authentication.
    'admin' => array(
        // The default is to use core:AdminPassword, but it can be replaced with
        // any authentication source.
        'core:AdminPassword',
    ),

    'default-sp' => array(
        'saml:SP',
        'entityID' => 'https://simplesamlphp.wrlc.localhost/shibboleth',
        'privatekey' => $_ENV['SSP_CERTDIR'] . '/saml.pem',
        'certificate' => $_ENV['SSP_CERTDIR'] . '/saml.crt',
	    'NameIDPolicy' => [
		    'Format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
		    'AllowCreate' => false,
	    ],
        'SingleLogoutService' => [
            [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                'Location' => 'https://simplesamlphp.wrlc.localhost/simplesaml/module.php/saml/sp/saml2-logout.php/default-sp',
            ],
        ],
        'AssertionConsumerService' => [
            [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                'Location' => 'https://simplesamlphp.wrlc.localhost/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp',
                'index' => 0,
            ],
            [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
                'Location' => 'https://simplesamlphp.wrlc.localhost/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp',
                'index' => 1,
            ],
        ],
        'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
        'contacts' => [
            [
                'emailAddress' => $_ENV['SSP_TECHNICALCONTACT_EMAIL'],
                'givenName' => $_ENV['SSP_TECHNICALCONTACT_NAME'],
                'contactType' => 'technical',
            ],
        ],
    ),
);
