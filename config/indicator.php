<?php

return [
    'default' => [
        'group_columns' => [
            'larosate'          => 'Lärosäten',
            'amnesomrade'       => 'Ämnesområden',
            'amnesdelsomrade'   => 'Ämnesdelsområden',
            'amnesgrupp'        => 'Ämnesgrupp',
        ],

        'dynamic_title' => [
            'default'   => 'Antal{gender} registrerade studenter{age_group}{group}{university}{year}',
            'university' => 'vid',
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
    'antal-registrerade-studenter' => [
        'group_columns' => [
            'larosate'          => 'Lärosäten',
            'amnesomrade'       => 'Ämnesområden',
            'amnesdelsomrade'   => 'Ämnesdelsområden',
            'amnesgrupp'        => 'Ämnesgrupp',
        ],

        'dynamic_title' => [
            'default'   => 'Antal{gender} registrerade studenter{age_group}{group}{university}{year}',
            'university' => 'vid',
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
    'hst-per-studieform-och-amnesomrade' => [
        'group_columns' => [
            'larosate'          => 'Lärosäten',
            'studieform'        => 'Studieform',
            'amnesomrade'       => 'Ämnesområden',
            'amnesdelsomrade'   => 'Ämnesdelsområden',
            'amnesgrupp'        => 'Ämnesgrupp',
        ],

        'dynamic_title' => [
            'default'   => 'Antal{gender} högskolestuderande{age_group}{group}{university}{year}',
            'university' => 'vid',
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
    'nya-sokande-program' => [
        // 'group_columns' => [
        //     'utbildningsform'  => 'Lärosäten',
        // ],
        'dynamic_title' => [
            'default'   => 'Antal{gender} sökande studenter{age_group}{group}{university}{year}',
            'university' => 'vid',
            'group'     => 'till',
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
    'intakter' => [
        'dynamic_title' => [
            'default'   => 'Summa intäkter{gender} {age_group}{group}{university}{year}',
            'university' => 'vid',
            'group'     => 'till',
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