<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Filter;
use App\Indicator;
use App\Search;
use App\Total;
use Elasticsearch\ClientBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class SearchController extends Controller
{

    public function index(Request $request, $indicator, $query)
    {
        $indicator = Indicator::find($indicator);
        $lastPublishedDataset = $this->lastPublishedDataset($indicator);
        $client = ClientBuilder::create()->build();
        $results = [
            'error' => false,
        ];

        $search = new Search($client, $indicator, $lastPublishedDataset);

        if (! empty($query)) {
            try {
                $results['result'] = $search->search($query, $lastPublishedDataset['year']);
            } catch(\Exception $e) {
                $results = [
                    'error' => true,
                ];

                return Response::json($results, 404);
            }
        }

        return $results;
    }

    private function lastPublishedDataset($indicator)
    {
        $latest = Total::whereHas('dataset', function($query) use($indicator) {
            $query->where('status', 'published');
            $query->where('indicator_id', $indicator->id);
        })
        ->where('gender', 'Total')
        ->orderBy('year', 'desc')
        ->first(['year', 'dataset_id']);

        return $latest;
    }
}
