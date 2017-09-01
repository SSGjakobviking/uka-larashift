<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Filter;
use App\Helpers\DatasetHelper;
use App\Indicator;
use App\Search;
use App\Total;
use Elasticsearch\ClientBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class SearchController extends Controller
{

    public function index(Request $request, $indicator, $query)
    {
        // Log::info('Before published');
        $lastPublishedDataset = DatasetHelper::lastPublishedDataset($indicator);
        // Log::info('After published');
        $client = ClientBuilder::create()->build();
        $results = [];
        // Log::info('initialize search');
        $search = new Search($client, $indicator, $lastPublishedDataset);
        // Log::info('after search init');
        if (! empty($query)) {
            // Log::info('Try query!');
            try {
                // Log::info('Before search');
                $results['result'] = $search->search($query, $lastPublishedDataset['year']);
                // Log::info('After search');
            } catch(\Exception $e) {
                $results = [
                    'error' => true,
                    'code'  => 404,
                    'message' => 'Kunde inte hitta en träff med söktermen ' . $query,
                ];

                return Response::json($results, 404);
            }
        }

        return $results;
    }
}
