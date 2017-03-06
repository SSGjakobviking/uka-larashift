<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Indicator;
use Illuminate\Http\Request;

class TotalsController extends Controller
{

    public function index(Request $request)
    {
        $indicatorId = $request->input('indicator');

        if (empty($indicatorId)) {
            dd('No indicator specified.');
        }

        $indicator = Indicator::find($indicatorId);

        if (is_null($indicator)) {
            dd('Indicator doesn\'t exist.');
        }

        $dataset = Dataset::where('indicator_id', $indicator->id)->first();
        $groups = $this->groups($dataset);
        $genders = $this->gender($dataset);
        $totalColumns = $this->totalColumns($dataset);

        $data = [
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
        ];

        return $data;
    }

    /**
     * Retrieve all groups including their total value
     * @param  [type] $dataset
     * @return [type]
     */
    private function groups($dataset)
    {
        return $dataset->groups->map(function($group) {
            return [
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
        $genders = $dataset->totals->groupBy('gender')->map(function($total) {
            return [
                'gender' => $total->first()->gender,
                'value'  => $total->first()->values->first()->value,
            ];
        });

        return $genders;
    }

    /**
     * Retrieve total columns and their values
     * @param  [type] $dataset
     * @return [type]
     */
    private function totalColumns($dataset)
    {
        $values = $dataset->totals
                    ->where('gender', 'Totalt')
                    ->first()->values;

        return $values->map(function($total) {
            return [
                'name' => $total->column->name,
                'value' => $total->value,
            ];
        });
    }
}
