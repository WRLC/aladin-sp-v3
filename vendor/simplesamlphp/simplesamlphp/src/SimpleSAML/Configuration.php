<?php

declare(strict_types=1);

namespace SimpleSAML;

use Exception;
use ParseError;
use SAML2\Binding;
use SAML2\Constants;
use SAML2\Exception\Protocol\UnsupportedBindingException;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Error;
use SimpleSAML\Utils;
use Symfony\Component\Filesystem\Filesystem;

use function array_key_exists;
use function array_keys;
use function dirname;
use function file_exists;
use function is_array;
use function is_int;
use function is_null;
use function is_string;
use function ob_end_clean;
use function ob_get_length;
use function ob_start;
use function preg_match;
use function preg_replace;
use function rtrim;
use function substr;
use function var_export;

/**
 * Configuration of SimpleSAMLphp
 *
 * @package SimpleSAMLphp
 */
class Configuration implements Utils\ClearableState
{
    /**
     * The release version of this package
     */
    public const VERSION = '2.4.2';

    /**
     * A default value which means that the given option is required.
     *
     * @var string
     */
    public const REQUIRED_OPTION = '___REQUIRED_OPTION___';

    /**
     * The default security-headers to be sent on responses.
     */
    public const DEFAULT_SECURITY_HEADERS = [
        'Content-Security-Policy' =>
            "default-src 'none'; " .
            "frame-ancestors 'self'; " .
            "object-src 'none'; " .
            "script-src 'self'; " .
            "style-src 'self'; " .
            "font-src 'self'; " .
            "connect-src 'self'; " .
            "media-src data:; " .
            "img-src 'self' data:; " .
            "base-uri 'none'",
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'origin-when-cross-origin',
    ];

    /**
     * Associative array with mappings from instance-names to configuration objects.
     *
     * @var array<string, \SimpleSAML\Configuration>
     */
    private static array $instance = [];

    /**
     * Configuration directories.
     *
     * This associative array contains the mappings from configuration sets to
     * configuration directories.
     *
     * @var array<string, string>
     */
    private static array $configDirs = [];

    /**
     * Cache of loaded configuration files.
     *
     * The index in the array is the full path to the file.
     *
     * @var array
     */
    private static array $loadedConfigs = [];

    /**
     * The configuration array.
     *
     * @var array
     */
    private array $configuration;

    /**
     * The location which will be given when an error occurs.
     *
     * @var string
     */
    private string $location;

    /**
     * The file this configuration was loaded from.
     *
     * @var string|null
     */
    private ?string $filename = null;

    /**
     * Initializes a configuration from the given array.
     *
     * @param array $config The configuration array.
     * @param string $location The location which will be given when an error occurs.
     */
    public function __construct(array $config, string $location)
    {
        $this->configuration = $config;
        $this->location = $location;
    }


    /**
     * Load the given configuration file.
     *
     * @param string $filename The full path of the configuration file.
     * @param bool $required Whether the file is required.
     *
     * @return \SimpleSAML\Configuration The configuration file. An exception will be thrown if the
     *                                   configuration file is missing.
     *
     * @throws \Exception If the configuration file is invalid or missing.
     */
    private static function loadFromFile(string $filename, bool $required): Configuration
    {
        if (array_key_exists($filename, self::$loadedConfigs)) {
            return self::$loadedConfigs[$filename];
        }

        $fileSystem = new Filesystem();
        if ($fileSystem->exists($filename)) {
            /** @psalm-var mixed $config */
            $config = 'UNINITIALIZED';

            // the file initializes a variable named '$config'
            try {
                ob_start();
                $returnedConfig = require($filename);
                $spurious_output = ob_get_length() > 0;
            } catch (ParseError $e) {
                self::$loadedConfigs[$filename] = self::loadFromArray([], '[ARRAY]', 'simplesaml');
                throw new Error\ConfigurationError($e->getMessage(), $filename, []);
            } finally {
                ob_end_clean();
            }

            // Check if the config file actually returned an array instead of defining $config variable.
            if (is_array($returnedConfig)) {
                $config = $returnedConfig;
            }

            // check that $config exists
            if (!isset($config)) {
                throw new Error\ConfigurationError(
                    '$config is not defined in the configuration file.',
                    $filename,
                );
            }

            // check that $config is initialized to an array
            if (!is_array($config)) {
                throw new Error\ConfigurationError(
                    '$config is not an array.',
                    $filename,
                );
            }

            // check that $config is not empty
            if (empty($config)) {
                throw new Error\ConfigurationError(
                    '$config is empty.',
                    $filename,
                );
            }
        } elseif ($required) {
            // file does not exist, but is required
            throw new Error\ConfigurationError('Missing configuration file', $filename);
        } else {
            // file does not exist, but is optional, so return an empty configuration object without saving it
            $cfg = new Configuration([], $filename);
            $cfg->filename = $filename;
            return $cfg;
        }

        $cfg = new Configuration($config, $filename);
        $cfg->filename = $filename;

        self::$loadedConfigs[$filename] = $cfg;

        if ($spurious_output) {
            Logger::warning(
                "The configuration file '$filename' generates output. Please review your configuration.",
            );
        }

        return $cfg;
    }


