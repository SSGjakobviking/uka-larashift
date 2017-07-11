<?php

namespace App\Http\Controllers;

use App\Dataset;
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
        $this->middleware(['auth', 'admin']);
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

    public function edit($id)
    {
        $indicator = Indicator::find($id);

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
        
        $dataset = $this->lastPublishedDataset($indicator);

        $client = ClientBuilder::create()->build();

        $search = new Search($client, $indicator, $dataset);

        $search->index();
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
        if ($input['status'] == 'published') {
            $this->indexDataset($id);
        }
    }
}
