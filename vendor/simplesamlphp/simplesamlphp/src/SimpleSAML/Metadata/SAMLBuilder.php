<?php

declare(strict_types=1);

namespace SimpleSAML\Metadata;

use DOMElement;
use SAML2\Constants;
use SAML2\XML\idpdisc\DiscoveryResponse;
use SAML2\XML\md\AttributeAuthorityDescriptor;
use SAML2\XML\md\AttributeConsumingService;
use SAML2\XML\md\ContactPerson;
use SAML2\XML\md\EndpointType;
use SAML2\XML\md\EntityDescriptor;
use SAML2\XML\md\IDPSSODescriptor;
use SAML2\XML\md\IndexedEndpointType;
use SAML2\XML\md\Organization;
use SAML2\XML\md\RequestedAttribute;
use SAML2\XML\md\RoleDescriptor;
use SAML2\XML\md\SPSSODescriptor;
use SAML2\XML\mdattr\EntityAttributes;
use SAML2\XML\mdrpi\RegistrationInfo;
use SAML2\XML\mdui\DiscoHints;
use SAML2\XML\mdui\Keywords;
use SAML2\XML\mdui\Logo;
use SAML2\XML\mdui\UIInfo;
use SAML2\XML\saml\Attribute;
use SAML2\XML\saml\AttributeValue;
use SAML2\XML\shibmd\Scope;
use SimpleSAML\{Configuration, Module, Logger, Utils};
use SimpleSAML\Assert\{Assert, AssertionFailedException};

/**
 * Class for generating SAML 2.0 metadata from SimpleSAMLphp metadata arrays.
 *
 * This class builds SAML 2.0 metadata for an entity by examining the metadata for the entity.
 *
 * @package SimpleSAMLphp
 */

class SAMLBuilder
{
    /**
     * The EntityDescriptor we are building.
     *
     * @var \SAML2\XML\md\EntityDescriptor
     */
    private EntityDescriptor $entityDescriptor;


    /**
     * Initialize the SAML builder.
     *
     * @param string   $entityId The entity id of the entity.
     * @param int|null $maxCache The maximum time in seconds the metadata should be cached. Defaults to null
     * @param int|null $maxDuration The maximum time in seconds this metadata should be considered valid. Defaults
     * to null.
     */
    public function __construct(
        string $entityId,
        private ?int $maxCache = null,
        private ?int $maxDuration = null,
    ) {
        $this->entityDescriptor = new EntityDescriptor();
        $this->entityDescriptor->setEntityID($entityId);
    }


    /**
     * @param array $metadata
     */
    private function setExpiration(array $metadata): void
    {
        if (array_key_exists('expire', $metadata)) {
            if ($metadata['expire'] - time() < $this->maxDuration) {
                $this->maxDuration = $metadata['expire'] - time();
            }
        }

        if ($this->maxCache !== null) {
            $this->entityDescriptor->setCacheDuration('PT' . $this->maxCache . 'S');
        }
        if ($this->maxDuration !== null) {
            $this->entityDescriptor->setValidUntil(time() + $this->maxDuration);
        }
    }


    /**
     * Retrieve the EntityDescriptor element which is generated for this entity.
     *
     * @return \DOMElement The EntityDescriptor element of this entity.
     */
    public function getEntityDescriptor(): DOMElement
    {
        $xml = $this->entityDescriptor->toXML();
        $xml->ownerDocument->appendChild($xml);

        return $xml;
    }


    /**
     * Retrieve the EntityDescriptor as text.
     *
     * This function serializes this EntityDescriptor, and returns it as text.
     *
     * @param bool $formatted Whether the returned EntityDescriptor should be formatted first.
     *
     * @return string The serialized EntityDescriptor.
     */
    public function getEntityDescriptorText(bool $formatted = true): string
    {
        $xml = $this->getEntityDescriptor();
        if ($formatted) {
            $xmlUtils = new Utils\XML();
            $xmlUtils->formatDOMElement($xml);
        }

        $xml->ownerDocument->encoding = "utf-8";

        return $xml->ownerDocument->saveXML();
    }