    /**
     * Set the directory for configuration files for the given configuration set.
     *
     * @param string $path The directory which contains the configuration files.
     * @param string $configSet The configuration set. Defaults to 'simplesaml'.
     */
    public static function setConfigDir(string $path, string $configSet = 'simplesaml'): void
    {
        self::$configDirs[$configSet] = $path;
    }


    /**
     * Store a pre-initialized configuration.
     *
     * Allows consumers to create configuration objects without having them
     * loaded from a file.
     *
     * @param \SimpleSAML\Configuration $config  The configuration object to store
     * @param string $filename  The name of the configuration file.
     * @param string $configSet  The configuration set. Optional, defaults to 'simplesaml'.
     * @throws \Exception
     */
    public static function setPreLoadedConfig(
        Configuration $config,
        string $filename = 'config.php',
        string $configSet = 'simplesaml',
    ): void {
        if (!array_key_exists($configSet, self::$configDirs)) {
            if ($configSet !== 'simplesaml') {
                throw new Exception('Configuration set \'' . $configSet . '\' not initialized.');
            } else {
                self::$configDirs['simplesaml'] = dirname(__FILE__, 3) . '/config';
            }
        }

        $dir = self::$configDirs[$configSet];
        $filePath = $dir . '/' . $filename;

        self::$loadedConfigs[$filePath] = $config;
    }


    /**
     * Load a configuration file from a configuration set.
     *
     * @param string $filename The name of the configuration file.
     * @param string $configSet The configuration set. Optional, defaults to 'simplesaml'.
     *
     * @return \SimpleSAML\Configuration The Configuration object.
     * @throws \Exception If the configuration set is not initialized.
     */
    public static function getConfig(
        string $filename = 'config.php',
        string $configSet = 'simplesaml',
    ): Configuration {
        if (!array_key_exists($configSet, self::$configDirs)) {
            if ($configSet !== 'simplesaml') {
                throw new Exception('Configuration set \'' . $configSet . '\' not initialized.');
            } else {
                $configUtils = new Utils\Config();
                self::$configDirs['simplesaml'] = $configUtils->getConfigDir();
            }
        }

        $dir = self::$configDirs[$configSet];
        $filePath = $dir . '/' . $filename;
        return self::loadFromFile($filePath, true);
    }


    /**
     * Load a configuration file from a configuration set.
     *
     * This function will return a configuration object even if the file does not exist.
     *
     * @param string $filename The name of the configuration file.
     * @param string $configSet The configuration set. Optional, defaults to 'simplesaml'.
     *
     * @return \SimpleSAML\Configuration A configuration object.
     * @throws \Exception If the configuration set is not initialized.
     */
    public static function getOptionalConfig(
        string $filename = 'config.php',
        string $configSet = 'simplesaml',
    ): Configuration {
        if (!array_key_exists($configSet, self::$configDirs)) {
            if ($configSet !== 'simplesaml') {
                throw new Exception('Configuration set \'' . $configSet . '\' not initialized.');
            }

            $configUtils = new Utils\Config();
            self::$configDirs['simplesaml'] = $configUtils->getConfigDir();
        }

        $dir = self::$configDirs[$configSet];
        $filePath = $dir . '/' . $filename;
        return self::loadFromFile($filePath, false);
    }


    /**
     * Loads a configuration from the given array.
     *
     * @param array  $config The configuration array.
     * @param string $location The location which will be given when an error occurs. Optional.
     * @param string|null $instance The name of this instance. If specified, the configuration will be loaded and an
     * instance with that name will be kept for it to be retrieved later with getInstance($instance). If null, the
     * configuration will not be kept for later use. Defaults to null.
     *
     * @return \SimpleSAML\Configuration The configuration object.
     */
    public static function loadFromArray(
        array $config,
        string $location = '[ARRAY]',
        ?string $instance = null,
    ): Configuration {
        $c = new Configuration($config, $location);
        if ($instance !== null) {
            self::$instance[$instance] = $c;
        }
        return $c;
    }


    /**
     * Get a configuration file by its instance name.
     *
     * This function retrieves a configuration file by its instance name. The instance
     * name is initialized by the init function, or by copyFromBase function.
     *
     * If no configuration file with the given instance name is found, an exception will
     * be thrown.
     *
     * @param string $instancename The instance name of the configuration file. Deprecated.
     *
     * @return \SimpleSAML\Configuration The configuration object.
     *
     * @throws \Exception If the configuration with $instancename name is not initialized.
     */
    public static function getInstance(string $instancename = 'simplesaml'): Configuration
    {
        // check if the instance exists already
        if (array_key_exists($instancename, self::$instance)) {
            return self::$instance[$instancename];
        }

        if ($instancename === 'simplesaml') {
            try {
                return self::getConfig();
            } catch (Error\ConfigurationError $e) {
                throw Error\CriticalConfigurationError::fromException($e);
            }
        }

        throw new Error\CriticalConfigurationError(
            'Configuration with name ' . $instancename . ' is not initialized.',
        );
    }


