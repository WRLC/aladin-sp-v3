<?php
/**
 * SAML 2.0 remote IdP metadata for simpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote 
 */

/* UDC's new Ellucian Ethos Identity management */
$metadata['https://myqlidp.udc.edu'] = [
    'name' => array (
        'en' => 'UDC Ethos Identity',
    ),
    'entityid' => 'https://myqlidp.udc.edu',
    'contacts' => [],
    'metadata-set' => 'saml20-idp-remote',
    'SingleSignOnService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => 'https://idp.quicklaunchsso.com/samlsso?tenantDomain=udc.edu',
        ],
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'https://idp.quicklaunchsso.com/samlsso?tenantDomain=udc.edu',
        ],
    ],
    'SingleLogoutService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
            'Location' => 'https://idp.quicklaunchsso.com/samlsso?tenantDomain=udc.edu',
            'ResponseLocation' => 'https://idp.quicklaunchsso.com/samlsso?tenantDomain=udc.edu',
        ],
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => 'https://idp.quicklaunchsso.com/samlsso?tenantDomain=udc.edu',
            'ResponseLocation' => 'https://idp.quicklaunchsso.com/samlsso?tenantDomain=udc.edu',
        ],
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'https://idp.quicklaunchsso.com/samlsso?tenantDomain=udc.edu',
            'ResponseLocation' => 'https://idp.quicklaunchsso.com/samlsso?tenantDomain=udc.edu',
        ],
    ],
    'ArtifactResolutionService' => [],
    'NameIDFormats' => [
        'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
    ],
    'keys' => [
        [
            'encryption' => false,
            'signing' => true,
            'type' => 'X509Certificate',
            'X509Certificate' => "\n"
                .'MIIDSTCCAjGgAwIBAgIEW3k1szANBgkqhkiG9w0BAQsFADBVMQswCQYDVQQGEwJV'."\n"
                .'UzELMAkGA1UECBMCTkoxDzANBgNVBAcTBkplcnNleTEMMAoGA1UEChMDdWRjMQww'."\n"
                .'CgYDVQQLEwN1ZGMxDDAKBgNVBAMTA3VkYzAeFw0yMDA5MjkwOTM3MDFaFw00MTA3'."\n"
                .'MjEwOTM3MDFaMFUxCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJOSjEPMA0GA1UEBxMG'."\n"
                .'SmVyc2V5MQwwCgYDVQQKEwN1ZGMxDDAKBgNVBAsTA3VkYzEMMAoGA1UEAxMDdWRj'."\n"
                .'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhgA9VBBijgz16Ncl8bFC'."\n"
                .'z3V+B8O8WoiQOvberLl/nB2fJibkCTggyHR/JpzSPOLRyohH8ChyzTO0IJTicHJq'."\n"
                .'bCefNshcyA56z7Sg5uvbKWlPCQyXDASkYLTwHx+iw/Ih6rv8L7hv0cG0pimZAkRu'."\n"
                .'F00srcR0u0I07+Dx/Jeq4tChOUdQTJPCYYgpTmEanuDOz440dttPJCID1VAuE6kt'."\n"
                .'afwXYgX1fDzDsmtjlLKdzZ+TaQdbY+J4iwsPIR6E6VuSYE8pMiWEJ5elJ6faGwyP'."\n"
                .'8/tK7C7+Z4KQyWKokOs4QvN6bcpAcwurlzXjfqVmGa0wz2iW9qyLzAGOmgy9xy1L'."\n"
                .'kQIDAQABoyEwHzAdBgNVHQ4EFgQUe3BMsKB1THerIdpCaUwfsf0HWOUwDQYJKoZI'."\n"
                .'hvcNAQELBQADggEBAEcqxfKku3AjKyoCRLsNzgJUVTM2Uydmz8iCMMICA9hgOT4t'."\n"
                .'/d5tlMYzjaZon1GT7dBlykOZCjSHr+LwFq4ILqaj9DedumhpBjspLmVxb7aX2w08'."\n"
                .'ZWnbZUCB0z1SoRjaWqqfmEzmqqBqJwxOqreq71TOWr5ock+m8a3yTHTeQ2InAvXD'."\n"
                .'Gw0F8qDUc0wOWAm+lVV7lPUQR4OZzIYANfCdCEB15UeP1UFiGngFLBv8RiPyQAlQ'."\n"
                .'/3ANJf4cMhq5EzZhOhmPqQg06LX7MIEZJO9cKmhQzuv8hbbXKu2lc66va+8+aigB'."\n"
                .'8m8D+WMQwQg21ZHLwUGm5U7s6frgFPB/gz/nImU='."\n"
                .'          ',
        ],
    ],
];

