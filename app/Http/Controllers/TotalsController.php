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
        $this->indicatorConfig = config('indicator')[$indicator->slug];
        $universityDefaultId = $this->universitiesConfig['default']['id'];
        
        // Retrieve filter args
        $university = ! empty($request->university) ? $request->university : $universityDefaultId;
        $gender = ! empty($request->gender) ? $request->gender : 'Total';
        $groupInput = ! empty($request->group) ? $request->group : null;
        $age_group = ! empty($request->age_group) ? $request->age_group : 4;
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

        $groupColumn = $this->groupColumn($groupInput);

        $universities = $this->universities($dataset, $year, $gender, $groupInput, $age_group, $filter);

        $groups = $this->groups($dataset, $university, $year, $gender, $groupInput, $age_group, $filter);

        $genders = $this->gender($dataset, $university, $year, $groupInput, $age_group, $filter);

        $totalColumns = $this->totalColumns($dataset, $university, $year, $gender, $groupInput, $filter);

        $yearlyTotals = $this->yearlyTotals($indicator, $university, $gender, $groupInput, $age_group, $filter);

        $totals = new TotalsFormatter();

        if ($university == $universityDefaultId) {
            $totals->addGroup('Lärosäten', $universities);
        }

        if (! $groups->isEmpty()) {
            $totals->addGroup($groupColumn, $groups);
        }

        if (! $filter->all()->get('gender')) {
            $totals->addGroup('Kön', $genders);
        }

        if (! $filter->all()->get('age_group')) {
            $totals->addGroup('Åldersgrupper', $totalColumns);
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

        return Dataset::where('indicator_id', $indicator->id)
                    ->where('id', $datasetId)
                    ->first();
    }

    /**
     * Retrieves current group name
     * @param  integer|null $groupInput
     * @return string
     */
    private function groupColumn($groupInput)
    {
        $groupColumn = Group::where('parent_id', $groupInput)->get();
        
        if (! $groupColumn->isEmpty()) {
            $groupColumn = $groupColumn->first()->column->name;
            $groupColumn = $this->indicatorConfig['group_columns'][StringHelper::slugify($groupColumn)];
        }

        return $groupColumn;
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
    private function universities($dataset, $year, $gender, $groupId, $ageGroup, $filter)
    {
        $totals = Total::where('dataset_id', $dataset->id)
                    ->where('group_id', $groupId)
                    ->where('gender', $gender)
                    ->where('university_id', '!=', 1)
                    ->with('values')
                    ->with('university')
                    ->groupBy('university_id')
                    ->get();

        return $totals->map(function($total) use($ageGroup, $filter) {
            return [
                'id'     => $total->university->slug,
                'name'   => $total->university->name,
                'value'  => $total->values[$ageGroup-1]->value,
                'url'   => $filter->updateUrl(['university' => $total->university_id]),
            ];
        });
    }

    /**
     * Retrieve all groups including their total value
     * @param  [type] $dataset
     * @return [type]
     */
    private function groups($dataset, $university, $year, $gender, $groupId, $ageGroup, $filter)
    {
        $totals = $dataset->totals()
                    ->where('university_id', $university)
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
    private function gender($dataset, $university, $year, $groupId, $ageGroup, $filter)
    {
        $totals = Total::where('dataset_id', $dataset->id)
                    ->where('group_id', $groupId)
                    ->where('gender', '!=', 'Total')
                    ->where('university_id', $university)
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
    private function totalColumns($dataset, $university, $year, $gender, $groupId, $filter)
    {
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

    private function yearlyTotals(Indicator $indicator, $university, $gender, $groupId, $ageGroup, $filter)
    {
        $totals = Total::with(['dataset' => function($query) use($indicator) {
                        $query->where('indicator_id', $indicator->id);
                    }])
                    ->where('gender', $gender)
                    ->where('group_id', $groupId)
                    ->where('university_id', $university)
                    ->orderBy('year')
                    ->orderBy('term', 'desc')
                    ->get();

        $yearlyTotals = $totals->map(function($total) use($indicator, $ageGroup, $filter) {

            return [
                'year' => $total->year,
                'value' => $total->values[$ageGroup-1]->value,
                'url'   => $filter->updateUrl(['year' => $total->year]),
            ];
        });

        return $yearlyTotals;
    }

    /**
     * Return year suffix for the specific year + term.
     * 
     * @param  integer $year
     * @param  string $term
     * @return string
     */
    private function yearSuffix($year, $term)
    {
        return $year . $this->indicatorConfig['term']['date_suffix'][$term];
    }
}
