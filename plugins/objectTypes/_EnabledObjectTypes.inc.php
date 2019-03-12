<?php

// To disable object types you don't need, simply comment out the lines below
// or delete the folders. You can also adjust the order in which the icons appear
// in the treeview by shifting the following lines.

if (is_dir(__DIR__ . '/oid'))   include_once __DIR__ . '/oid/OIDplusOid.class.php';
if (is_dir(__DIR__ . '/doi'))   include_once __DIR__ . '/doi/OIDplusDoi.class.php';
if (is_dir(__DIR__ . '/java'))  include_once __DIR__ . '/java/OIDplusJava.class.php';
if (is_dir(__DIR__ . '/guid'))  include_once __DIR__ . '/guid/OIDplusGuid.class.php';
if (is_dir(__DIR__ . '/ipv4'))  include_once __DIR__ . '/ipv4/OIDplusIpv4.class.php';
if (is_dir(__DIR__ . '/ipv6'))  include_once __DIR__ . '/ipv6/OIDplusIpv6.class.php';
if (is_dir(__DIR__ . '/gs1'))   include_once __DIR__ . '/gs1/OIDplusGs1.class.php';
if (is_dir(__DIR__ . '/other')) include_once __DIR__ . '/other/OIDplusOther.class.php';

