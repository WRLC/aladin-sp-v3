<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Alg\KeyTransport;

use SimpleSAML\Assert\Assert;
use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\Exception\BlacklistedAlgorithmException;
use SimpleSAML\XMLSecurity\Exception\UnsupportedAlgorithmException;
use SimpleSAML\XMLSecurity\Key\KeyInterface;

use function array_key_exists;
use function sprintf;

/**
 * Factory class to create and configure key transport algorithms.
 */
class KeyTransportAlgorithmFactory
{
    /**
     * A cache of algorithm implementations indexed by algorithm ID.
     *
     * @var array<string, \SimpleSAML\XMLSecurity\Alg\KeyTransport\KeyTransportAlgorithmInterface>
     */
    protected static array $cache = [];

    /**
     * Whether the factory has been initialized or not.
     *
     * @var bool
     */
    protected static bool $initialized = false;

    /**
     * An array of blacklisted algorithms.
     *
     * Defaults to RSA 1.5.
     *
     * @var string[]
     */
    public const DEFAULT_BLACKLIST = [
        C::KEY_TRANSPORT_RSA_1_5,
    ];


    /**
     * An array of default algorithms that can be used.
     *
     * @var class-string[]
     */
    private const SUPPORTED_DEFAULTS = [
        RSA::class,
    ];


    /**
     * Build a factory that creates algorithms.
     *
     * @param string[] $blacklist A list of algorithms forbidden for their use.
     */
    public function __construct(
        protected array $blacklist = self::DEFAULT_BLACKLIST,
    ) {
        // initialize the cache for supported algorithms per known implementation
        if (!self::$initialized) {
            foreach (self::SUPPORTED_DEFAULTS as $algorithm) {
                foreach ($algorithm::getSupportedAlgorithms() as $algId) {
                    if (array_key_exists($algId, self::$cache) && !array_key_exists($algId, $this->blacklist)) {
                        /*
                         * If the key existed before initialization, that means someone registered a handler for this
                         * algorithm, so we should respect that and skip registering the default here.
                         */
                        continue;
                    }
                    self::$cache[$algId] = $algorithm;
                }
            }
            self::$initialized = true;
        }
    }


    /**
     * Get a new object implementing the given digital signature algorithm.
     *
     * @param string $algId The identifier of the algorithm desired.
     * @param \SimpleSAML\XMLSecurity\Key\KeyInterface $key The key to use with the given algorithm.
     *
     * @return \SimpleSAML\XMLSecurity\Alg\KeyTransport\KeyTransportAlgorithmInterface An object implementing the given
     * algorithm.
     *
     * @throws \SimpleSAML\XMLSecurity\Exception\UnsupportedAlgorithmException If an error occurs, e.g. the given
     * algorithm is blacklisted, unknown or the given key is not suitable for it.
     */
    public function getAlgorithm(
        string $algId,
        #[\SensitiveParameter]
        KeyInterface $key,
    ): KeyTransportAlgorithmInterface {
        Assert::notInArray(
            $algId,
            $this->blacklist,
            sprintf('Blacklisted algorithm: \'%s\'.', $algId),
            BlacklistedAlgorithmException::class,
        );
        Assert::keyExists(
            self::$cache,
            $algId,
            sprintf('Unknown or unsupported algorithm: \'%s\'.', $algId),
            UnsupportedAlgorithmException::class,
        );

        return new self::$cache[$algId]($key, $algId);
    }


    /**
     * Register an implementation of some algorithm(s) for its use.
     *
     * @param class-string $className
     */
    public static function registerAlgorithm(string $className): void
    {
        Assert::implementsInterface(
            $className,
            KeyTransportAlgorithmInterface::class,
            sprintf(
                'Cannot register algorithm "%s", must implement %s.',
                $className,
                KeyTransportAlgorithmInterface::class,
            ),
        );

        foreach ($className::getSupportedAlgorithms() as $algId) {
            self::$cache[$algId] = $className;
        }
    }
}