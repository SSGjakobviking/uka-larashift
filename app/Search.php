<?php

namespace App;

use App\Group;
use App\TotalColumn;
use App\University;
use Elasticsearch\ClientBuilder;

class Search {

    protected $client;
    protected $indicator;
    protected $datasetId;
    protected $params;

    public function __construct($client, $indicator, $datasetId)
    {
        $this->client = $client;
        $this->indicator = $indicator;
        $this->datasetId = $datasetId;
    }

    public function search($query)
    {
        $params = [
            'index' => $this->indicator->slug,
            'type'  => 'university,group,gender,age-group',
            'body'  => [
                'size' => 100,
                'query' => [
                    'query_string' => [
                        'default_field' => 'name',
                        'query' => '*' . $query . '*',
                    ],
                ],
            ],
        ];

        return $this->responseFormatter($this->client->search($params));
    }

    private function responseFormatter($response)
    {
        return $response['hits']['hits'];
    }

    public function index()
    {
        $index = $this->indicator->slug;

        if (! $this->client->indices()->exists(['index' => $index])) {
            $this->client->indices()->create($this->params());
        }
        
        $params = ['body' => []];
        $i = 0;

        foreach($this->groups() as $group => $names) {

            // dd($group);
            foreach($names as $name) {
                $i++;
                $params['body'][] = [
                    'index' => [
                        '_index' => $index,
                        '_type' => $group,
                        '_id' => $i
                    ]
                ];

                $params['body'][] = [
                    'name' => $name,
                ];

                // Every 1000 documents stop and send the bulk request
                if ($i % 50 == 0) {
                    $responses = $this->client->bulk($params);

                    // erase the old bulk request
                    $params = ['body' => []];

                    // unset the bulk response when you are done to save memory
                    unset($responses);
                }

            }
        }

        // Send the last batch if it exists
        if (!empty($params['body'])) {
            $responses = $this->client->bulk($params);
        }
    }

    public function remove()
    {
        
    }

    private function params()
    {
        $params = [
            'index' => $this->indicator->slug,
            'body' => [
                'settings' => [
                    'number_of_shards' => 3,
                    'number_of_replicas' => 2
                ],
                'mappings' => [
                    'university' => [
                        '_source' => [
                            'enabled' => true
                        ],
                        'properties' => [
                            'name' => [
                                'type' => 'text',
                                'analyzer' => 'standard'
                            ],
                        ]
                    ],
                    'group' => [
                        '_source' => [
                            'enabled' => true
                        ],
                        'properties' => [
                            'name' => [
                                'type' => 'text',
                                'analyzer' => 'standard'
                            ],
                        ]
                    ],
                    'gender' => [
                        '_source' => [
                            'enabled' => true
                        ],
                        'properties' => [
                            'name' => [
                                'type' => 'text',
                                'analyzer' => 'standard'
                            ],
                        ]
                    ],
                    'age-group' => [
                        '_source' => [
                            'enabled' => true
                        ],
                        'properties' => [
                            'name' => [
                                'type' => 'text',
                                'analyzer' => 'standard'
                            ],
                        ]
                    ],
                ]
            ]
        ];

        return $params;
    }

    private function groups()
    {
        $universities = University::all();
        $groups = Group::all();
        $ageGroup = TotalColumn::all();

        return [
            'university' => $universities->pluck('name')->toArray(),
            'group' => $groups->pluck('name')->toArray(),
            'gender' => ['Kvinnor', 'Män'],
            'age-group' => $ageGroup->pluck('name')->toArray(),
        ];
    }

}