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
use Box\Spout\Common\Type;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Writer\WriterFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $export = ! empty($request->export) ? $request->export : null;
        $exportType = ! empty($request->export_type) ? $request->export_type : null;
        $status = ! empty($request->status) ? $request->status : 'published';

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
        $dataset = $this->dataset($indicator, $year, $status);
        
        if (is_null($dataset)) {
            return response()->json([
                'error' => 'No dataset for this indicator ID: ' . $indicator->id . ' and year ' . $year,
            ]);
        }

        $universities = $this->universities($dataset, $year, $gender, $groupInput, $groupTopParent, $age_group, $filter);

        $groups = $this->groups($dataset, $university, $year, $gender, $groupInput, $groupTopParent, $age_group, $filter);

        $genders = $this->gender($dataset, $university, $year, $groupInput, $groupTopParent, $age_group, $filter);

        $totalColumns = $this->totalColumns($dataset, $university, $year, $gender, $groupInput, $groupTopParent, $filter);

        $yearlyTotals = $this->yearlyTotals($indicator, $university, $gender, $groupInput, $groupTopParent, $age_group, $status, $filter);

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

        // check if export was requested.
        if ($export) {
            // return csv data for the whole year
            if ($export === 'all') {
                $filePath = null;

                if ($exportType === 'xlsx') {
                    $filePath = $this->convertToExcel($dataset->file, 'uploads');
                }

                if ($exportType === 'csv') {
                    $filePath = asset('uploads/' . $dataset->file);
                }

                return response()
                    ->json(['url' => $filePath]);
            }

            // create csv file and return data for the current api request.
            if ($export === 'current') {
                $filePath = $this->jsonToCsv($data, $indicator->slug);

                if ($exportType === 'xlsx') {
                    $filePath = str_replace(url('downloads') . '/', '', $filePath);
                    $filePath = $this->convertToExcel($filePath, 'downloads');
                }

                return response()->json(['url' => $filePath]);
            }
        }

        return $data;
    }

    /**
     * Convert csv to excel.
     * 
     * @param  [type] $csvFile
     * @param  [type] $folder
     * @return [type]
     */
    public function convertToExcel($csvFile, $folder)
    {
        $excelFile = head(explode('.', $csvFile)) . '.xlsx';
        $relativePath = public_path('downloads/' . $excelFile);
        $filePath = asset('downloads/' . $excelFile);

        // return file if already exist
        if (file_exists($relativePath)) {
            return $filePath;
        }

        $reader = ReaderFactory::create(Type::CSV); // for CSV files
        $reader->setFieldDelimiter(';');
        $writer = WriterFactory::create(Type::XLSX); // for XLSX files

        $reader->open(public_path($folder . '/' . $csvFile));
        $writer->openToFile($relativePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                // do stuff with the row  
                $writer->addRow($row);
            }
        }

        $writer->close();
        $reader->close();

        return $filePath;
    }

    /**
     * Create csv file out ouf current api request.
     * 
     * @param  [type] $data
     * @param  [type] $fileName
     * @return [type]
     */
    private function jsonToCsv($data, $fileName)
    {
        $header = [
            'År',
            'Indikator',
        ];

        $content = [];

        $groups = collect($data['groups']);
        $grouped = $groups->pluck('column')->flatMap(function($item) {
            return [
                $item,
                'Värde['.$item.']',
            ];
        });
        $headers = array_merge($header, $grouped->toArray());

        $rows = collect([]);

        $content = $groups->map(function($item) {
            return $item;
        });

        $content->map(function($column) use (&$rows, $headers, $data) {
            $res = collect($column['totals'])->map(function($item, $key) use (&$rows, $headers, $column, $data) {
                $group = $rows->get($key, collect([]));

                if ($group->isEmpty()) {
                    foreach ($headers as $header) {
                        if ($header === 'År') {
                            $group->put($header, $data['indicator']['current_year']);
                        } else if ($header === 'Indikator') {
                            $group->put($header, $data['indicator']['measurement']);
                        } else {
                            $group->put($header, null);
                        }

                        $rows->put($key, $group);
                    }
                }

                $nameField = isset($item['name']) ? 'name' : 'gender';

                $group->put($column['column'], $item[$nameField]);
                $group->put('Värde['.$column['column'].']', $item['value']);
                $rows->put($key, $group);

                return [$item[$nameField], $item['value']];
            });

            return $res;
        });

        $rows = $rows->map(function ($row) {
            return $row->values();
        });

        $headers = array_map(function ($header) {
            return preg_replace('/\[(.*)\]$/', null, $header);
        }, $headers);

        // add headers to the first line.
        $output = $rows->prepend($headers);
        $fileName = uniqid() . '-' . $fileName . '.csv';
        $filePath = public_path('downloads/' . $fileName);
        $fp = fopen($filePath, 'w');

        $output->each(function($fields) use($fp) {
            $fields = $fields instanceof Collection ? $fields->toArray() : $fields;
            // dump($fields);
            fputcsv($fp, $fields, ';');
        });

        return asset('downloads/' . $fileName);
    }

    /**
     * Retrieves current dataset by indicator id and year.
     * 
     * @param  [type] $indicator
     * @param  [type] $year
     * @return [type]
     */
    private function dataset($indicator, $year, $status)
    {
        return $indicator->datasets()
                ->where('year', $year)
                ->whereHas('statuses', function($query) use($status) {
                    $query->where('name', $status);
                })
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

        return $totals->filter(function($total) use($ageGroup) {
            if (isset($total->values->keyBy('column_id')[$ageGroup])) {
                return true;
            } else {
                return false;
            }
        })->map(function($total) use($ageGroup, $filter) {
            return [
                'id'     => $total->university->slug,
                'name'   => $total->university->name,
                'value'  => $total->values->keyBy('column_id')[$ageGroup]->value,
                'url'   => $filter->updateUrl(['university' => $total->university_id]),
            ];
        })->values();
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

    private function yearlyTotals(Indicator $indicator, $university, $gender, $groupId, $groupTopParent, $ageGroup, $status, $filter)
    {
        $groupTopParent = $this->groupTopParent($groupId, $groupTopParent);

        $totals = Total::whereHas('dataset', function($query) use($indicator, $status) {
                        $query->where('indicator_id', $indicator->id);

                        $query->whereHas('statuses', function($query) use($status) {
                            $query->status($status);
                        });
                    })
                    ->where('gender', $gender)
                    ->where('group_id', $groupId)
                    ->where('university_id', $university)
                    ->orderBy('year');

        $totals = $totals->where('group_top_parent', $groupTopParent);

        $totals = $totals->get();

        $yearlyTotals = $totals->map(function($total) use($indicator, $ageGroup, $filter) {

            return [
                'year' => trim($total->year),
                'value' => $total->values->keyBy('column_id')[$ageGroup]->value,
                'url'   => $filter->updateUrl(['year' => trim($total->year)]),
            ];
        });

        // code below sorts ht/vt years in the following order: HT2010, VT2010, HT2011, VT2011
        $checkForHtVt = ['ht', 'vt'];

        if (in_array(strtolower(substr($yearlyTotals->first()['year'], 0, 2)), $checkForHtVt)) {
            $yearlyTotals = $yearlyTotals->groupBy(function($item) { // sorts the year by ht/vt if exists in year format
                return substr($item['year'], 2, 4);
            })->collapse();
        }

        return $yearlyTotals;
    }
}