    /**
     * Add extensions to the metadata.
     *
     * @param \SimpleSAML\Configuration    $metadata The metadata to get extensions from.
     * @param \SAML2\XML\md\RoleDescriptor $e Reference to the element where the Extensions element should be included.
     */
    private function addExtensions(Configuration $metadata, RoleDescriptor $e): void
    {
        if ($metadata->hasValue('hint.cidr')) {
            $a = new Attribute();
            $a->setName('hint.cidr');
            foreach ($metadata->getArray('hint.cidr') as $hint) {
                $a->addAttributeValue(new AttributeValue($hint));
            }
            $e->setExtensions(array_merge($e->getExtensions(), [$a]));
        }

        if ($metadata->hasValue('scope')) {
            foreach ($metadata->getArray('scope') as $scopetext) {
                $s = new Scope();
                $s->setScope($scopetext);
                // Check whether $ ^ ( ) * | \ are in a scope -> assume regex.
                if (1 === preg_match('/[\$\^\)\(\*\|\\\\]/', $scopetext)) {
                    $s->setIsRegexpScope(true);
                } else {
                    $s->setIsRegexpScope(false);
                }
                $e->setExtensions(array_merge($e->getExtensions(), [$s]));
            }
        }

        if ($metadata->hasValue('EntityAttributes')) {
            $ea = new EntityAttributes();
            foreach ($metadata->getArray('EntityAttributes') as $attributeName => $attributeValues) {
                $a = new Attribute();
                $a->setName($attributeName);
                $a->setNameFormat(Constants::NAMEFORMAT_URI);

                // Attribute names that is not URI is prefixed as this: '{nameformat}name'
                if (preg_match('/^\{(.*?)\}(.*)$/', $attributeName, $matches)) {
                    $a->setName($matches[2]);
                    $nameFormat = $matches[1];
                    $a->setNameFormat($nameFormat);
                }
                foreach ($attributeValues as $attributeValue) {
                    $a->addAttributeValue(new AttributeValue($attributeValue));
                }
                $ea->addChildren($a);
            }
            $this->entityDescriptor->setExtensions(
                array_merge($this->entityDescriptor->getExtensions(), [$ea]),
            );
        }

        if ($metadata->hasValue('saml:Extensions')) {
            $this->entityDescriptor->setExtensions(
                array_merge($this->entityDescriptor->getExtensions(), $metadata->getArray('saml:Extensions')),
            );
        }

        if ($metadata->hasValue('RegistrationInfo')) {
            $ri = new RegistrationInfo();
            foreach ($metadata->getArray('RegistrationInfo') as $riName => $riValues) {
                switch ($riName) {
                    case 'authority':
                        $ri->setRegistrationAuthority($riValues);
                        break;
                    case 'instant':
                        $ri->setRegistrationInstant(\SAML2\Utils::xsDateTimeToTimestamp($riValues));
                        break;
                    case 'policies':
                        $ri->setRegistrationPolicy($riValues);
                        break;
                }
            }
            $this->entityDescriptor->setExtensions(
                array_merge($this->entityDescriptor->getExtensions(), [$ri]),
            );
        }

        if ($metadata->hasValue('DiscoveryResponse')) {
            $discoResponse = self::createEndpoints($metadata->getArray('DiscoveryResponse'), true);

            $e->setExtensions(
                array_merge($e->getExtensions(), $discoResponse),
            );
        }

        if ($metadata->hasValue('UIInfo')) {
            $ui = new UIInfo();
            foreach ($metadata->getArray('UIInfo') as $uiName => $uiValues) {
                switch ($uiName) {
                    case 'DisplayName':
                        $ui->setDisplayName($uiValues);
                        break;
                    case 'Description':
                        $ui->setDescription($uiValues);
                        break;
                    case 'InformationURL':
                        $ui->setInformationURL($uiValues);
                        break;
                    case 'PrivacyStatementURL':
                        $ui->setPrivacyStatementURL($uiValues);
                        break;
                    case 'Keywords':
                        foreach ($uiValues as $lang => $keywords) {
                            $uiItem = new Keywords();
                            $uiItem->setLanguage($lang);
                            $uiItem->setKeywords($keywords);
                            $ui->addKeyword($uiItem);
                        }
                        break;
                    case 'Logo':
                        foreach ($uiValues as $logo) {
                            $uiItem = new Logo();
                            $uiItem->setUrl($logo['url']);
                            $uiItem->setWidth($logo['width']);
                            $uiItem->setHeight($logo['height']);
                            if (isset($logo['lang'])) {
                                $uiItem->setLanguage($logo['lang']);
                            }
                            $ui->addLogo($uiItem);
                        }
                        break;
                }
            }
            $e->setExtensions(array_merge($e->getExtensions(), [$ui]));
        }

        if ($metadata->hasValue('DiscoHints')) {
            $dh = new DiscoHints();
            foreach ($metadata->getArray('DiscoHints') as $dhName => $dhValues) {
                switch ($dhName) {
                    case 'IPHint':
                        $dh->setIPHint($dhValues);
                        break;
                    case 'DomainHint':
                        $dh->setDomainHint($dhValues);
                        break;
                    case 'GeolocationHint':
                        $dh->setGeolocationHint($dhValues);
                        break;
                }
            }
            $e->setExtensions(array_merge($e->getExtensions(), [$dh]));
        }
    }


