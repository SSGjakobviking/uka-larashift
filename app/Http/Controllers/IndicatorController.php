<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Helpers\DatasetHelper;
use App\Indicator;
use App\Search;
use App\Total;
use Elasticsearch\ClientBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndicatorController extends Controller
{

    public function __construct()
    {
        $this->middleware(['auth', 'admin'], ['except' => 'all']);
    }

    /**
     * Retrieves all indicator groups with their indicators.
     * 
     * @return [type]
     */
    public function all()
    {
        $allIndicators = Indicator::all();

        // loop through all indicators 
        $indicators = $allIndicators->map(function($item) {
            // retrieve last published dataset (which we are showing by default in the GUI)
            $lastPublishedDataset = DatasetHelper::lastPublishedDataset($item);

            if (! $lastPublishedDataset) {
                return false;
            }

            return [
                'id' => $item->id,
                'name' => $item->name,
                'most_recent_url' => route('totals', $item->id) . '/?year=' . trim($lastPublishedDataset->year),
                'indicator_group' => $item->indicatorGroup->name,
            ];
        })->groupBy('indicator_group');

        // remove indicators with no datasets
        if ($indicators->has('')) {
            $indicators->forget('');
        }
        echo 'hello';
        // return response()->json($indicators);
    }
    
    public function index()
    {
        $indicators = Indicator::all();

        return view('indicator.index', ['indicators' => $indicators]);
    }

    public function update($id, Request $request)
    {
        $indicator = Indicator::findOrFail($id);

        $indicator->update($request->all());

        return redirect()->back();
    }

    public function edit($indicator)
    {
        return view('indicator.edit', [
            'indicator' => $indicator,
            'previewData' => $indicator->datasets()->preview()->get(),
            'publishedData' => $indicator->datasets()->published()->get(),
            'unAttachedData'  => Dataset::unAttached()->get(),
        ]);
    }

    /**
     * Saves the dataset with the right status depending on the form being used (preview|production).
     * 
     * @param  [type]  $id
     * @param  Request $request
     * @return [type]
     */
    public function saveDataset($id, Request $request)
    {
        $this->updateStatus($id, $request);

        return redirect()->back();
    }

    private function indexDataset($indicatorId)
    {
        $indicator = Indicator::find($indicatorId);
        
        $lastPublishedDataset = DatasetHelper::lastPublishedDataset($indicator);

        $client = ClientBuilder::create()->build();

        $search = new Search($client, $indicator, $lastPublishedDataset);

        if ($search->indexExist($indicator->slug)) {

            // fetch one document from elasticsearch to retrieve the dataset id
            $result = $search->one();
            $currentIndexedDataset = $result['_source']['dataset_id'];

            // remove indexed dataset if it matches with the current unattached dataset.
            if ($currentIndexedDataset != $lastPublishedDataset->dataset_id) {
                $search->remove();

                if ($lastPublishedDataset) {
                    $search->index();
                }
            }
        } else {
            $search->index();
        }

    }

    /**
     * Checks if the input is added via preview or production form.
     * 
     * @param  [type] $request
     * @return [type]
     */
    private function detectStatus($request)
    {
        $datasets = $request->input('dataset_preview');
        $status = 'preview';

        if (! is_null($request->input('dataset_production'))) {
            $datasets = $request->input('dataset_production');
            $status = 'published';
        }

        return [
            'datasets' => $datasets,
            'status'    => $status,
        ];
    }

    /**
     * Loops through all of the selected datasets and updates their statuses.
     * 
     * @param  [type] $id
     * @param  [type] $request
     * @return [type]
     */
    public function updateStatus($id, $request)
    {

        // Retrieve dataset status, preview or production?
        $input = $this->detectStatus($request);

        foreach ($input['datasets'] as $datasetId) {
            Dataset::where('id', $datasetId)->update([
                'indicator_id' => $id,
                'status'    => $input['status'],
            ]);
        }

        // Index only production dataset in elasticsearch
        if ($input['status'] === 'published') {
            $this->indexDataset($id);
        }
    }
}