    /**
     * Retrieve the current version of SimpleSAMLphp.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }


    /**
     * Retrieve a configuration option set in config.php.
     *
     * @param string $name  Name of the configuration option.
     * @return mixed        The configuration option with name $name.
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the required option cannot be retrieved.
     */
    public function getValue(string $name): mixed
    {
        Assert::true(
            $this->hasValue($name),
            sprintf('%s: Could not retrieve the required option %s.', $this->location, var_export($name, true)),
        );

        return $this->configuration[$name];
    }


    /**
     * Retrieve an optional configuration option set in config.php.
     *
     * @param string $name     Name of the configuration option.
     * @param mixed  $default  Default value of the configuration option.
                               This parameter will default to null if not specified.
     *
     * @return mixed           The configuration option with name $name, or $default if the option was not found.
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the required option cannot be retrieved.
     */
    public function getOptionalValue(string $name, mixed $default): mixed
    {
        // return the default value if the option is unset
        if (!$this->hasValue($name)) {
            return $default;
        }

        return $this->configuration[$name];
    }


    /**
     * Check whether a key in the configuration exists or not.
     *
     * @param string $name The key in the configuration to look for.
     *
     * @return boolean If the value is set in this configuration.
     */
    public function hasValue(string $name): bool
    {
        return array_key_exists($name, $this->configuration) && !is_null($this->configuration[$name]);
    }


    /**
     * Check whether any key of the set given exists in the configuration.
     *
     * @param array $names An array of options to look for.
     *
     * @return boolean If any of the keys in $names exist in the configuration
     */
    public function hasValueOneOf(array $names): bool
    {
        foreach ($names as $name) {
            if ($this->hasValue($name)) {
                return true;
            }
        }
        return false;
    }


    /**
     * Retrieve the absolute path pointing to the SimpleSAMLphp installation.
     *
     * The path is guaranteed to start and end with a slash ('/'). E.g.: /simplesaml/
     *
     * @return string The absolute path where SimpleSAMLphp can be reached in the web server.
     *
     * @throws \SimpleSAML\Error\CriticalConfigurationError If the format of 'baseurlpath' is incorrect.
     */
    public function getBasePath(): string
    {
        $baseURL = $this->getOptionalString('baseurlpath', 'simplesaml/');

        if (preg_match('#^https?://[^/]*(?:/(.+/?)?)?$#', $baseURL, $matches)) {
            // We have a full url, we need to strip the path.
            if (!array_key_exists(1, $matches)) {
                // Absolute URL without path.
                return '/';
            }
            return '/' . rtrim($matches[1], '/') . '/';
        }

        if ($baseURL === '' || $baseURL === '/') {
            // Root directory of site.
            return '/';
        }

        if (preg_match('#^/?((?:[^/\s]+/?)+)#', $baseURL, $matches)) {
            // Local path only.
            return '/' . rtrim($matches[1], '/') . '/';
        }

        /**
         * Invalid 'baseurlpath'. We cannot recover from this.
         * Throw a critical exception and try to be graceful
         * with the configuration. Use a guessed base path instead of the one provided.
         */
        $c = $this->toArray();
        $httpUtils = new Utils\HTTP();
        $c['baseurlpath'] = $httpUtils->guessBasePath();
        throw new Error\CriticalConfigurationError(
            'Incorrect format for option \'baseurlpath\'. Value is: "' .
            $this->getOptionalString('baseurlpath', 'simplesaml/') . '". Valid format is in the form' .
            ' [(http|https)://(hostname|fqdn)[:port]]/[path/to/simplesaml/].',
            $this->filename,
            $c,
        );
    }


    /**
     * This function resolves a path which may be relative to the SimpleSAMLphp base directory.
     *
     * The path will never end with a '/'.
     *
     * @param string|null $path The path we should resolve. This option may be null.
     *
     * @return string|null $path if $path is an absolute path, or $path prepended with the base directory of this
     * SimpleSAMLphp installation. We will return NULL if $path is null.
     */
    public function resolvePath(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $sysUtils = new Utils\System();
        return $sysUtils->resolvePath($path, $this->getBaseDir());
    }


    /**
     * Retrieve a path configuration option set in config.php.
     *
     * The function will always return an absolute path unless the option is not set. It will then return the default
     * value.
     *
     * It checks if the value starts with a slash, and prefixes it with the value from getBaseDir if it doesn't.
     *
     * @param string $name Name of the configuration option.
     * @param string|null $default Default value of the configuration option. This parameter will default to null if
     * not specified.
     *
     * @return string|null The path configuration option with name $name, or $default if the option was not found.
     */
    public function getPathValue(string $name, ?string $default = null): ?string
    {
        // return the default value if the option is unset
        if (!array_key_exists($name, $this->configuration)) {
            $path = $default;
        } else {
            $path = $this->configuration[$name];
        }

        $path = $this->resolvePath($path);
        if ($path === null) {
            return null;
        }

        return $path . '/';
    }