    /**
     * Add an Organization element based on data passed as parameters
     *
     * @param array $orgName An array with the localized OrganizationName.
     * @param array $orgDisplayName An array with the localized OrganizationDisplayName.
     * @param array $orgURL An array with the localized OrganizationURL.
     */
    public function addOrganization(array $orgName, array $orgDisplayName, array $orgURL): void
    {
        $org = new Organization();

        $org->setOrganizationName($orgName);
        $org->setOrganizationDisplayName($orgDisplayName);
        $org->setOrganizationURL($orgURL);

        $this->entityDescriptor->setOrganization($org);
    }


    /**
     * Add an Organization element based on metadata array.
     *
     * @param array $metadata The metadata we should extract the organization information from.
     */
    public function addOrganizationInfo(array $metadata): void
    {
        if (
            empty($metadata['OrganizationName']) ||
            empty($metadata['OrganizationDisplayName']) ||
            empty($metadata['OrganizationURL'])
        ) {
            // empty or incomplete organization information
            return;
        }

        $arrayUtils = new Utils\Arrays();

        $orgName = $arrayUtils->arrayize($metadata['OrganizationName'], 'en');
        $orgDisplayName = $arrayUtils->arrayize($metadata['OrganizationDisplayName'], 'en');
        $orgURL = $arrayUtils->arrayize($metadata['OrganizationURL'], 'en');

        $this->addOrganization($orgName, $orgDisplayName, $orgURL);
    }


    /**
     * Add a list of endpoints to metadata.
     *
     * @param array $endpoints The endpoints.
     * @param bool  $indexed Whether the endpoints should be indexed.
     *
     * @return array An array of endpoint objects,
     *     either \SAML2\XML\md\EndpointType or \SAML2\XML\md\IndexedEndpointType.
     */
    private static function createEndpoints(array $endpoints, bool $indexed): array
    {
        $ret = [];

        foreach ($endpoints as &$ep) {
            if ($indexed) {
                if ($ep['Binding'] === Constants::NS_IDPDISC) {
                    $t = new DiscoveryResponse();
                } else {
                    $t = new IndexedEndpointType();
                }

                if (isset($ep['isDefault'])) {
                    $t->setIsDefault($ep['isDefault']);
                }

                if (!isset($ep['index'])) {
                    // Find the maximum index
                    $maxIndex = -1;
                    foreach ($endpoints as $ep) {
                        if (!isset($ep['index'])) {
                            continue;
                        }

                        if ($ep['index'] > $maxIndex) {
                            $maxIndex = $ep['index'];
                        }
                    }

                    $ep['index'] = $maxIndex + 1;
                }

                $t->setIndex($ep['index']);
            } else {
                $t = new EndpointType();
            }

            $t->setBinding($ep['Binding']);
            $t->setLocation($ep['Location']);
            if (isset($ep['ResponseLocation'])) {
                $t->setResponseLocation($ep['ResponseLocation']);
            }
            if (isset($ep['hoksso:ProtocolBinding'])) {
                $t->setAttributeNS(
                    Constants::NS_HOK,
                    'hoksso:ProtocolBinding',
                    Constants::BINDING_HTTP_REDIRECT,
                );
            }

            $ret[] = $t;
        }

        return $ret;
    }


