<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Indicator;
use Illuminate\Http\Request;

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
            'previewData' => Dataset::preview()->get(),
            'publishedData' => Dataset::published()->get(),
            'unAttachedData'    => Dataset::unAttached()->get(),
        ]);
    }

    public function saveDataset($id, Request $request)
    {
        $datasets = $request->input('dataset_preview');
        $status = 'preview';

        if (! is_null($request->input('dataset_production'))) {
            $datasets = $request->input('dataset_production');
            $status = 'published';
        }

        foreach ($datasets as $datasetId) {
            Dataset::where('id', $datasetId)->update([
                'indicator_id' => $id,
                'status'    => $status,
            ]);
        }

        return redirect()->back();
    }
}