    /**
     * Retrieve the location of the vendor directory
     *
     * This function checks whether SimpleSAMLphp is installed as a stand-alone application or as a library
     * and determines the location of the vendor directory.
     *
     * @return string The absolute path to the vendor directory. This path will always end with a slash.
     */
    public function getVendorDir(): string
    {
        if (file_exists(dirname(__FILE__, 3) . '/vendor')) {
            return dirname(__FILE__, 3) . '/vendor/';
        } else {
            // SSP is loaded as a library.
            return dirname(__FILE__, 6) . '/vendor/';
        }
    }


    /**
     * Retrieve the base directory for this SimpleSAMLphp installation.
     *
     * This function first checks the 'basedir' configuration option. If this option is undefined or null, then we
     * fall back to looking at the current filename.
     *
     * @return string The absolute path to the base directory for this SimpleSAMLphp installation. This path will
     * always end with a slash.
     */
    public function getBaseDir(): string
    {
        // check if a directory is configured in the configuration file
        $dir = $this->getOptionalString('basedir', null);
        if ($dir !== null) {
            // add trailing slash if it is missing
            if (substr($dir, -1) !== DIRECTORY_SEPARATOR) {
                $dir .= DIRECTORY_SEPARATOR;
            }

            return $dir;
        }

        // the directory wasn't set in the configuration file, path is <base directory>/src/SimpleSAML/Configuration.php
        $dir = __FILE__;
        Assert::same(basename($dir), 'Configuration.php');

        $dir = dirname($dir);
        Assert::same(basename($dir), 'SimpleSAML');

        $dir = dirname($dir);
        Assert::same(basename($dir), 'src');

        $dir = dirname($dir);

        // Add trailing directory separator
        $dir .= DIRECTORY_SEPARATOR;

        return $dir;
    }


    /**
     * This function retrieves a boolean configuration option.
     *
     * An exception will be thrown if this option isn't a boolean, or if this option isn't found.
     *
     * @param string   $name The name of the option.
     * @return boolean       The option with the given name.
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the option is not boolean.
     */
    public function getBoolean(string $name): bool
    {
        $ret = $this->getValue($name);

        Assert::boolean(
            $ret,
            sprintf('%s: The option %s is not a valid boolean value.', $this->location, var_export($name, true)),
        );

        return $ret;
    }


    /**
     * This function retrieves a boolean configuration option.
     *
     * An exception will be thrown if this option isn't a boolean.
     *
     * @param string    $name     The name of the option.
     * @param bool|null $default  A default value which will be returned if the option isn't found.
     *                            The default value can be null or a boolean.
     *
     * @return bool|null          The option with the given name, or $default.
     * @psalm-return              ($default is bool ? bool : bool|null)
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the option is not boolean.
     */
    public function getOptionalBoolean(string $name, ?bool $default): ?bool
    {
        $ret = $this->getOptionalValue($name, $default);

        Assert::nullOrBoolean(
            $ret,
            sprintf(
                '%s: The option %s is not a valid boolean value or null.',
                $this->location,
                var_export($name, true),
            ),
        );

        return $ret;
    }


    /**
     * This function retrieves a string configuration option.
     *
     * An exception will be thrown if this option isn't a string, or if this option isn't found.
     *
     * @param string $name  The name of the option.
     * @return string       The option with the given name.
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the option is not a string.
     */
    public function getString(string $name): string
    {
        $ret = $this->getValue($name);

        Assert::string(
            $ret,
            sprintf('%s: The option %s is not a valid string value.', $this->location, var_export($name, true)),
        );

        return $ret;
    }


    /**
     * This function retrieves an optional string configuration option.
     *
     * An exception will be thrown if this option isn't a string.
     *
     * @param string       $name     The name of the option.
     * @param string|null  $default  A default value which will be returned if the option isn't found.
     *                               The default value can be null or a string.
     *
     * @return string|null The option with the given name, or $default if the option isn't found.
     * @psalm-return       ($default is string ? string : string|null)
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the option is not a string.
     */
    public function getOptionalString(string $name, ?string $default): ?string
    {
        $ret = $this->getOptionalValue($name, $default);

        Assert::nullOrString(
            $ret,
            sprintf(
                '%s: The option %s is not a valid string value or null.',
                $this->location,
                var_export($name, true),
            ),
        );

        return $ret;
    }


    /**
     * This function retrieves an integer configuration option.
     *
     * An exception will be thrown if this option isn't an integer, or if this option isn't found.
     *
     * @param string $name  The name of the option.
     * @return int          The option with the given name.
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the option is not an integer.
     */
    public function getInteger(string $name): int
    {
        $ret = $this->getValue($name);

        Assert::integer(
            $ret,
            sprintf('%s: The option %s is not a valid integer value.', $this->location, var_export($name, true)),
        );

        return $ret;
    }


    /**
     * This function retrieves an optional integer configuration option.
     *
     * An exception will be thrown if this option isn't an integer.
     *
     * @param string $name     The name of the option.
     * @param int|null  $default  A default value which will be returned if the option isn't found.
     *                         The default value can be null or an integer.
     *
     * @return int|null The option with the given name, or $default if the option isn't found.
     * @psalm-return           ($default is int ? int : int|null)
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the option is not an integer.
     */
    public function getOptionalInteger(string $name, ?int $default): ?int
    {
        $ret = $this->getOptionalValue($name, $default);

        Assert::nullOrInteger(
            $ret,
            sprintf(
                '%s: The option %s is not a valid integer value or null.',
                $this->location,
                var_export($name, true),
            ),
        );

        return $ret;
    }


