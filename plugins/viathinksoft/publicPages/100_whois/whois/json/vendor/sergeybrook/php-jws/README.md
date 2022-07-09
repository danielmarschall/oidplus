# PHP - JWS

JSON Web Signature (JWS) library for PHP

There are two signing algorithms implemented:
- Class JwsMac: For HMAC signature using SHA-256, SHA-384 or SHA-512.
- Class JwsRsa: For RSASSA-PKCS1-v1_5 signature using SHA-256, SHA-384 or SHA-512.

## Installation:

Using [Composer](https://getcomposer.org/):

```bash
$ composer require sergeybrook/php-jws
```

Or:

- Copy "src" dir to your project (optionally rename it to whatever you want).
- Require class autoloader (included):

```php
<?php
require_once("<src_dir>/autoload.php");
...
```

## Usage:

See `/examples` dir.

## Specs:

- JSON Web Signature (JWS) - [RFC 7515](https://tools.ietf.org/html/rfc7515)
- JSON Web Algorithms (JWA) - [RFC 7518](https://tools.ietf.org/html/rfc7518)
