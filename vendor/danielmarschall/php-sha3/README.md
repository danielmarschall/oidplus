# php-sha3   [![Build Status](https://app.travis-ci.com/danielmarschall/php-sha3.svg?branch=master)](https://app.travis-ci.com/github/danielmarschall/php-sha3/) [![Coverage Status](https://coveralls.io/repos/github/danielmarschall/php-sha3/badge.svg)](https://coveralls.io/github/danielmarschall/php-sha3)

Pure PHP implementation of SHA-3.

Extended version to support `hash_hmac`.

Original source can be found [here](https://github.com/0xbb/php-sha3).

## Usage

```php
<?php 

use bb\Sha3\Sha3;

Sha3::hash('', 224);
// 6b4e03423667dbb73b6e15454f0eb1abd4597f9a1b078e3f5b5a6bc7

Sha3::hash('', 256);
// a7ffc6f8bf1ed76651c14756a061d662f580ff4de43b49fa82d80a4b80f8434a

Sha3::hash('', 384);
// 0c63a75b845e4f7d01107d852e4c2485c51a50aaaa94fc61995e71bbee983a2ac3713831264adb47fb6bd1e058d5f004

Sha3::hash('', 512);
// a69f73cca23a9ac5c8b567dc185a756e97c982164fe25859e0d1dcc1475c80a615b2123af1f5f94c11e3e9402c3ac558f500199d95b6d3e301758586281dcd26

Sha3::hash_hmac('', 'key', 512);
// 7539119b6367aa902bdc6f558d20c906d6acbd4aba3fd344eb08b0200144a1fa453ff6e7919962358be53f6db2a320d1852c52a3dea3e907070775f7a91f1282

Sha3::shake('', 128, 256);
// 7f9c2ba4e88f827d616045507605853ed73b8093f6efbc88eb1a6eacfa66ef26

Sha3::shake('', 256, 512);
// 46b9dd2b0ba88d13233b3feb743eeb243fcd52ea62b81b82b50c27646ed5762fd75dc4ddd8c0f200cb05019d67b592f6fc821c49479ab48640292eacb3b7c4be