    /**
     * This function retrieves an integer configuration option where the value must be in the specified range.
     *
     * An exception will be thrown if:
     * - the option isn't an integer
     * - the option isn't found, and no default value is given
     * - the value is outside of the allowed range
     *
     * @param string $name The name of the option.
     * @param int    $minimum The smallest value which is allowed.
     * @param int    $maximum The largest value which is allowed.
     *
     * @return int The option with the given name.
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the option is not in the range specified.
     */
    public function getIntegerRange(string $name, int $minimum, int $maximum): int
    {
        $ret = $this->getInteger($name);

        Assert::range(
            $ret,
            $minimum,
            $maximum,
            sprintf(
                '%s: Value of option %s is out of range. Value is %%s, allowed range is [%%2$s - %%3$s]',
                $this->location,
                var_export($name, true),
            ),
        );

        return $ret;
    }


    /**
     * This function retrieves an optional integer configuration option where the value must be in the specified range.
     *
     * An exception will be thrown if:
     * - the option isn't an integer
     * - the value is outside of the allowed range
     *
     * @param string    $name    The name of the option.
     * @param int       $minimum The smallest value which is allowed.
     * @param int       $maximum The largest value which is allowed.
     * @param int|null  $default A default value which will be returned if the option isn't found.
     *                             The default value can be null or an integer.
     *
     * @return int|null The option with the given name, or $default if the option isn't found and $default is
     *     specified.
     * @psalm-return    ($default is int ? int : int|null)
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the option is not in the range specified.
     */
    public function getOptionalIntegerRange(string $name, int $minimum, int $maximum, ?int $default): ?int
    {
        $ret = $this->getOptionalInteger($name, $default);

        Assert::nullOrRange(
            $ret,
            $minimum,
            $maximum,
            sprintf(
                '%s: Value of option %s is out of range. Value is %%s, allowed range is [%%2$s - %%3$s] or null.',
                $this->location,
                var_export($name, true),
            ),
        );

        return $ret;
    }


    /**
     * Retrieve a configuration option with one of the given values.
     *
     * This will check that the configuration option matches one of the given values. The match will use
     * strict comparison. An exception will be thrown if it does not match.
     *
     * The option is mandatory and an exception will be thrown if it isn't provided.
     *
     * @param string $name           The name of the option.
     * @param array  $allowedValues  The values the option is allowed to take, as an array.
     *
     * @return mixed The option with the given name.
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the option does not have any of the allowed values.
     */
    public function getValueValidate(string $name, array $allowedValues): mixed
    {
        $ret = $this->getValue($name);

        Assert::oneOf(
            $ret,
            $allowedValues,
            sprintf(
                '%s: Invalid value given for option %s. It should have one of: %%2$s; but got: %%s.',
                $this->location,
                var_export($name, true),
            ),
        );

        return $ret;
    }


    /**
     * Retrieve an optional configuration option with one of the given values.
     *
     * This will check that the configuration option matches one of the given values. The match will use
     * strict comparison. An exception will be thrown if it does not match.
     *
     * The option is optional. The default value is automatically included in the list of allowed values.
     *
     * @param string $name           The name of the option.
     * @param array  $allowedValues  The values the option is allowed to take, as an array.
     * @param mixed  $default        The default value which will be returned if the option isn't found.
     *                               The default value can be any value, including null.
     *
     * @return mixed The option with the given name, or $default if the option isn't found and $default is given.
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the option does not have any of the allowed values.
     */
    public function getOptionalValueValidate(string $name, array $allowedValues, mixed $default): mixed
    {
        $ret = $this->getOptionalValue($name, $default);

        Assert::nullOrOneOf(
            $ret,
            $allowedValues,
            sprintf(
                '%s: Invalid value given for option %s. It should have one of: %%2$s or null; but got: %%s.',
                $this->location,
                var_export($name, true),
            ),
        );

        return $ret;
    }


    /**
     * This function retrieves an array configuration option.
     *
     * An exception will be thrown if this option isn't an array, or if this option isn't found.
     *
     * @param string $name The name of the option.
     * @return array The option with the given name.
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the option is not an array.
     */
    public function getArray(string $name): array
    {
        $ret = $this->getValue($name);

        Assert::isArray(
            $ret,
            sprintf('%s: The option %s is not an array.', $this->location, var_export($name, true)),
        );

        return $ret;
    }


    /**
     * This function retrieves an optional array configuration option.
     *
     * An exception will be thrown if this option isn't an array, or if this option isn't found.
     *
     * @param string      $name     The name of the option.
     * @param array|null  $default  A default value which will be returned if the option isn't found.
     *                                The default value can be null or an array.
     *
     * @return array|null The option with the given name, or $default if the option isn't found and $default is
     * specified.
     * @psalm-return      ($default is array ? array : array|null)
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the option is not an array.
     */
    public function getOptionalArray(string $name, ?array $default): ?array
    {
        $ret = $this->getOptionalValue($name, $default);

        Assert::nullOrIsArray(
            $ret,
            sprintf('%s: The option %s is not an array or null.', $this->location, var_export($name, true)),
        );

        return $ret;
    }


