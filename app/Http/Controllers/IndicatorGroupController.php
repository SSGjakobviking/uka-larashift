<?php

namespace App\Http\Controllers;

use App;
use App\Http\Requests;
use App\Indicator;
use App\IndicatorGroup;

class IndicatorGroupController extends Controller
{
    public function create()
    {
        return view('indicator-group.create');
    }

    /**
     * Save the indicator group.
     * 
     * @param  Requests\IndicatorGroup $request
     * @return [type]
     */
    public function store(Requests\IndicatorGroup $request)
    {
        App\IndicatorGroup::create($request->all());
        return redirect('indicator');
    }

    public function update(Requests\IndicatorGroup $request, $id)
    {
        App\IndicatorGroup::find($id)->update($request->all());

        return redirect('indicator');
    }

    public function edit($id)
    {
        $indicatorGroup = IndicatorGroup::find($id);

        return view('indicator-group.edit', compact('indicatorGroup'));
    }

    public function destroy($id)
    {
        $indicatorCount = Indicator::where('indicator_group', $id)->count();

        if ($indicatorCount === 0) {
            IndicatorGroup::destroy($id);
        }

        return redirect('indicator');
    }
}
