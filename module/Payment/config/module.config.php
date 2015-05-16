<?php

return [
    'controllers' => [
        'invokables' => [
            'payments-administration' => 'Payment\Controller\PaymentAdministrationController',
            'payments-widget' => 'Payment\Controller\PaymentWidgetController',
            'payments' => 'Payment\Controller\PaymentProcessController'
        ]
    ],
    'router' => [
        'routes' => [
        ],
    ],
    'console' => [
        'router' => [
            'routes' => [
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