/*
 * GW new Azure AD IdP - check whether there is a refresh URL after testing
 */
$metadata['https://sts.windows.net/d689239e-c492-40c6-b391-2c5951d31d14/'] = [
    'name' => array (
        'en' => 'GWU Azure AD',
    ),
    'entityid' => 'https://sts.windows.net/d689239e-c492-40c6-b391-2c5951d31d14/',
    'contacts' => [],
    'metadata-set' => 'saml20-idp-remote',
    'SingleSignOnService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'https://login.microsoftonline.com/d689239e-c492-40c6-b391-2c5951d31d14/saml2',
        ],
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => 'https://login.microsoftonline.com/d689239e-c492-40c6-b391-2c5951d31d14/saml2',
        ],
    ],
    'SingleLogoutService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'https://login.microsoftonline.com/d689239e-c492-40c6-b391-2c5951d31d14/saml2',
        ],
    ],
    'ArtifactResolutionService' => [],
    'NameIDFormats' => [],
    'keys' => [
        [
            'encryption' => false,
            'signing' => true,
            'type' => 'X509Certificate',
            'X509Certificate' => 'MIIC8DCCAdigAwIBAgIQE9cMhEEhIIhCnSvJWX3t4TANBgkqhkiG9w0BAQsFADA0MTIwMAYDVQQDEylNaWNyb3NvZnQgQXp1cmUgRmVkZXJhdGVkIFNTTyBDZXJ0aWZpY2F0ZTAeFw0yMzEyMDUxNzA3NDdaFw0yNjEyMDUxNzA3NTJaMDQxMjAwBgNVBAMTKU1pY3Jvc29mdCBBenVyZSBGZWRlcmF0ZWQgU1NPIENlcnRpZmljYXRlMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsgygqM3DnvVvbVMU5YWBIDd+Ga/Em+8kCTzRbFJNWHRK9K1n2W4Rr5Z0eorapA4rlHwDszckP+O+0UbS41mTQ9+IHMGEJ96+XxmZrj8ioXpgHfRCxlYj7z06n5vtgCjlZNjjjpkNXQ/kM4e7nj7ocJK6bfDyalwbO87y5RKnPTmRXgJBD/zHR1HNdNk8bZhzY6/363iqCwggPFXF/WWesTyI/ACT6fImwYg7JE6ouszyEZbaoSBwVdvaiACSuDSCZ4feJhfi5jzQLRIvooqluphqZ/lxq2WxUomSwFqkVG27eJCc+0Jvpact53UU/l2XMT1ttB9lb3SI13/HTFCw1QIDAQABMA0GCSqGSIb3DQEBCwUAA4IBAQCjbJtMZSifDLYPOPtp/DqbwixibIUU5Y0p1dprXXlatV44AlLYz0TFPzDznDLcxKdKqFcdY2Dy/3hQyc7FvwZ2Uss9zWmU9jTM3FOVMMwC6HL0tu+HBIEnusX5ayHCVRbk4ODrcdnX/9EiPe9g4uxWO7HEMI+jCrwtE+CfaGPGiT1q1JXftkJm0S+8P6fAz+vGQeffi5cxz+aPosSMWuYfskXMyLD0lkgf+HtazF5TQpveTkka/jMOPwxh41LglmhxtCzBI6nLv5Qn6TNhOUIyTAdapJthbnnMBnXjfI0c/a30A0RmzjLuOAss7/55CIuTsUiKCcWB9btC8HN2mS+O',
        ],
    ],
];

/*
 * WRLC added to nightly refresh - don 20171002
 */