    /**
     * This function retrieves an array configuration option.
     *
     * If the configuration option isn't an array, it will be converted to an array.
     *
     * @param string $name The name of the option.
     *
     * @return array The option with the given name.
     */
    public function getArrayize(string $name): array
    {
        $ret = $this->getValue($name);

        if (!is_array($ret)) {
            $ret = [$ret];
        }

        return $ret;
    }


    /**
     * This function retrieves an optional array configuration option.
     *
     * If the configuration option isn't an array, it will be converted to an array.
     *
     * @param string      $name The name of the option.
     * @param array|null  $default A default value which will be returned if the option isn't found.
     *                       The default value can be null or an array.
     *
     * @return array|null The option with the given name.
     * @psalm-return      ($default is null ? array|null : array)
     */
    public function getOptionalArrayize(string $name, $default): ?array
    {
        $ret = $this->getOptionalValue($name, $default);

        if (!is_array($ret)) {
            $ret = [$ret];
        }

        return $ret;
    }


    /**
     * This function retrieves a configuration option with a string or an array of strings.
     *
     * If the configuration option is a string, it will be converted to an array with a single string
     *
     * @param string $name The name of the option.
     * @return string[] The option with the given name.
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the option is not a string or an array of strings.
     */
    public function getArrayizeString(string $name): array
    {
        $ret = $this->getArrayize($name);

        Assert::allString(
            $ret,
            sprintf(
                '%s: The option %s must be a string or an array of strings.',
                $this->location,
                var_export($name, true),
            ),
        );

        return $ret;
    }


    /**
     * This function retrieves an optional configuration option with a string or an array of strings.
     *
     * If the configuration option is a string, it will be converted to an array with a single string
     *
     * @param string         $name The name of the option.
     * @param string[]|null  $default A default value which will be returned if the option isn't found.
     *                         The default value can be null or an array of strings.
     *
     * @return string[]|null The option with the given name, or $default if the option isn't found
     *                         and $default is specified.
     * @psalm-return         ($default is null ? array|null : array)
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the option is not a string or an array of strings.
     */
    public function getOptionalArrayizeString(string $name, ?array $default): ?array
    {
        $ret = $this->getOptionalArrayize($name, $default);

        Assert::nullOrAllString(
            $ret,
            sprintf(
                '%s: The option %s must be null, a string or an array of strings.',
                $this->location,
                var_export($name, true),
            ),
        );

        return $ret;
    }


    /**
     * Retrieve an array as a \SimpleSAML\Configuration object.
     *
     * This function will load the value of an option into a \SimpleSAML\Configuration object.
     *   The option must contain an array.
     *
     * An exception will be thrown if this option isn't an array, or if this option isn't found.
     *
     * @param string $name The name of the option.
     * @return \SimpleSAML\Configuration The option with the given name,
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the option is not an array.
     */
    public function getConfigItem(string $name): Configuration
    {
        $ret = $this->getArray($name);

        return self::loadFromArray($ret, $this->location . '[' . var_export($name, true) . ']');
    }



    /**
     * Retrieve an optional array as a \SimpleSAML\Configuration object.
     *
     * This function will load the optional value of an option into a \SimpleSAML\Configuration object.
     *   The option must contain an array.
     *
     * An exception will be thrown if this option isn't an array, or if this option isn't found.
     *
     * @param string     $name The name of the option.
     * @param array|null $default A default value which will be used if the option isn't found. An empty Configuration
     *                     object will be returned if this parameter isn't given and the option doesn't exist.
     *                     This function will only return null if $default is set to null and the option doesn't exist.
     *
     * @return \SimpleSAML\Configuration|null The option with the given name,
     *   or $default, converted into a Configuration object.
     * @psalm-return     ($default is array ? \SimpleSAML\Configuration : \SimpleSAML\Configuration|null)
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException If the option is not an array.
     */
    public function getOptionalConfigItem(string $name, ?array $default): ?Configuration
    {
        $ret = $this->getOptionalArray($name, $default);

        if ($ret !== null) {
            return self::loadFromArray($ret, $this->location . '[' . var_export($name, true) . ']');
        }
        return null;
    }


    /**
     * Retrieve list of options.
     *
     * This function returns the name of all options which are defined in this
     * configuration file, as an array of strings.
     *
     * @return string[] Name of all options defined in this configuration file.
     */
    public function getOptions(): array
    {
        return array_keys($this->configuration);
    }


    /**
     * Convert this configuration object back to an array.
     *
     * @return array An associative array with all configuration options and values.
     */
    public function toArray(): array
    {
        return $this->configuration;
    }