    /**
     * Add an AttributeConsumingService element to the metadata.
     *
     * @param \SAML2\XML\md\SPSSODescriptor $spDesc The SPSSODescriptor element.
     * @param \SimpleSAML\Configuration     $metadata The metadata.
     */
    private function addAttributeConsumingService(
        SPSSODescriptor $spDesc,
        Configuration $metadata,
    ): void {
        $attributes = $metadata->getOptionalArray('attributes', []);
        $name = $metadata->getOptionalLocalizedString('name', null);

        if ($name === null || count($attributes) == 0) {
            // we cannot add an AttributeConsumingService without name and attributes
            return;
        }

        $attributesrequired = $metadata->getOptionalArray('attributes.required', []);

        /*
         * Add an AttributeConsumingService element with information as name and description and list
         * of requested attributes
         */
        $attributeconsumer = new AttributeConsumingService();
        $attributeconsumer->setIndex($metadata->getOptionalInteger('attributes.index', 0));

        if ($metadata->hasValue('attributes.isDefault')) {
            $attributeconsumer->setIsDefault($metadata->getOptionalBoolean('attributes.isDefault', false));
        }

        $attributeconsumer->setServiceName($name);
        $attributeconsumer->setServiceDescription($metadata->getOptionalLocalizedString('description', []));

        $nameFormat = $metadata->getOptionalString('attributes.NameFormat', Constants::NAMEFORMAT_URI);
        foreach ($attributes as $friendlyName => $attribute) {
            $t = new RequestedAttribute();
            $t->setName($attribute);
            if (!is_int($friendlyName)) {
                $t->setFriendlyName($friendlyName);
            }
            if ($nameFormat !== Constants::NAMEFORMAT_UNSPECIFIED) {
                $t->setNameFormat($nameFormat);
            }
            if (in_array($attribute, $attributesrequired, true)) {
                $t->setIsRequired(true);
            }
            $attributeconsumer->addRequestedAttribute($t);
        }

        $spDesc->addAttributeConsumingService($attributeconsumer);
    }


    /**
     * Add a specific type of metadata to an entity.
     *
     * @param string $set The metadata set this metadata comes from.
     * @param array  $metadata The metadata.
     */
    public function addMetadata(string $set, array $metadata): void
    {
        $this->setExpiration($metadata);

        switch ($set) {
            case 'saml20-sp-remote':
                $this->addMetadataSP20($metadata);
                break;
            case 'saml20-idp-remote':
                $this->addMetadataIdP20($metadata);
                break;
            case 'attributeauthority-remote':
                $this->addAttributeAuthority($metadata);
                break;
            default:
                Logger::warning('Unable to generate metadata for unknown type \'' . $set . '\'.');
        }
    }