$metadata['https://sts.windows.net/f79661a6-dba8-42a1-8998-aa96adefb592/'] = [
    'name' => array (
        'en' => 'WRLC Azure AD',
    ),
    'entityid' => 'https://sts.windows.net/f79661a6-dba8-42a1-8998-aa96adefb592/',
    'contacts' => [],
    'metadata-set' => 'saml20-idp-remote',
    'SingleSignOnService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'https://login.microsoftonline.com/f79661a6-dba8-42a1-8998-aa96adefb592/saml2',
        ],
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => 'https://login.microsoftonline.com/f79661a6-dba8-42a1-8998-aa96adefb592/saml2',
        ],
    ],
    'SingleLogoutService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'https://login.microsoftonline.com/f79661a6-dba8-42a1-8998-aa96adefb592/saml2',
        ],
    ],
    'ArtifactResolutionService' => [],
    'NameIDFormats' => [],
    'keys' => [
        [
            'encryption' => false,
            'signing' => true,
            'type' => 'X509Certificate',
            'X509Certificate' => 'MIIC8DCCAdigAwIBAgIQEr4sk8YX/7NJbH3XUipjDDANBgkqhkiG9w0BAQsFADA0MTIwMAYDVQQDEylNaWNyb3NvZnQgQXp1cmUgRmVkZXJhdGVkIFNTTyBDZXJ0aWZpY2F0ZTAeFw0yMzEyMjIxNTM1MDBaFw0yNjEyMjIxNTM1MDBaMDQxMjAwBgNVBAMTKU1pY3Jvc29mdCBBenVyZSBGZWRlcmF0ZWQgU1NPIENlcnRpZmljYXRlMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyfI/YJbCuXkC7SoFl+C2Af4QVUbjTnCArukgjSZCdr0NsZxkQYT2tDmXez2tEuV2FVA4SHhPqDrhX+muMzgBOLEB8mldgijcBJMMDilnbNh4CldQfel6F4HrkzlR9jP5NNmj2bYTVAonO2C4JlszqNZI8nB4FcePHmWix81nUh7t0pGEtvOtYGR943LbhJhqp4YR40vwdaWcBw8MB3IM0od/ugEZy2z473or5EFfQjP5w+L0Jo1YJtDnDgB15TL7Jyjkx0NTGvhq2Wjs4xWppvgMzVbAxFafZxg7cqVa5RNlYlP5zZ2Sp7hxreh3pJKvV6ASV6xg5hteVWYrh6TskQIDAQABMA0GCSqGSIb3DQEBCwUAA4IBAQAmoNzDlJyiT9aHQAeUMuUNBmXdJ2dEqW1+x5h2u4KgqQAyYZGJ/RSHkDhrgih8rgbKqJ2KQd9sBSjqbP7qXjRitEU87vp8gQ1c90tWRSIpMb+kC0Y2fo4arjnS/FS5FCZ5S4aZYz3OIuvpMRuQr5skzqjonhdSjZ1IsnGqOCL8Z7ne5lIMuazsmJ/cjqQn/L3xzOJ4o6dnuyHTYiN9zbzb57QaZLKbyVikK5zK8abdejTKsT219FfccZ0wXZgjLMuDBwvqiNx5Jj4CQOJT850F66EC3tLk8S7IzCSlO1fP3po2LvOG4O/gDZq8g6myZp4BxHXPiln7kmO0sg6LPWdw',
        ],
    ],
];
$metadata['http://idp.wrlc.localhost/saml/metadata.xml'] = [
    'name' => array (
        'en' => 'WRLC Flask IdP',
    ),
    'entityid' => 'http://idp.wrlc.localhost/saml/metadata.xml',
    'contacts' => [],
    'metadata-set' => 'saml20-idp-remote',
    'SingleSignOnService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'http://idp.wrlc.localhost/saml/login/',
        ],
    ],
    'SingleLogoutService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'http://idp.wrlc.localhost/saml/logout/',
        ],
    ],
    'ArtifactResolutionService' => [],
    'NameIDFormats' => [
        'urn:oasis:names:tc:SAML:2.0:nameid-format:email',
    ],
    'keys' => [
        [
            'encryption' => false,
            'signing' => true,
            'type' => 'X509Certificate',
            'X509Certificate' => 'MIIDfzCCAmegAwIBAgIUJLA4Gk2B5Q7NUy/YiOORK6UDya8wDQYJKoZIhvcNAQELBQAwTzELMAkGA1UEBhMCVVMxCzAJBgNVBAgMAk1EMRcwFQYDVQQHDA5VcHBlciBNYXJsYm9ybzENMAsGA1UECgwEV1JMQzELMAkGA1UECwwCSVQwHhcNMjQwNTA3MTI1MDMxWhcNMjUwNTA3MTI1MDMxWjBPMQswCQYDVQQGEwJVUzELMAkGA1UECAwCTUQxFzAVBgNVBAcMDlVwcGVyIE1hcmxib3JvMQ0wCwYDVQQKDARXUkxDMQswCQYDVQQLDAJJVDCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBALn0wZowpv1GCObSZ+ZfHK4R67Os6n+J7Z0Qu/AZAJmxHd0nlMoB/cqruNV5cfLefHOeRlJViQqbzN4lG+kqr6dqcQ3v4wo3nBn5QS/x9R2xPSTfS9t8bdKLRBSSnLQeF7eA9TvcHa9ktNJ3wYG4t66AeIPirWgnIDYDCbQZyGa7Z7UJitDzmmeXpsA4Udst49qHbh+CSVI+/o2FG2N4ycbJp7kc5+4grgAvCe0Lsw4KRxD5DQbQpJJbOoum8sDQk8g2e7KtBpuIO7RLq7RtK2sZMqvMWRBklKDgxP/bFFE/q9ZbdTl/QWW+jU1usVvpP/56483EVUk3KCUTlpCS54UCAwEAAaNTMFEwHQYDVR0OBBYEFGsgLkXQMH/eBt6eMPkb1DjBGDTsMB8GA1UdIwQYMBaAFGsgLkXQMH/eBt6eMPkb1DjBGDTsMA8GA1UdEwEB/wQFMAMBAf8wDQYJKoZIhvcNAQELBQADggEBAB/W8WcgK1m8e/DXqdRjuB/ZN1G5V7POmlTLs+bGVxoD0wWZOaKxHJLcK1qS5vN9/E/e9AC4bapxi007NMH/A/6eK+JhQyv/HE1IPsCjcc5j6vriNklQh6K7lVItcriWmFZ2q8EwKPn87EllhWEdH6l7nxYfYry+EgL+NmGVvbPFqG2bUDJffI1VPRl6spfP1QrTg1EjhOgfPFJm5tuE0f+Oaa+X2N+NfTfCwNtlL4YFYahEZbZduQiUzkPrTnRWFNIYdUi3B3b4JfDnsLXWaVSFLOGQskpMvk+Zlbs2KCyFBfZIMF+HAkwjVB9GP3BNG6C+NQqG6p/gQ0cTCHN6a44=',
        ],
        [
            'encryption' => true,
            'signing' => false,
            'type' => 'X509Certificate',
            'X509Certificate' => 'MIIDfzCCAmegAwIBAgIUJLA4Gk2B5Q7NUy/YiOORK6UDya8wDQYJKoZIhvcNAQELBQAwTzELMAkGA1UEBhMCVVMxCzAJBgNVBAgMAk1EMRcwFQYDVQQHDA5VcHBlciBNYXJsYm9ybzENMAsGA1UECgwEV1JMQzELMAkGA1UECwwCSVQwHhcNMjQwNTA3MTI1MDMxWhcNMjUwNTA3MTI1MDMxWjBPMQswCQYDVQQGEwJVUzELMAkGA1UECAwCTUQxFzAVBgNVBAcMDlVwcGVyIE1hcmxib3JvMQ0wCwYDVQQKDARXUkxDMQswCQYDVQQLDAJJVDCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBALn0wZowpv1GCObSZ+ZfHK4R67Os6n+J7Z0Qu/AZAJmxHd0nlMoB/cqruNV5cfLefHOeRlJViQqbzN4lG+kqr6dqcQ3v4wo3nBn5QS/x9R2xPSTfS9t8bdKLRBSSnLQeF7eA9TvcHa9ktNJ3wYG4t66AeIPirWgnIDYDCbQZyGa7Z7UJitDzmmeXpsA4Udst49qHbh+CSVI+/o2FG2N4ycbJp7kc5+4grgAvCe0Lsw4KRxD5DQbQpJJbOoum8sDQk8g2e7KtBpuIO7RLq7RtK2sZMqvMWRBklKDgxP/bFFE/q9ZbdTl/QWW+jU1usVvpP/56483EVUk3KCUTlpCS54UCAwEAAaNTMFEwHQYDVR0OBBYEFGsgLkXQMH/eBt6eMPkb1DjBGDTsMB8GA1UdIwQYMBaAFGsgLkXQMH/eBt6eMPkb1DjBGDTsMA8GA1UdEwEB/wQFMAMBAf8wDQYJKoZIhvcNAQELBQADggEBAB/W8WcgK1m8e/DXqdRjuB/ZN1G5V7POmlTLs+bGVxoD0wWZOaKxHJLcK1qS5vN9/E/e9AC4bapxi007NMH/A/6eK+JhQyv/HE1IPsCjcc5j6vriNklQh6K7lVItcriWmFZ2q8EwKPn87EllhWEdH6l7nxYfYry+EgL+NmGVvbPFqG2bUDJffI1VPRl6spfP1QrTg1EjhOgfPFJm5tuE0f+Oaa+X2N+NfTfCwNtlL4YFYahEZbZduQiUzkPrTnRWFNIYdUi3B3b4JfDnsLXWaVSFLOGQskpMvk+Zlbs2KCyFBfZIMF+HAkwjVB9GP3BNG6C+NQqG6p/gQ0cTCHN6a44=',
        ],
    ],
];
