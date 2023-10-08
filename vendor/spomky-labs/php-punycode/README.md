Punycode
========

[![Build Status](https://travis-ci.org/Spomky-Labs/php-punycode.svg)](https://travis-ci.org/Spomky-Labs/php-punycode)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Spomky-Labs/php-punycode/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Spomky-Labs/php-punycode/?branch=master)

[![Coverage Status](https://coveralls.io/repos/Spomky-Labs/php-punycode/badge.svg?branch=master&service=github)](https://coveralls.io/github/Spomky-Labs/php-punycode?branch=master)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d59a9463-ed65-4304-a764-04f62d3fd58c/big.png)](https://insight.sensiolabs.com/projects/d59a9463-ed65-4304-a764-04f62d3fd58c)

[![Latest Stable Version](https://poser.pugx.org/spomky-labs/php-punycode/v/stable.png)](https://packagist.org/packages/spomky-labs/php-punycode)
[![Total Downloads](https://poser.pugx.org/spomky-labs/php-punycode/downloads.png)](https://packagist.org/packages/spomky-labs/php-punycode)
[![Latest Unstable Version](https://poser.pugx.org/spomky-labs/php-punycode/v/unstable.png)](https://packagist.org/packages/spomky-labs/php-punycode)
[![License](https://poser.pugx.org/spomky-labs/php-punycode/license.png)](https://packagist.org/packages/spomky-labs/php-punycode)


A Bootstring encoding of Unicode for Internationalized Domain Names in Applications (IDNA).

Original code from https://github.com/true/php-punycode

# The Release Process

The release process [is described here](doc/Release.md).

# Prerequisites

This library needs at least ![PHP 5.4+](https://img.shields.io/badge/PHP-5.4%2B-ff69b4.svg).

It has been successfully tested using `PHP 5.4` to `PHP 5.6`, `PHP 7` (`7.0` and nightly) and `HHVM`.

# Installation

The preferred way to install this library is to rely on Composer:

```sh
composer require "spomky-labs/php-punycode"
```

# How to use

```php
<?php

// Import Punycode
use SpomkyLabs\Punycode;

var_dump(Punycode::encode('renangonçalves.com'));
// outputs: xn--renangonalves-pgb.com

var_dump(Punycode::decode('xn--renangonalves-pgb.com'));
// outputs: renangonçalves.com
```

# Contributing

Requests for new features, bug fixed and all other ideas to make this library useful are welcome. [Please follow these best practices](doc/Contributing.md).

# Licence

This library is release under [MIT licence](LICENSE.txt).
