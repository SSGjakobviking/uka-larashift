<?php

namespace App\Http\Controllers;

use App\Indicator;
use App\IndicatorGroup;
use Illuminate\Http\Request;

class IndicatorSettingsController extends Controller
{
    public function edit($id)
    {
        $indicator = Indicator::find($id);
        $indicatorGroups = IndicatorGroup::orderBy('name')->get();

        return view('indicator-settings.edit', compact('indicator', 'indicatorGroups'));
    }

    public function update($id, \App\Http\Requests\Indicator $request)
    {
        $indicator = Indicator::find($id);
        $indicator->update($request->all());
        return redirect()->back()->with('success', 'Indikatorn är uppdaterad.');
    }
}
