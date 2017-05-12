<?php

namespace App;

use App\Group;
use App\TotalColumn;
use App\University;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\DB;

class Search {

    protected $client;
    protected $indicator;
    protected $datasetId;
    protected $params;

    public function __construct($client, $indicator, $dataset)
    {
        $this->client = $client;
        $this->indicator = $indicator;
        $this->dataset = $dataset;
        $this->groups();
    }

    public function search($query)
    {
        $params = [
            'index' => $this->indicator->slug,
            'type'  => 'university,group,gender,age_group',
            'body'  => [
                'size' => 100,
                'query' => [
                    'match_phrase_prefix' => [
                        'name' => $query,
                    ],
                ],
            ],
        ];

        $results = $this->client->search($params);

        $results = collect($results['hits']['hits']);
        $ordered = $this->orderResults($results);
        // $ordered = $ordered->sortBy('id')->groupBy('parent_id')->collapse();
        $ordered = $ordered->sortBy('order')->values();

        $results = $results->map(function($item, $key) use($ordered) {
            $item['_source'] = $ordered[$key];
            return $item;
        });

        return $results;
    }

    public function orderResults($results)
    {
        $results = collect($results);

        $results = $results->map(function($item) {
            return $item['_source'];
        });

        return $results->sortBy('parent_id');
    }

    public function index()
    {
        $index = $this->indicator->slug;

        if (! $this->indexExist($index)) {
            $this->client->indices()->create($this->params());
        }
        
        foreach($this->groups() as $type => $group) {

            foreach($group as $list) {
                $document = collect([]);
                $document = $document->merge($list)->toArray();
                $document['dataset_id'] = $this->dataset['dataset_id'];

                $params['body'][] = [
                    'index' => [
                        '_index' => $index,
                        '_type' => $type,
                    ]
                ];

                $params['body'][] = $document;
            }

            $responses = $this->client->bulk($params);

            // erase the old bulk request
            $params = ['body' => []];

            // unset the bulk response when you are done to save memory
            unset($responses);
        }

        // Send the last batch if it exists
        if (!empty($params['body'])) {
            $responses = $this->client->bulk($params);
        }
    }

    /**
     * Retrieve 
     * @return [type]
     */
    private function groups()
    {
        $universities = University::get(['id', 'name'])->map(function($item) {
            $item['order'] = 1;
            $item['group'] = 'university';
            return $item;
        });

        $groups = Group::get(['id', 'parent_id', 'name'])->map(function($item) {
            $item['order'] = 2;
            $item['group'] = 'group';
            return $item;
        });

        $ageGroup = TotalColumn::get(['id', 'name'])->map(function($item) {
            $item['order'] = 3;
            $item['group'] = 'age_group';
            return $item;
        });

        $genders = collect([
                [
                    'id' => 'Män',
                    'name' => 'Män',
                ], [
                    'id' => 'Kvinnor',
                    'name' => 'Kvinnor',
                ]
            ])->map(function($item) {
                $item['order'] = 4;
                $item['group'] = 'gender';

                return $item;
            });

        return [
            'university' => $universities,
            'group' => $groups,
            'gender' => $genders,
            'age_group' => $ageGroup,
        ];
    }

    /**
     * Check whether index exist or not.
     * 
     * @param  string $index
     * @return boolean
     */
    public function indexExist($index)
    {
        return $this->client->indices()->exists(['index' => $index]);
    }

    /**
     * Removes the whole index.
     * 
     * @return [type]
     */
    public function remove()
    {
        $this->client->indices()->delete(['index' => $this->indicator->slug]);
    }

    /**
     * Create default mapping structure array.
     * 
     * @return [type]
     */
    private function params()
    {
        return [
            'index' => $this->indicator->slug,
            'body' => [
                'settings' => [
                    'number_of_shards' => 3,
                    'number_of_replicas' => 2,
                    'analysis' => [
                        'analyzer' => [
                            'my_analyzer' => [
                                'type' => 'custom',
                                'tokenizer' => 'standard',
                                'filter' => ['lowercase', 'synonym_filter'],
                            ],
                        ],
                        'filter' => [
                            'synonym_filter' => [
                                'type' => 'synonym',
                                'ignore_case' => true,
                                'synonyms' => [
                                    'kvinna, kvinnor, kvinnliga',
                                    'man, män, manliga',
                                    'kth, kungl tekniska högskolan',
                                ],
                            ],
                        ],
                    ],
                ],
                'mappings' => [
                    'university' => [
                        '_source' => [
                            'enabled' => true
                        ],
                        'properties' => [
                            'name' => [
                                'type' => 'text',
                                'analyzer' => 'my_analyzer',
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
                                'analyzer' => 'my_analyzer',
                            ],
                        ]
                    ],
                    'gender' => [
                        '_source' => [
                            'enabled' => true
                        ],
                        'properties' => [
                            'id' => [
                                'type' => 'text',
                                'analyzer' => 'my_analyzer',
                            ],
                            'name' => [
                                'type' => 'text',
                                'analyzer' => 'my_analyzer',
                            ],
                        ]
                    ],
                    'age_group' => [
                        '_source' => [
                            'enabled' => true
                        ],
                        'properties' => [
                            'name' => [
                                'type' => 'text',
                                'analyzer' => 'my_analyzer',
                            ],
                        ]
                    ],
                ]
            ]
        ];
    }

}