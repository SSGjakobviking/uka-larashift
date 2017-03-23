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

    protected $config;

    public function index(Request $request, Indicator $indicator)
    {
        $data = [];
        $this->config = config('indicator')[$indicator->slug];

        // Retrieve filter args
        $university = ! empty($request->university) ? $request->university : 1;
        $term = ! empty($request->term) ? $request->term : 'VT';
        $gender = ! empty($request->gender) ? $request->gender : 'Total';
        $groupInput = ! empty($request->group) ? $request->group : null;
        $age_group = ! empty($request->age_group) ? $request->age_group : 4;
        $year = $request->year;

        $filters = [
            'university' => $university,
            'term'      => $term,
            'year'      => $year,
            'group'     => $request->group,
            'gender'    => $request->gender,
            'age_group' => $request->age_group,
        ];

        $filter = new Filter($filters, $indicator, $year);

        $filterUrl = $filter->url();

        $dynamicTitle = new DynamicTitle($indicator, $filter);

        $data['indicator'] = $this->indicatorData($indicator, $dynamicTitle, $year, $term);
        
        // retrieve dataset id for current year
        $datasetId = Total::where('year', $year)->get()->first()->dataset_id;

        $dataset = Dataset::where('indicator_id', $indicator->id)
                    ->where('id', $datasetId)
                    ->get()->first();

        $groupColumn = Group::where('parent_id', $groupInput)->get();
        
        if (! $groupColumn->isEmpty()) {
            $groupColumn = $groupColumn->first()->column->name;
            $groupColumn = $this->config['group_columns'][StringHelper::slugify($groupColumn)];
        }

        $universities = $this->universities($dataset, $year, $term, $gender, $groupInput, $age_group, $filter);

        $groups = $this->groups($dataset, $university, $year, $term, $gender, $groupInput, $age_group, $filter);

        $genders = $this->gender($dataset, $university, $year, $term, $groupInput, $age_group, $filter);

        $totalColumns = $this->totalColumns($dataset, $university, $year, $term, $gender, $groupInput, $filter);

        $yearlyTotals = $this->yearlyTotals($indicator, $university, $gender, $groupInput, $age_group, $filter);

        $totals = new TotalsFormatter();

        if ($university == 1) {
            $totals->addGroup('Lärosäten', $universities);
        }

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
    private function indicatorData($indicator, $dynamicTitle, $year, $term)
    {
        return [
            'id'            => $indicator->id,
            'name'          => $dynamicTitle->get(),
            'description'   => $indicator->description,
            'measurement'   => $indicator->measurement,
            'current_year'  => $this->yearSuffix($year, $term),
            'current_term'  => $term,
        ];
    }

    /**
     * Retrieve universities totals
     * @param  [type] $dataset
     * @return [type]
     */
    private function universities($dataset, $year, $term, $gender, $groupId, $ageGroup, $filter)
    {
        $totals = Total::where('dataset_id', $dataset->id)
                    ->where('group_id', $groupId)
                    ->where('term', $term)
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
    private function groups($dataset, $university, $year, $term, $gender, $groupId, $ageGroup, $filter)
    {
        $totals = $dataset->totals()
                    ->where('university_id', $university)
                    ->where('year', $year)
                    ->where('gender', $gender)
                    ->where('term', $term)
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
    private function gender($dataset, $university, $year, $term, $groupId, $ageGroup, $filter)
    {
        $totals = Total::where('dataset_id', $dataset->id)
                    ->where('group_id', $groupId)
                    ->where('gender', '!=', 'Total')
                    ->where('university_id', $university)
                    ->where('term', $term)
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
    private function totalColumns($dataset, $university, $year, $term, $gender, $groupId, $filter)
    {
        $totals = DB::table('totals')
            ->select('total_columns.id', 'total_columns.name', 'total_values.value')
            ->leftJoin('total_values', 'totals.id', 'total_values.total_id')
            ->leftJoin('total_columns', 'total_values.column_id', 'total_columns.id')
            ->where('totals.dataset_id', $dataset->id)
            ->where('totals.year', $year)
            ->where('totals.university_id', $university)
            ->where('term', $term)
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
        $datasets = $indicator->datasets()
                    ->with(['totals' => function($query) use($university, $gender, $groupId) {
                        $query->where('gender', $gender);
                        $query->where('group_id', $groupId);
                        $query->where('university_id', $university);
                    }])
                    ->get();

        $totals = $datasets->pluck('totals')->flatten();
        $yearlyTotals = $totals->map(function($total) use($indicator, $ageGroup, $filter) {

            $year = $this->yearSuffix($total->year, $total->term);

            return [
                'year' => $total->year,
                'prefix' => $total->term,
                'value' => $total->values[$ageGroup-1]->value,
                'url'   => $filter->updateUrl(['year' => $total->year, 'term' => $total->term]),
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
        return $year . $this->config['term']['date_suffix'][$term];
    }
}