    /**
     * Add SAML 2.0 SP metadata.
     *
     * @param array $metadata The metadata.
     * @param string[] $protocols The protocols supported. Defaults to \SAML2\Constants::NS_SAMLP.
     */
    public function addMetadataSP20(array $metadata, array $protocols = [Constants::NS_SAMLP]): void
    {
        Assert::notNull($metadata['entityid']);
        Assert::notNull($metadata['metadata-set']);

        $metadata = Configuration::loadFromArray($metadata, $metadata['entityid']);

        $e = new SPSSODescriptor();
        $e->setProtocolSupportEnumeration($protocols);

        if ($metadata->hasValue('saml20.sign.assertion')) {
            $e->setWantAssertionsSigned($metadata->getBoolean('saml20.sign.assertion'));
        }

        if ($metadata->hasValue('redirect.validate')) {
            $e->setAuthnRequestsSigned($metadata->getBoolean('redirect.validate'));
        } elseif ($metadata->hasValue('validate.authnrequest')) {
            $e->setAuthnRequestsSigned($metadata->getBoolean('validate.authnrequest'));
        }

        $this->addExtensions($metadata, $e);

        $this->addCertificate($e, $metadata);

        $e->setSingleLogoutService(self::createEndpoints($metadata->getEndpoints('SingleLogoutService'), false));

        $e->setNameIDFormat($metadata->getOptionalArrayizeString('NameIDFormat', []));

        $endpoints = $metadata->getEndpoints('AssertionConsumerService');
        foreach ($metadata->getOptionalArrayizeString('AssertionConsumerService.artifact', []) as $acs) {
            $endpoints[] = [
                'Binding'  => Constants::BINDING_HTTP_ARTIFACT,
                'Location' => $acs,
            ];
        }
        $e->setAssertionConsumerService(self::createEndpoints($endpoints, true));

        $this->addAttributeConsumingService($e, $metadata);

        $this->entityDescriptor->addRoleDescriptor($e);

        foreach ($metadata->getOptionalArray('contacts', []) as $contact) {
            if (array_key_exists('contactType', $contact) && array_key_exists('emailAddress', $contact)) {
                $this->addContact(Utils\Config\Metadata::getContact($contact));
            }
        }
    }


    /**
     * Add metadata of a SAML 2.0 identity provider.
     *
     * @param array $metadata The metadata.
     */
    public function addMetadataIdP20(array $metadata): void
    {
        Assert::notNull($metadata['entityid']);
        Assert::notNull($metadata['metadata-set']);

        $metadata = Configuration::loadFromArray($metadata, $metadata['entityid']);

        $e = new IDPSSODescriptor();
        $e->setProtocolSupportEnumeration(array_merge($e->getProtocolSupportEnumeration(), [Constants::NS_SAMLP]));

        if ($metadata->hasValue('sign.authnrequest')) {
            $e->setWantAuthnRequestsSigned($metadata->getBoolean('sign.authnrequest'));
        } elseif ($metadata->hasValue('redirect.sign')) {
            $e->setWantAuthnRequestsSigned($metadata->getBoolean('redirect.sign'));
        }

        if ($metadata->hasValue('errorURL')) {
            $e->setErrorURL($metadata->getString('errorURL'));
        } else {
            $e->setErrorURL(Module::getModuleURL(
                'core/error/ERRORURL_CODE?ts=ERRORURL_TS&rp=ERRORURL_RP&tid=ERRORURL_TID&ctx=ERRORURL_CTX',
            ));
        }

        $this->addExtensions($metadata, $e);

        $this->addCertificate($e, $metadata);

        if ($metadata->hasValue('ArtifactResolutionService')) {
            $e->setArtifactResolutionService(self::createEndpoints(
                $metadata->getEndpoints('ArtifactResolutionService'),
                true,
            ));
        }

        $e->setSingleLogoutService(self::createEndpoints($metadata->getEndpoints('SingleLogoutService'), false));

        $e->setNameIDFormat($metadata->getOptionalArrayizeString('NameIDFormat', []));

        $e->setSingleSignOnService(self::createEndpoints($metadata->getEndpoints('SingleSignOnService'), false));

        $this->entityDescriptor->addRoleDescriptor($e);

        foreach ($metadata->getOptionalArray('contacts', []) as $contact) {
            if (array_key_exists('contactType', $contact) && array_key_exists('emailAddress', $contact)) {
                $this->addContact(Utils\Config\Metadata::getContact($contact));
            }
        }
    }


