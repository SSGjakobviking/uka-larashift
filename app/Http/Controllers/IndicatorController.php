<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Indicator;
use Illuminate\Http\Request;

class IndicatorController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
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

    public function saveDataset(Request $request)
    {
        dd($request->all());
    }
}
