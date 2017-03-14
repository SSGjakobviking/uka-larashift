<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Group;
use App\Indicator;
use App\Total;
use App\TotalColumn;
use App\TotalValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TotalsController extends Controller
{

    public function index(Request $request, Indicator $indicator, $year)
    {
        $data = [];
        $gender = ! empty($request->gender) ? $request->gender : 'Totalt';

        $groupInput = ! empty($request->group) ? $request->group : null;
        
        $datasetId = Total::where('year', $request->year)->get()->first()->dataset_id;

        $dataset = Dataset::where('indicator_id', $indicator->id)
                    ->where('id', $datasetId)
                    ->get()->first();
        $groupColumn = Group::where('parent_id', $groupInput)->get();
        // dd($groupColumn);
        // var_dump($groupColumn);
        $groups = $this->groups($dataset, $year, $gender, $groupInput);

        $genders = $this->gender($dataset, $year, $groupInput);

        $totalColumns = $this->totalColumns($dataset, $year, $gender, $groupInput);

        $yearlyTotals = $this->yearlyTotals($indicator, $gender, $groupInput);

        if (! $groupColumn->isEmpty()) {
            $groupColumn = $groupColumn->first()->column->name;

            $data['groups'][] = [
                'column' => $groupColumn,
                'totals' => $groups
            ];
        }


        $data['indicator'] = [
            'id'            => $indicator->id,
            'name'          => $indicator->name,
            'description'   => $indicator->description,
            'measurement'   => $indicator->measurement,
        ];

        $data['groups'][] = [
            'column' => 'Kön',
            'totals' => $genders,
        ];
        
        $data['groups'][] = [
            'column' => 'Åldersgrupper',
            'totals' => $totalColumns,
        ];

        $data['yearly_totals'] = [
            'column'    => 'Tid',
            'totals'    => $yearlyTotals,
        ];
        // var_dump($data);
        return $data;
    }

    /**
     * Retrieve all groups including their total value
     * @param  [type] $dataset
     * @return [type]
     */
    private function groups($dataset, $year, $gender = 'Totalt', $groupId)
    {
        $totals = $dataset->totals()
                    ->where('year', $year)
                    ->where('gender', $gender)
                    ->whereHas('group', function($query) use($groupId) {
                        $query->where('parent_id', $groupId);
                    })
                    ->with(['group', 'values'])
                    ->get();

       return $totals->map(function($total) {
            return [
                'id'    => $total->group->id,
                'name'  => $total->group->name,
                'value' => $total->values->first()->value,
            ];
        });
    }

    /**
     * Retrieve gender types
     * @param  [type] $dataset
     * @return [type]
     */
    private function gender($dataset, $year, $groupId)
    {
        $totals = Total::where('dataset_id', $dataset->id)
                    ->where('group_id', $groupId)
                    ->with('values')
                    ->groupBy('gender')
                    ->get();

        return $totals->map(function($total) {
            return [
                'id'     => str_slug($total->gender),
                'gender' => $total->gender,
                'value'  => $total->values->first()->value,
            ];
        });
    }

    /**
     * Retrieve total columns and their values.
     * 
     * @param  [type] $dataset
     * @return \Illuminate\Support\Collection
     */
    private function totalColumns($dataset, $year, $gender, $groupId)
    {
        $totals = DB::table('totals')
            ->select('total_columns.id', 'total_columns.name', 'total_values.value')
            ->leftJoin('total_values', 'totals.id', 'total_values.total_id')
            ->leftJoin('total_columns', 'total_values.column_id', 'total_columns.id')
            ->where('totals.dataset_id', $dataset->id)
            ->where('totals.year', $year)
            ->where('totals.gender', $gender)
            ->where('totals.group_id', $groupId)
            ->groupBy('total_values.column_id')
            ->get();

        return $totals->map(function($total) {
            return [
                'id'   => $total->id,
                'name' => $total->name,
                'value' => $total->value,
            ];
        });
    }

    private function yearlyTotals(Indicator $indicator, $gender, $groupId)
    {
        $datasets = $indicator->datasets()
                    ->with(['totals' => function($query) use($gender, $groupId) {
                        $query->where('gender', $gender);
                        $query->where('group_id', $groupId);
                    }])
                    ->get();

        $yearlyTotals = $datasets->map(function($dataset) {
            $totals = $dataset->totals->first();

            return [
                'year' => $totals->year,
                'value' => $totals->values->first()->value,
            ];
        });
        
        return $yearlyTotals;
    }
}
