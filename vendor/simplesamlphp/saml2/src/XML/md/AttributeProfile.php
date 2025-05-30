<?php

declare(strict_types=1);

namespace SimpleSAML\SAML2\XML\md;

use SimpleSAML\SAML2\XML\StringElementTrait;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;

/**
 * Class implementing AttributeProfile.
 *
 * @package simplesamlphp/saml2
 */
final class AttributeProfile extends AbstractMdElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;
    use StringElementTrait;


    /**
     * @param string $content
     */
    public function __construct(string $content)
    {
        $this->setContent($content);
    }
}
