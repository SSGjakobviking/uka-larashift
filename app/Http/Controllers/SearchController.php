<?php

namespace App\Http\Controllers;

use App\Helpers\DatasetHelper;
use App\Search;
use Elasticsearch\ClientBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class SearchController extends Controller
{
    public function index(Request $request, $indicator, $query)
    {
        $lastPublishedDataset = DatasetHelper::lastPublishedDataset($indicator);
        $client = ClientBuilder::create()->build();
        $results = [];

        $search = new Search($client, $indicator, $lastPublishedDataset);

        if (! empty($query)) {
            try {
                $results['result'] = $search->search($query, $lastPublishedDataset['year']);
            } catch (\Exception $e) {
                $results = [
                    'error' => true,
                    'code' => 404,
                    'message' => 'Kunde inte hitta en träff med söktermen '.$query,
                ];

                return Response::json($results, 404);
            }
        }

        return $results;
    }
}
