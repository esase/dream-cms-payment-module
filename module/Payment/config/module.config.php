<?php

return [
    'controllers' => [
        'invokables' => [
            'payments-administration' => 'Payment\Controller\PaymentAdministrationController',
            'payments-widget' => 'Payment\Controller\PaymentWidgetController',
            'payments' => 'Payment\Controller\PaymentProcessController',
            'payments-console' => 'Payment\Controller\PaymentConsoleController'
        ]
    ],
    'router' => [
        'routes' => [
        ],
    ],
    'console' => [
        'router' => [
            'routes' => [
                'payments clean' => [
                    'options' => [
                        'route'    => 'payment clean expired items [--verbose|-v]',
                        'defaults' => [
                            'controller' => 'payments-console',
                            'action'     => 'cleanExpiredItems'
                        ]
                    ]
                ]
            ]
        ]
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type'     => 'getText',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
                'text_domain'  => 'default'
            ]
        ]
    ],
    'view_helpers' => [
    ]
];