    /**
     * Retrieve the default binding for the given endpoint type.
     *
     * This function combines the current metadata type (SAML 2 / SAML 1.1)
     * with the endpoint type to determine which binding is the default.
     *
     * @param string $endpointType The endpoint type.
     *
     * @return string The default binding.
     *
     * @throws \Exception If the default binding is missing for this endpoint type.
     */
    private function getDefaultBinding(string $endpointType): string
    {
        $set = $this->getString('metadata-set');
        switch ($set . ':' . $endpointType) {
            case 'saml20-idp-remote:SingleSignOnService':
            case 'saml20-idp-remote:SingleLogoutService':
            case 'saml20-sp-remote:SingleLogoutService':
                return Constants::BINDING_HTTP_REDIRECT;
            case 'saml20-sp-remote:AssertionConsumerService':
                return Constants::BINDING_HTTP_POST;
            case 'saml20-idp-remote:ArtifactResolutionService':
            case 'attributeauthority-remote:AttributeService':
                return Constants::BINDING_SOAP;
            default:
                throw new Exception('Missing default binding for ' . $endpointType . ' in ' . $set);
        }
    }

    /**
     * Helper function for dealing with metadata endpoints.
     *
     * @param string $endpointType The endpoint type.
     *
     * @return array Array of endpoints of the given type.
     *
     * @throws \Exception If any element of the configuration options for this endpoint type is incorrect.
     */
    public function getEndpoints(string $endpointType): array
    {
        $loc = $this->location . '[' . var_export($endpointType, true) . ']:';

        if (!array_key_exists($endpointType, $this->configuration)) {
            // no endpoints of the given type
            return [];
        }

        $eps = $this->configuration[$endpointType];
        if (!is_array($eps)) {
            $filename = explode('/', $loc)[0];
            throw new Error\CriticalConfigurationError(
                "Endpoint of type $endpointType is not an array in $loc.",
                $filename,
            );
        }

        $eps_count = count($eps);

        foreach ($eps as $i => &$ep) {
            $iloc = $loc . '[' . var_export($i, true) . ']';

            if (!is_array($ep)) {
                throw new Exception($iloc . ': Expected a string or an array.');
            }

            if (!array_key_exists('Location', $ep)) {
                throw new Exception($iloc . ': Missing Location.');
            }
            if (!is_string($ep['Location'])) {
                throw new Exception($iloc . ': Location must be a string.');
            }

            if (!array_key_exists('Binding', $ep)) {
                $ep['Binding'] = $this->getDefaultBinding($endpointType);
            }
            if (!is_string($ep['Binding'])) {
                throw new Exception($iloc . ': Binding must be a string.');
            }

            if ($eps_count <= 1) {
                $isDefault = false;
                if (array_key_exists('isDefault', $ep) && $ep['isDefault']) {
                    $isDefault = true;
                } else {
                    try {
                        Binding::getBinding($ep['Binding']);
                    } catch (UnsupportedBindingException $e) {
                        $ep['Binding'] = $this->getDefaultBinding($endpointType);
                    }
                }
            }
            if (array_key_exists('ResponseLocation', $ep)) {
                if (!is_string($ep['ResponseLocation'])) {
                    throw new Exception($iloc . ': ResponseLocation must be a string.');
                }
            }

            if (array_key_exists('index', $ep)) {
                if (!is_int($ep['index'])) {
                    throw new Exception($iloc . ': index must be an integer.');
                }
            }
        }

        return $eps;
    }


    /**
     * Find an endpoint of the given type, using a list of supported bindings as a way to prioritize.
     *
     * @param string $endpointType The endpoint type.
     * @param string[] $bindings Sorted array of acceptable bindings.
     * @param mixed  $default The default value to return if no matching endpoint is found. If no default is provided,
     *     an exception will be thrown.
     *
     * @return mixed|null The default endpoint.
     *
     * @throws \Exception If no supported endpoint is found.
     */
    public function getEndpointPrioritizedByBinding(
        string $endpointType,
        array $bindings,
        mixed $default = self::REQUIRED_OPTION,
    ): mixed {
        $endpoints = $this->getEndpoints($endpointType);

        foreach ($bindings as $binding) {
            foreach ($endpoints as $ep) {
                if ($ep['Binding'] === $binding) {
                    return $ep;
                }
            }
        }

        if ($default === self::REQUIRED_OPTION) {
            $loc = $this->location . '[' . var_export($endpointType, true) . ']:';
            throw new Exception($loc . 'Could not find a supported ' . $endpointType . ' endpoint.');
        }

        return $default;
    }


    /**
     * Find the default endpoint of the given type.
     *
     * @param string $endpointType The endpoint type.
     * @param string[]|null $bindings Array with acceptable bindings. Can be null if any binding is allowed.
     * @param mixed  $default The default value to return if no matching endpoint is found. If no default is provided,
     *     an exception will be thrown.
     *
     * @return mixed The default endpoint, or the $default parameter if no acceptable endpoints are used.
     *
     * @throws \Exception If no supported endpoint is found and no $default parameter is specified.
     */
    public function getDefaultEndpoint(
        string $endpointType,
        ?array $bindings = null,
        mixed $default = self::REQUIRED_OPTION,
    ): mixed {
        $endpoints = $this->getEndpoints($endpointType);

        $defaultEndpoint = Utils\Config\Metadata::getDefaultEndpoint($endpoints, $bindings);
        if ($defaultEndpoint !== null) {
            return $defaultEndpoint;
        }

        if ($default === self::REQUIRED_OPTION) {
            $loc = $this->location . '[' . var_export($endpointType, true) . ']:';
            throw new Exception($loc . 'Could not find a supported ' . $endpointType . ' endpoint.');
        }

        return $default;
    }


