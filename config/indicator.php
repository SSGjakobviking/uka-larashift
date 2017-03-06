<?php

return [
    'antal-registrerade-studenter' => [
    
        'groupColumns' => [
            'name' => 'Ämnesområde',
            'child' => [
                'name' => 'Ämnesdelsområde',
                'child' => [
                    'name' => 'Ämnesgrupp',
                ],
            ],
        ],

    ],
];