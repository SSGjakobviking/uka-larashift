<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Indicator;
use Illuminate\Http\Request;

class TotalsController extends Controller
{

    public function index(Request $request, Indicator $indicator, $year)
    {
        // $indicatorId = $request->input('indicator');

        // if (empty($indicatorId)) {
        //     dd('No indicator specified.');
        // }

        // $indicator = Indicator::find($indicatorId);

        // if (is_null($indicator)) {
        //     dd('Indicator doesn\'t exist.');
        // }
        $gender = 'Totalt';
        $dataset = Dataset::where('indicator_id', $indicator->id)->first();

        $groups = $this->groups($dataset, $year, $gender);

        $genders = $this->gender($dataset, $year);

        $totalColumns = $this->totalColumns($dataset);

        $yearlyTotals = $this->yearlyTotals($indicator);

        $data = [
            'indicator' => [
                'id'            => $indicator->id,
                'name'          => $indicator->name,
                'description'   => $indicator->description,
                'measurement'   => $indicator->measurement,
            ],
            'groups' => [
                'column' => 'Ämnesområden',
                'totals' => $groups
            ],
            'genders' => [
                'column' => 'Kön',
                'totals' => $genders,
            ],
            'total_columns' => [
                'column' => 'Åldersgrupper',
                'totals' => $totalColumns,
            ],
            'yearly_totals' => [
                'column'    => 'Tid',
                'totals'    => $yearlyTotals,
            ],
        ];

        return $data;
    }

    /**
     * Retrieve all groups including their total value
     * @param  [type] $dataset
     * @return [type]
     */
    private function groups($dataset, $year, $gender)
    {
        // $totals = $dataset->totals()
        //             ->where('gender', $gender)->first()
        //             ->where('year', $year)
        //             ->where('relation_type', 'App\group');

        // // foreach($totals as $total) {
        // //     $id = $total->relation->id;
        // //     $name = $total->relation->name;
        // //     $value = $total->values->first()->value;
        // // }
        // // $cool = $totals->relation;
        // $test = $totals->with(['relation' => function($query) {
        //     $query->where('parent_id', null);
        // }])->get();
        // dd($test);
        // $test = $totals->map(function($total) {
        //     $group = $total->relation->where('parent_id', null)->get();
        //     dd($group);
        //     return [
        //         'id'    => $group->id,
        //         'name'  => $group->name,
        //         'value' => $total->values->first()->value,
        //     ];
        // });
       
        return $dataset->groups->map(function($group) use ($year) {
            return [
                'id'    => $group->id,
                'name'  => $group->name,
                'value' => $group->totals->first()->values->first()->value
            ];
        });
    }

    /**
     * Retrieve gender types
     * @param  [type] $dataset
     * @return [type]
     */
    private function gender($dataset)
    {
        return $dataset->totals->groupBy('gender')->map(function($total) {
            return [
                'id'     => str_slug($total->first()->gender),
                'gender' => $total->first()->gender,
                'value'  => $total->first()->values->first()->value,
            ];
        });
    }

    /**
     * Retrieve total columns and their values.
     * 
     * @param  [type] $dataset
     * @return \Illuminate\Support\Collection
     */
    private function totalColumns($dataset)
    {
        $values = $dataset->totals
                    ->where('gender', 'Totalt')
                    ->first()->values;

        return $values->map(function($total) {
            return [
                'id'   => $total->column->id,
                'name' => $total->column->name,
                'value' => $total->value,
            ];
        });
    }

    private function yearlyTotals(Indicator $indicator)
    {
        $datasets = $indicator->datasets;
        $gender = 'Totalt';
        
        $yearlyTotals = $datasets->map(function($dataset) use($gender) {
            $totals = $dataset->totals->where('gender', $gender)->first();

            return [
                'year' => $totals->year,
                'value' => $totals->values()->first()->value,
            ];
        });
        
        return $yearlyTotals;
    }
}
