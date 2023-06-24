<?php return array(
    'root' => array(
        'name' => '__root__',
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'reference' => NULL,
        'type' => 'library',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        '__root__' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'reference' => NULL,
            'type' => 'library',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'aywan/php-json-canonicalization' => array(
            'pretty_version' => 'master',
            'version' => 'dev-master',
            'reference' => 'main',
            'type' => 'library',
            'install_path' => __DIR__ . '/../aywan/php-json-canonicalization',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'sergeybrook/php-jws' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => 'e1efec3247c1f5b58ecb108cffae6f55da4f4339',
            'type' => 'library',
            'install_path' => __DIR__ . '/../sergeybrook/php-jws',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
    ),
);
