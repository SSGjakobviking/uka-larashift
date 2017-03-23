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
            'larosate'          => 'Lärosäten',
            'amnesomrade'       => 'Ämnesområden',
            'amnesdelsomrade'   => 'Ämnesdelsområden',
            'amnesgrupp'        => 'Ämnesgrupp',
        ],

        'dynamic_title' => [
            'default'   => 'Antal{gender} registrerade studenter{age_group}{group}{university}{term}{year}',
            'university' => 'vid',
            'term'      => [
                'vt'    => 'VT',
                'ht'    => 'HT',
            ],
            'group'     => 'inom',
            'gender'    => [
                'man'       => 'manliga',
                'kvinnor'   => 'kvinnliga'
            ],
            'age_group' => [
                '24'    => 'i åldersgruppen -24 år',
                '25-34'  => 'i åldersgruppen 25-34 år',
                '35'    => 'i åldersgruppen 35- år',
                'antal'    => null,
            ],
        ]

    ],
];