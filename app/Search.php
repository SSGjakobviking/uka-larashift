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
    protected $groupChildIds = [];

    public function __construct($client, $indicator, $dataset)
    {
        $this->client = $client;
        $this->indicator = $indicator;
        $this->dataset = $dataset;
    }

    public function search($query, $year = null)
    {
        $params = [
            'index' => $this->indicator->slug,
            'type'  => 'university,group,gender',
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
        
        // no match
        if ($results['hits']['total'] == 0) {
            throw new \Exception('Hittade inget som matchade sökordet ' . $query);
        }

        $results = collect($results['hits']['hits']);
        
        $ordered = $this->orderResults($results);

        $grouped = $ordered->groupBy('_source.parent_id');

        $result = [];

        foreach ($grouped as $group) {
            $result[] = $this->iterateChildren($grouped, $group, $year);
        }

        $result = collect(array_filter($result))->collapse();

        return $result;
    }

    private function iterateChildren($collection, $group, $year)
    {
        $result = [];

        foreach ($group as $child) {
            $currentChild = $child['_source']['id'];

            // skip duplicates
            if (in_array($currentChild, $this->groupChildIds)) continue;

            $filterArgs = [
                $child['_source']['group'] => $child['_source']['id'],
                'year' => $year,
            ];

            $filter = new Filter($filterArgs, $this->indicator, $year, $child);

            $data = [
                'title' => $filter->title(),
                'url'   => $filter->url(),
            ];

            if ($collection->has($currentChild)) {
                $child['children'] = $this->iterateChildren($collection, $collection->get($currentChild), $year);
                $data['children'] = $child['children'];
            }

            $result[] = $data;
            $this->groupChildIds[] = $currentChild;

        }

        return $result;
    }

    private function orderResults($results)
    {
        $results = collect($results);

        $results = $results->sortBy(function($item) { // sort by order first
            return $item['_source']['order'];
        })->values()->sortBy(function($item) { // sort by id within order groups
            return isset($item['_source']['group_id']) ? $item['_source']['group_id'] : $item['_source']['id'];
        });

        return $results;
    }

    /**
     * Retrieves dataset_id from the current indicator index so we can check if we need to replace the indexed data with
     * a new dataset.
     * 
     * @return array
     */
    public function one()
    {
        $params = [
            'index' => $this->indicator->slug,
            'type'  => 'university,group_slug,gender',
            'body'  => [
                'size' => 1,
            ],
        ];

        $results = $this->client->search($params);

        return isset($results['hits']['hits'][0]) ? $results['hits']['hits'][0] : false;
    }

    /**
     * Creates an index in elasticsearch and bulk indexes search data.
     * 
     * @return void
     */
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
                $document['dataset_id'] = $this->dataset->id;

                $params['body'][] = [
                    'index' => [
                        '_index' => $index,
                        '_type' => $type,
                    ]
                ];

                $params['body'][] = $document;
            }

            if (! empty($params['body'])) {
                $responses = $this->client->bulk($params);
            }

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
        $dataset = $this->dataset;

        $universities = Total::where('dataset_id', $this->dataset->id)
                        ->whereHas('university')
                        ->with('university')
                        ->groupBy('university_id')
                        ->get()
                        ->map(function($item) {
                            return [
                                'id' => $item->university->id,
                                'name' => $item->university->name,
                                'group' => 'university',
                                'order' => 1,
                            ];
                        });

        $groups = Total::where('dataset_id', $this->dataset->id)->whereHas('group')
                    ->with('group')
                    ->groupBy('group_slug')
                    ->get()
                    ->map(function($item) {
                        return [
                            'parent_id' => $item->group_parent_slug,
                            'id' => $item->group_slug,
                            'group_id' => $item->group->id,
                            'name' => $item->group->name,
                            'group' => 'group_slug',
                            'order' => 2,
                        ];
                    });

        $genders = Total::where('dataset_id', $this->dataset->id)
                    ->where('gender', '!=', 'Total')
                    ->groupBy('gender')
                    ->get(['gender'])
                    ->map(function($item) {
                        return [
                            'id' => $item->gender,
                            'name' => $item->gender,
                            'order' => 4,
                            'group' => 'gender',
                        ];
                    });
    
        return [
            'university' => $universities,
            'group' => $groups,
            'gender' => $genders,
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
                                'tokenizer' => 'whitespace',
                                'filter' => ['lowercase', 'synonym_filter'],
                            ],
                        ],
                        'filter' => [
                            'synonym_filter' => [
                                'type' => 'synonym',
                                'ignore_case' => true,
                                'synonyms' => [
                                    'kvinna, kvinnor, kvinnliga, kvinnlig',
                                    'man, män, manlig, manliga',
                                    'kth, Kungl. Tekniska högskolan',
                                ],
                                'tokenizer' => 'whitespace',
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
                ]
            ]
        ];
    }

}