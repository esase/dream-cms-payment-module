<?php

return [
    'compatable' => '2.2.x',
    'version' => '1.0.0',
    'vendor' => 'eSASe',
    'vendor_email' => 'alexermashev@gmail.com',
    'description' => 'The module allows you to shop on the site',
    'system_requirements' => [
        'php_extensions' => [
        ],
        'php_settings' => [
        ],
        'php_enabled_functions' => [
        ],
        'php_version' => null
    ],
    'module_depends' => [
    ],
    'clear_caches' => [
        'setting'       => true,
        'time_zone'     => false,
        'admin_menu'    => true,
        'js_cache'      => false,
        'css_cache'     => false,
        'layout'        => false,
        'localization'  => false,
        'page'          => true,
        'user'          => false,
        'xmlrpc'        => false
    ],
    'resources' => [
    ],
    'install_sql' => __DIR__ . '/../install/install.sql',
    'install_intro' => null,
    'uninstall_sql' => __DIR__ . '/../install/uninstall.sql',
    'uninstall_intro' => null,
    'layout_path' => 'payment'
];