    /**
     * Add metadata of a SAML attribute authority.
     *
     * @param array $metadata The AttributeAuthorityDescriptor, in the format returned by
     * \SimpleSAML\Metadata\SAMLParser.
     */
    public function addAttributeAuthority(array $metadata): void
    {
        Assert::notNull($metadata['entityid']);
        Assert::notNull($metadata['metadata-set']);

        $metadata = Configuration::loadFromArray($metadata, $metadata['entityid']);

        $e = new AttributeAuthorityDescriptor();
        $e->setProtocolSupportEnumeration($metadata->getOptionalArray('protocols', [Constants::NS_SAMLP]));

        $this->addExtensions($metadata, $e);
        $this->addCertificate($e, $metadata);

        $e->setAttributeService(self::createEndpoints($metadata->getEndpoints('AttributeService'), false));
        $e->setAssertionIDRequestService(self::createEndpoints(
            $metadata->getEndpoints('AssertionIDRequestService'),
            false,
        ));

        $e->setNameIDFormat($metadata->getOptionalArrayizeString('NameIDFormat', []));

        $this->entityDescriptor->addRoleDescriptor($e);
    }


    /**
     * Add contact information.
     *
     * Accepts a contact type, and a contact array that must be previously sanitized
     * by calling Utils\Config\Metadata::getContact().
     *
     * @param array  $details The details about the contact.
     */
    public function addContact(array $details): void
    {
        Assert::notNull($details['contactType']);
        Assert::oneOf($details['contactType'], ContactPerson::CONTACT_TYPES);

        $e = new \SAML2\XML\md\ContactPerson();
        $e->setContactType($details['contactType']);

        if (!empty($details['attributes'])) {
            $e->setContactPersonAttributes($details['attributes']);
        }

        if (isset($details['company'])) {
            $e->setCompany($details['company']);
        }
        if (isset($details['givenName'])) {
            $e->setGivenName($details['givenName']);
        }
        if (isset($details['surName'])) {
            $e->setSurName($details['surName']);
        }

        if (isset($details['emailAddress'])) {
            $eas = $details['emailAddress'];
            if (!is_array($eas)) {
                $eas = [$eas];
            }
            foreach ($eas as $ea) {
                $e->addEmailAddress($ea);
            }
        }

        if (isset($details['telephoneNumber'])) {
            $tlfNrs = $details['telephoneNumber'];
            if (!is_array($tlfNrs)) {
                $tlfNrs = [$tlfNrs];
            }
            foreach ($tlfNrs as $tlfNr) {
                $e->addTelephoneNumber($tlfNr);
            }
        }

        $this->entityDescriptor->addContactPerson($e);
    }


    /**
     * Add a KeyDescriptor with an X509 certificate.
     *
     * @param \SAML2\XML\md\RoleDescriptor $rd The RoleDescriptor the certificate should be added to.
     * @param string                      $use The value of the 'use' attribute.
     * @param string                      $x509data The certificate data.
     * @param string|null                 $keyName The name of the key. Should be valid for usage in an ID attribute,
     *                                             e.g. not start with a digit.
     */
    private function addX509KeyDescriptor(
        RoleDescriptor $rd,
        string $use,
        string $x509data,
        ?string $keyName = null,
    ): void {
        Assert::oneOf($use, ['encryption', 'signing']);

        $keyDescriptor = \SAML2\Utils::createKeyDescriptor($x509data, $keyName);
        $keyDescriptor->setUse($use);
        $rd->addKeyDescriptor($keyDescriptor);
    }


    /**
     * Add a certificate.
     *
     * Helper function for adding a certificate to the metadata.
     *
     * @param \SAML2\XML\md\RoleDescriptor $rd The RoleDescriptor the certificate should be added to.
     * @param \SimpleSAML\Configuration    $metadata The metadata of the entity.
     */
    private function addCertificate(RoleDescriptor $rd, Configuration $metadata): void
    {
        $keys = $metadata->getPublicKeys();
        foreach ($keys as $key) {
            if ($key['type'] !== 'X509Certificate') {
                continue;
            }
            if (!isset($key['signing']) || $key['signing'] === true) {
                $this->addX509KeyDescriptor($rd, 'signing', $key['X509Certificate'], $key['name'] ?? null);
            }
            if (!isset($key['encryption']) || $key['encryption'] === true) {
                $this->addX509KeyDescriptor($rd, 'encryption', $key['X509Certificate'], $key['name'] ?? null);
            }
        }

        if ($metadata->hasValue('https.certData')) {
            $this->addX509KeyDescriptor($rd, 'signing', $metadata->getString('https.certData'));
        }
    }
}
