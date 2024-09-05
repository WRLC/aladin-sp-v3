# SimpleSAMLphp Sanity check module

![Build Status](https://github.com/simplesamlphp/simplesamlphp-module-sanitycheck/workflows/CI/badge.svg?branch=master)
[![Coverage Status](https://codecov.io/gh/simplesamlphp/simplesamlphp-module-sanitycheck/branch/master/graph/badge.svg)](https://codecov.io/gh/simplesamlphp/simplesamlphp-module-sanitycheck)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/simplesamlphp/simplesamlphp-module-sanitycheck/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/simplesamlphp/simplesamlphp-module-sanitycheck/?branch=master)
[![Type Coverage](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-sanitycheck/coverage.svg)](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-sanitycheck)
[![Psalm Level](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-sanitycheck/level.svg)](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-sanitycheck)

## Install

Install with composer

```bash
vendor/bin/composer require simplesamlphp/simplesamlphp-module-sanitycheck
```

## Configuration

Next thing you need to do is to enable the module:

in `config.php`, search for the `module.enable` key and set `sanitycheck` to true:

```php
'module.enable' => [ 'sanitycheck' => true, … ],
```
