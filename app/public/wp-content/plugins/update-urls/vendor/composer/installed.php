<?php return array(
    'root' => array(
        'name' => 'kaizencoders/update-urls',
        'pretty_version' => 'dev-master',
        'version' => 'dev-master',
        'reference' => '0b5f8c71862893291fd7c7dd391a74d38c62cc74',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'composer/installers' => array(
            'pretty_version' => 'v1.12.0',
            'version' => '1.12.0.0',
            'reference' => 'd20a64ed3c94748397ff5973488761b22f6d3f19',
            'type' => 'composer-plugin',
            'install_path' => __DIR__ . '/./installers',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'kaizencoders/update-urls' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => '0b5f8c71862893291fd7c7dd391a74d38c62cc74',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'roundcube/plugin-installer' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'shama/baton' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
    ),
);