    /**
     * Retrieve a string which may be localized into many languages.
     *
     * The default language returned is always 'en'.
     *
     * @param string $name The name of the option.
     * @param array  $default The default value.
     *
     * @return array Associative array with language => string pairs.
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException
     *   If the translation is not an array or a string, or its index or value are not strings.
     */
    public function getLocalizedString(string $name): array
    {
        $ret = $this->getValue($name);

        if (is_string($ret)) {
            $ret = ['en' => $ret];
        }

        Assert::isArray($ret, sprintf('%s: Must be an array or a string.', $this->location));

        foreach ($ret as $k => $v) {
            Assert::string($k, sprintf('%s: Invalid language code: %s', $this->location, var_export($k, true)));
            Assert::string($v, sprintf('%s[%s]: Must be a string.', $this->location, var_export($v, true)));
        }

        return $ret;
    }


    /**
     * Retrieve an optional string which may be localized into many languages.
     *
     * The default language returned is always 'en'.
     *
     * @param string $name The name of the option.
     * @param array|null  $default The default value.
     *
     * @return array|null Associative array with language => string pairs, or the provided default value.
     * @psalm-return ($default is array ? array : array|null)
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException
     *   If the translation is not an array or a string, or its index or value are not strings.
     */
    public function getOptionalLocalizedString(string $name, ?array $default): ?array
    {
        if (!$this->hasValue($name)) {
            // the option wasn't found, or it matches the default value. In any case, return this value
            return $default;
        }

        return $this->getLocalizedString($name);
    }


    /**
     * Get public key from metadata.
     *
     * @param string|null $use The purpose this key can be used for. (encryption or signing).
     * @param bool $required Whether the public key is required. If this is true, a
     *                       missing key will cause an exception. Default is false.
     * @param string $prefix The prefix which should be used when reading from the metadata
     *                       array. Defaults to ''.
     *
     * @return array Public key data, or empty array if no public key or was found.
     *
     * @throws \Exception If the certificate or public key cannot be loaded from location.
     * @throws \SimpleSAML\Error\Exception If the location does not contain a valid PEM-encoded certificate, or there
     *                                     is no certificate in the metadata.
     */
    public function getPublicKeys(?string $use = null, bool $required = false, string $prefix = ''): array
    {
        if ($this->hasValue($prefix . 'keys')) {
            $ret = [];
            foreach ($this->getArray($prefix . 'keys') as $key) {
                if ($use !== null && isset($key[$use]) && !$key[$use]) {
                    continue;
                }
                if (isset($key['X509Certificate'])) {
                    // Strip whitespace from key
                    $key['X509Certificate'] = preg_replace('/\s+/', '', $key['X509Certificate']);
                }
                $ret[] = $key;
            }
            if (!empty($ret)) {
                return $ret;
            }
        } elseif ($this->hasValue($prefix . 'certData')) {
            $certData = $this->getString($prefix . 'certData');
            $certData = preg_replace('/\s+/', '', $certData);
            $keyName = $this->getOptionalString($prefix . 'key_name', null);
            return [
                [
                    'name'            => $keyName,
                    'encryption'      => true,
                    'signing'         => true,
                    'type'            => 'X509Certificate',
                    'X509Certificate' => $certData,
                ],
            ];
        } elseif ($this->hasValue($prefix . 'certificate')) {
            $location = $this->getString($prefix . 'certificate');

            $cryptoUtils = new Utils\Crypto();
            $data = $cryptoUtils->retrieveCertificate($location);

            if ($data === null) {
                throw new Exception(
                    $this->location . ': Unable to load certificate/public key from location "' . $location . '".',
                );
            }

            // extract certificate data (if this is a certificate)
            $pattern = '/^-----BEGIN CERTIFICATE-----([^-]*)^-----END CERTIFICATE-----/m';
            if (!preg_match($pattern, $data, $matches)) {
                throw new Error\Exception(
                    $this->location . ': Could not find PEM encoded certificate in "' . $location . '".',
                );
            }
            $certData = preg_replace('/\s+/', '', $matches[1]);
            $keyName = $this->getOptionalString($prefix . 'key_name', null);

            return [
                [
                    'name'            => $keyName,
                    'encryption'      => true,
                    'signing'         => true,
                    'type'            => 'X509Certificate',
                    'X509Certificate' => $certData,
                ],
            ];
        }

        // If still here, we didn't find a certificate of the requested use
        if ($required === true) {
            throw new Error\Exception($this->location . ': Missing certificate in metadata.');
        } else {
            return [];
        }
    }


    /**
     * Clear any configuration information cached.
     * Allows for configuration files to be changed and reloaded during a given request. Most useful
     * when running phpunit tests and needing to alter config.php between test cases
     *
     */
    public static function clearInternalState(): void
    {
        self::$configDirs = [];
        self::$instance = [];
        self::$loadedConfigs = [];
    }
}
