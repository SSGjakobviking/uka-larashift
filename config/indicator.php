<?php

return [
    'antal-registrerade-studenter' => [

        // 'groupColumns' => [
        //     'name' => 'Ämnesområde',
        //     'child' => [
        //         'name' => 'Ämnesdelsområde',
        //         'child' => [
        //             'name' => 'Ämnesgrupp',
        //         ],
        //     ],
        // ],

        'group_columns' => [
            'amnesomrade' => 'Ämnesområden',
            'amnesdelsomrade' => 'Ämnesdelsområden',
            'amnesgrupp' => 'Ämnesgrupp',
        ],

        'dynamic_title' => [
            'default'   => 'Antal{gender} registrerade studenter{age_group}{group} {year}',
            'group'     => 'inom',
            'gender'    => [
                'man'       => 'manliga',
                'kvinnor'   => 'kvinnliga'
            ],
            'age_group' => [
                'antal'    => null,
                '21-ar'    => 'i åldersgruppen -21 år',
                '22-24-ar'  => 'i åldersgruppen 22-24 år',
                '25-29-ar'  => 'i åldersgruppen 25-29 år',
                '30-34-ar'  => 'i åldersgruppen 30-34 år',
                '35-ar'    => 'i åldersgruppen 35- år',
            ]
        ]

    ],
];