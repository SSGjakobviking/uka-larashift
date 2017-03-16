<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\DynamicTitle;
use App\Filter;
use App\Group;
use App\Helpers\StringHelper;
use App\Helpers\UrlHelper;
use App\Indicator;
use App\Total;
use App\TotalColumn;
use App\TotalValue;
use App\TotalsFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TotalsController extends Controller
{

    public function index(Request $request, Indicator $indicator)
    {
        $data = [];
        $config = config('indicator')[$indicator->slug];

        // Retrieve filter args
        $gender = ! empty($request->gender) ? $request->gender : 'Totalt';
        $groupInput = ! empty($request->group) ? $request->group : null;
        $age_group = ! empty($request->age_group) ? $request->age_group : 1;
        $year = $request->year;

        $filters = [
            'year'      => $year,
            'group'     => $request->group,
            'gender'    => $request->gender,
            'age_group' => $request->age_group,
        ];

        $filter = new Filter($filters, $indicator, $year);

        $filterUrl = $filter->url();

        $dynamicTitle = new DynamicTitle($indicator, $filter);

        $data['indicator'] = $this->indicatorData($indicator, $dynamicTitle, $year);
        
        // retrieve dataset id for current year
        $datasetId = Total::where('year', $year)->get()->first()->dataset_id;

        $dataset = Dataset::where('indicator_id', $indicator->id)
                    ->where('id', $datasetId)
                    ->get()->first();

        $groupColumn = Group::where('parent_id', $groupInput)->get();
        
        if (! $groupColumn->isEmpty()) {
            $groupColumn = $groupColumn->first()->column->name;
            $groupColumn = $config['group_columns'][StringHelper::slugify($groupColumn)];
        }

        $groups = $this->groups($dataset, $year, $gender, $groupInput, $age_group, $filter);

        $genders = $this->gender($dataset, $year, $groupInput, $age_group, $filter);

        $totalColumns = $this->totalColumns($dataset, $year, $gender, $groupInput, $filter);

        $yearlyTotals = $this->yearlyTotals($indicator, $gender, $groupInput, $age_group, $filter);

        $totals = new TotalsFormatter();

        if (! $groups->isEmpty()) {
            $totals->addGroup($groupColumn, $groups);
        }

        if (! $filter->get()->get('gender')) {
            $totals->addGroup('Kön', $genders);
        }

        if (! $filter->get()->get('age_group')) {
            $totals->addGroup('Åldersgrupper', $totalColumns);
        }

        $totals->add('Tid', $yearlyTotals);

        $totalsData = $totals->get();

        $data = array_merge($data, $totalsData);
        // var_dump($data);
        return $data;
    }

    /**
     * Formats indicator data.
     * 
     * @param  Illuminate\Support\Collection $indicator
     * @param  Object $dynamicTitle
     * @return array
     */
    private function indicatorData($indicator, $dynamicTitle, $year)
    {
        return [
            'id'            => $indicator->id,
            'name'          => $dynamicTitle->get(),
            'description'   => $indicator->description,
            'measurement'   => $indicator->measurement,
            'current_year'  => $year,
        ];
    }

    /**
     * Retrieve all groups including their total value
     * @param  [type] $dataset
     * @return [type]
     */
    private function groups($dataset, $year, $gender = 'Totalt', $groupId, $ageGroup, $filter)
    {
        $totals = $dataset->totals()
                    ->where('year', $year)
                    ->where('gender', $gender)
                    ->whereHas('group', function($query) use($groupId) {
                        $query->where('parent_id', $groupId);
                    })
                    ->with(['group', 'values'])
                    ->get();

       return $totals->map(function($total) use($filter, $ageGroup) {
            return [
                'id'    => $total->group->id,
                'name'  => $total->group->name,
                'value' => $total->values[$ageGroup-1]->value,
                'url'   => $filter->updateUrl(['group' => $total->group->id]),
            ];
        });
    }

    /**
     * Retrieve gender types
     * @param  [type] $dataset
     * @return [type]
     */
    private function gender($dataset, $year, $groupId, $ageGroup, $filter)
    {
        $totals = Total::where('dataset_id', $dataset->id)
                    ->where('group_id', $groupId)
                    ->where('gender', '!=', 'Totalt')
                    ->with('values')
                    ->groupBy('gender')
                    ->get();

        return $totals->map(function($total) use($ageGroup, $filter) {
            return [
                'id'     => StringHelper::slugify($total->gender),
                'gender' => $total->gender,
                'value'  => $total->values[$ageGroup-1]->value,
                'url'   => $filter->updateUrl(['gender' => $total->gender]),
            ];
        });
    }

    /**
     * Retrieve total columns and their values.
     * 
     * @param  [type] $dataset
     * @return \Illuminate\Support\Collection
     */
    private function totalColumns($dataset, $year, $gender, $groupId, $filter)
    {
        $totals = DB::table('totals')
            ->select('total_columns.id', 'total_columns.name', 'total_values.value')
            ->leftJoin('total_values', 'totals.id', 'total_values.total_id')
            ->leftJoin('total_columns', 'total_values.column_id', 'total_columns.id')
            ->where('totals.dataset_id', $dataset->id)
            ->where('totals.year', $year)
            ->where('totals.gender', $gender)
            ->where('total_columns.name', '!=', 'Antal')
            ->where('totals.group_id', $groupId)
            ->groupBy('total_values.column_id')
            ->get();

        return $totals->map(function($total) use($filter) {
            return [
                'id'   => $total->id,
                'name' => $total->name,
                'value' => $total->value,
                'url'   => $filter->updateUrl(['age_group' => $total->id]),
            ];
        });
    }

    private function yearlyTotals(Indicator $indicator, $gender, $groupId, $ageGroup, $filter)
    {
        $datasets = $indicator->datasets()
                    ->with(['totals' => function($query) use($gender, $groupId) {
                        $query->where('gender', $gender);
                        $query->where('group_id', $groupId);
                    }])
                    ->get();

        $yearlyTotals = $datasets->map(function($dataset) use($indicator, $ageGroup, $filter) {
            $totals = $dataset->totals->first();

            return [
                'year' => $totals->year,
                'value' => $totals->values[$ageGroup-1]->value,
                'url'   => $filter->updateUrl(['year' => $totals->year]),
            ];
        });
        
        return $yearlyTotals;
    }
}
