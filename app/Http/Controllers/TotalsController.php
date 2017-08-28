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
use App\University;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TotalsController extends Controller
{

    protected $indicatorConfig;

    protected $universitiesConfig;

    public function __construct()
    {
        $this->universitiesConfig = config('universities');
    }

    public function index(Request $request, Indicator $indicator)
    {
        $data = [];
        $this->indicatorConfig = isset(config('indicator')[$indicator->slug]) ? config('indicator')[$indicator->slug] : config('indicator')['default'];
        $universityDefaultId = $this->universitiesConfig['default']['id'];
        
        // Retrieve filter args
        $university = ! empty($request->university) ? $request->university : $universityDefaultId;
        $gender = ! empty($request->gender) ? $request->gender : 'Total';
        $groupInput = ! empty($request->group) ? $request->group : null;
        $groupTopParent = ! empty($request->group_top_parent) ? $request->group_top_parent : null;
        $age_group = ! empty($request->age_group) ? $request->age_group : TotalColumn::where('name', 'Total')->first()->id;
        $year = $request->year;

        $filters = [
            'university' => $university,
            'year'      => $year,
            'group'     => $request->group,
            'gender'    => $request->gender,
            'age_group' => $request->age_group,
        ];

        // dd($filters);

        $filter = new Filter($filters, $indicator, $year);
        // dd($filter->title());
        $data['indicator'] = $this->indicatorData($indicator, $filter, $year);
        
        // retrieve dataset id for current year
        $dataset = $this->dataset($indicator, $year);

        $universities = $this->universities($dataset, $year, $gender, $groupInput, $groupTopParent, $age_group, $filter);

        $groups = $this->groups($dataset, $university, $year, $gender, $groupInput, $groupTopParent, $age_group, $filter);

        $genders = $this->gender($dataset, $university, $year, $groupInput, $groupTopParent, $age_group, $filter);

        $totalColumns = $this->totalColumns($dataset, $university, $year, $gender, $groupInput, $groupTopParent, $filter);

        $yearlyTotals = $this->yearlyTotals($indicator, $university, $gender, $groupInput, $groupTopParent, $age_group, $filter);

        $totals = new TotalsFormatter();

        if ($university == $universityDefaultId) {
            $totals->addGroup([
                'column' => 'Lärosäten',
                'totals' => $universities,
            ]);
        }

        if (! $groups->isEmpty()) {
            $totals->addGroups($groups);
        }

        if (! $filter->all()->get('gender')) {
            if ($genders->isNotEmpty()) {
                $totals->addGroup([
                    'column' => 'Kön',
                    'totals' => $genders,
                ]);
            }
        }

        if (! $filter->all()->get('age_group')) {
            if ($totalColumns->isNotEmpty()) {
                $totals->addGroup([
                    'column' => 'Åldersgrupper',
                    'totals' => $totalColumns,
                ]);
            }
        }

        $totals->add('Tid', $yearlyTotals);

        $totalsData = $totals->get();

        $data = array_merge($data, $totalsData);

        // var_dump($data);
        return $data;
    }

    /**
     * Retrieves current dataset by indicator id and year.
     * @param  [type] $indicator
     * @param  [type] $year
     * @return [type]
     */
    private function dataset($indicator, $year)
    {
        $datasetId = Total::where('year', $year)->first()->dataset_id;
        // dd($indicator);
        return Dataset::where('indicator_id', $indicator->id)
                    ->where('id', $datasetId)
                    ->first();
    }

    /**
     * Formats indicator data.
     * 
     * @param  Illuminate\Support\Collection $indicator
     * @param  Object $dynamicTitle
     * @return array
     */
    private function indicatorData($indicator, $filter, $year)
    {
        return [
            'id'            => $indicator->id,
            'name'          => $filter->title(),
            'description'   => $indicator->description,
            'measurement'   => $indicator->measurement,
            'current_year'  => $year,
        ];
    }

    /**
     * Retrieve universities totals
     * @param  [type] $dataset
     * @return [type]
     */
    private function universities($dataset, $year, $gender, $groupId, $groupTopParent, $ageGroup, $filter)
    {
        $groupTopParent = $this->groupTopParent($groupId, $groupTopParent);

        $totals = Total::where('dataset_id', $dataset->id)
                    ->where('group_id', $groupId)
                    ->where('gender', $gender)
                    ->where('university_id', '!=', 1)
                    ->with('values')
                    ->with('university')
                    ->groupBy('university_id');

        if (! empty($groupTopParent)) {
            $totals = $totals->where('group_top_parent', $groupTopParent);
        }

        $totals = $totals->get();

        return $totals->map(function($total) use($ageGroup, $filter) {
            return [
                'id'     => $total->university->slug,
                'name'   => $total->university->name,
                'value'  => $total->values->keyBy('column_id')[$ageGroup]->value,
                'url'   => $filter->updateUrl(['university' => $total->university_id]),
            ];
        });
    }

    /**
     * Retrieve all groups including their total value
     * @param  [type] $dataset
     * @return [type]
     */
    private function groups($dataset, $university, $year, $gender, $groupId, $groupTopParent, $ageGroup, $filter)
    {
        $groupTopParent = $this->groupTopParent($groupId, $groupTopParent);

        $totals = $dataset->totals()
                    ->where('university_id', $university)
                    ->where('year', $year)
                    ->where('gender', $gender)
                    ->where('group_parent_id', $groupId)
                    ->whereHas('group')
                    ->with(['group.column', 'values.column']);

        if (! empty($groupTopParent)) {
            $totals = $totals->where('group_top_parent', $groupTopParent);
        }

        $totals = $totals->get();

        return $totals->map(function($total) {
            $total->group_column = $total->group->column->name;
            $total->top_parent_id = $total->group->column->top_parent_id;
            return $total;
        })->groupBy('group_column')
        ->map(function($total, $groupColumn) use($filter, $ageGroup) {

            $allTotals = $total->map(function($item) use($filter, $ageGroup) {
                return [
                    'id'    => $item->group->id,
                    'name'  => $item->group->name,
                    'value' => $item->values->keyBy('column_id')[$ageGroup]->value,
                    'url'   => $filter->updateUrl([
                        'group' => $item->group->id,
                        'group_top_parent' => $item->group_top_parent,
                    ]),
                ];
            });

            return [
                'column' => $groupColumn,
                'top_parent_id' => $total->first()->top_parent_id,
                'totals' => $allTotals->toArray(),
            ];
        });
    }

    /**
     * Retrieve gender types
     * @param  [type] $dataset
     * @return [type]
     */
    private function gender($dataset, $university, $year, $groupId, $groupTopParent, $ageGroup, $filter)
    {
        $groupTopParent = $this->groupTopParent($groupId, $groupTopParent);

        $totals = Total::where('dataset_id', $dataset->id)
                    ->where('group_id', $groupId)
                    ->where('gender', '!=', 'Total')
                    ->where('university_id', $university)
                    ->with('values')
                    ->groupBy('gender');

        if (! empty($groupTopParent)) {
            $totals = $totals->where('group_top_parent', $groupTopParent);
        }

        $totals = $totals->get();

        return $totals->map(function($total) use($ageGroup, $filter) {
            return [
                'id'     => StringHelper::slugify($total->gender),
                'gender' => $total->gender,
                'value'  => $total->values->keyBy('column_id')[$ageGroup]->value,
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
    private function totalColumns($dataset, $university, $year, $gender, $groupId, $groupTopParent, $filter)
    {
        $groupTopParent = $this->groupTopParent($groupId, $groupTopParent);

        $totals = DB::table('totals')
            ->select('total_columns.id', 'total_columns.name', 'total_values.value')
            ->leftJoin('total_values', 'totals.id', 'total_values.total_id')
            ->leftJoin('total_columns', 'total_values.column_id', 'total_columns.id')
            ->where('totals.dataset_id', $dataset->id)
            ->where('totals.year', $year)
            ->where('totals.university_id', $university)
            ->where('totals.gender', $gender)
            ->where('total_columns.name', '!=', 'Total')
            ->where('totals.group_id', $groupId)
            ->groupBy('total_values.column_id');

        if (! empty($groupTopParent)) {
            $totals = $totals->where('group_top_parent', $groupTopParent);
        }

        $totals = $totals->get();

        return $totals->map(function($total) use($filter) {
            return [
                'id'   => $total->id,
                'name' => $total->name,
                'value' => $total->value,
                'url'   => $filter->updateUrl(['age_group' => $total->id]),
            ];
        });
    }

    /**
     * Set value to null if group is empty since we don't need to query groupTopParent if group
     * hasn't been selected.
     * 
     * @param  [type] $group
     * @param  [type] $groupTopParent
     * @return [type]
     */
    private function groupTopParent($group, $groupTopParent)
    {
        if (empty($group)) {
            return null;
        }

        return $groupTopParent;
    }

    private function yearlyTotals(Indicator $indicator, $university, $gender, $groupId, $groupTopParent, $ageGroup, $filter)
    {
        $groupTopParent = $this->groupTopParent($groupId, $groupTopParent);

        $totals = Total::whereHas('dataset', function($query) use($indicator) {
                        $query->where('indicator_id', $indicator->id);
                    })
                    ->where('gender', $gender)
                    ->where('group_id', $groupId)
                    ->where('university_id', $university)
                    ->orderBy('year');

        $totals = $totals->where('group_top_parent', $groupTopParent);

        $totals = $totals->get();

        $yearlyTotals = $totals->map(function($total) use($indicator, $ageGroup, $filter) {

            return [
                'year' => $total->year,
                'value' => $total->values->keyBy('column_id')[$ageGroup]->value,
                'url'   => $filter->updateUrl(['year' => $total->year]),
            ];
        });

        return $yearlyTotals;
    }
}
