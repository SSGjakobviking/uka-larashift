<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Filter;
use App\Indicator;
use App\Search;
use App\Total;
use Elasticsearch\ClientBuilder;
use Illuminate\Http\Request;

class SearchController extends Controller
{

    public function index(Request $request)
    {
        $indicator = Indicator::find(1);
        $lastPublishedDataset = $this->lastPublishedDataset($indicator);
        $client = ClientBuilder::create()->build();
        $results = null;

        $search = new Search($client, $indicator, $lastPublishedDataset);

        if (! empty($request->q)) {
            $results = $search->search($request->q);
            $results = $this->generateFilterUrl($results, $indicator, $lastPublishedDataset['year']);
        }

        return view('search.index', ['results' => $results]);
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

    private function generateFilterUrl($results, $indicator, $year)
    {
        return $results->map(function($result) use($indicator, $year) {
            $filterArgs = [
                $result['_source']['group'] => $result['_source']['id'],
                'year' => $year,
            ];

            $children = $result['children'] ?? [];

            return new Filter($filterArgs, $indicator, $year, $children);
        });
    }
}
