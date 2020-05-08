<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\DynamicTitle;
use App\Filter;
use App\Group;
use App\GroupColumn;
use App\Helpers\DatasetHelper;
use App\Helpers\StringHelper;
use App\Helpers\UrlHelper;
use App\Indicator;
use App\Total;
use App\TotalColumn;
use App\TotalValue;
use App\TotalsFormatter;
use App\University;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;
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
        $groupSlug = ! empty($request->group_slug) ? $request->group_slug : null;
        $age_group = ! empty($request->age_group) ? $request->age_group : TotalColumn::where('name', 'Total')->first()->id;
        $export = ! empty($request->export) ? $request->export : null;
        $exportType = ! empty($request->export_type) ? $request->export_type : null;
        $status = ! empty($request->status) ? $request->status : 'published';
        $year = ! empty($request->year) ? $request->year : null;

        try {
            $year = $this->setYear($indicator, $request);
        } catch(\Exception $e) {
            return ['error' => $e->getMessage()];
        }
       
        // set year to last published year if no year has been specified and indicator contains a dataset

        $filters = [
            'university' => $university,
            'year'       => $year,
            'group_slug' => $groupSlug,
            'gender'     => $request->gender,
            'age_group'  => $request->age_group,
        ];

        // dd($filters);
    

        $filter = new Filter($filters, $indicator, $year);
        
        //dd($filter->title());
       
        $data['indicator'] = $this->indicatorData($indicator, $filter, $year);
        //dd($data);
        
        // retrieve dataset id for current year
        $dataset = $this->dataset($indicator, $year, $status);
        
        if (is_null($dataset)) {
            return response()->json([
                'error' => 'No dataset for indicator ID: ' . $indicator->id . ' and year ' . $year,
            ]);
        }

        $universities = $this->universities($dataset, $year, $gender, $groupSlug, $age_group, $filter);
        
        $groups = $this->groups($dataset, $university, $year, $gender, $groupSlug, $age_group, $filter);
        
        $genders = $this->gender($dataset, $university, $year, $groupSlug, $age_group, $filter);
        
        $totalColumns = $this->totalColumns($dataset, $university, $year, $gender, $groupSlug, $filter);
        
        $yearlyTotals = $this->yearlyTotals($indicator, $university, $gender, $groupSlug, $age_group, $status, $filter);
        
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
       
        // dd($data);
        // check if export was requested.
        if ($export) {
            
            // return csv data for year/term
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
        // Log::info('Filepath: ' . $relativePath);
        $filePath = asset('downloads/' . $excelFile);

        // return file if already exist
        if (file_exists($relativePath)) {
            return $filePath;
        }

        $reader = ReaderFactory::create(Type::CSV); // for CSV files
        $reader->setEncoding('UTF-8');
        $reader->setFieldDelimiter(';');
        $writer = WriterFactory::create(Type::XLSX); // for XLSX files
        
        $writer->setShouldUseInlineStrings(false);
        $reader->open(public_path($folder . '/' . $csvFile));
        $writer->openToFile($relativePath);
        foreach ($reader->getSheetIterator() as $sheet) {
            $ageIndex = 1000; // Setting a default value to variable. 
            foreach ($sheet->getRowIterator() as $row) {
                
                $intRow = array();
                foreach ($row as $position => $cell) {

                    // Check if column is "Åldergrupp" then automatic push as text to array
                    if ($cell === "Åldersgrupper" || $cell === "Åldersgrupp") {
                        $ageIndex = $position;
                    }
                    // Picking out array index where index is age and treating it as text.
                    if ($position == $ageIndex) {
                        // Log::info('Åldersindex: ' . $cell);
                        array_push($intRow, $cell);
                    } else {
                        // Log::info('Övrig info: ' . $cell);
                        if ( preg_match("/^[0-9,.]+$/", $cell) ) {
                            $toDot = str_replace(',', '.', $cell);
                            array_push($intRow, (float)$toDot);
                        } else {
                            array_push($intRow, $cell);
                        }
                    }
                    // Log::info('cell: ' . print_r($cell, true));
                    // Log::info('Type: ' . gettype($cell));
                }
                // do stuff with the row  
                $writer->addRow($intRow);
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

        if (isset($data['groups'])) {
            $groups = collect($data['groups']);
        } else {
            $yearlyTotals = collect([$data['yearly_totals']]);
            $currentYearData = $this->extractCurrentYearData($yearlyTotals, request());

            $groups = collect([]);
            $currentYearData = collect($yearlyTotals->first())
                    ->put('totals', $currentYearData);

            $groups->push($currentYearData);
        }

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

                if (isset($item['name'])) {
                  $nameField = 'name';
                } elseif (isset($item['gender'])) {
                  $nameField = 'gender';
                } elseif (isset($item['year'])) { // this will be set when all groups has been filtrered and yearly totals is used as data.
                  $nameField = 'year';
                }

                $group->put($column['column'], $item[$nameField]);
                // Replaces dots with commas when written to from api to file
                $convertDecimals = str_replace(',', '.', $item['value']);
                $group->put('Värde['.$column['column'].']', $convertDecimals);
                $rows->put($key, $group);
                return [$item[$nameField], $convertDecimals];
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

        // special case. If all filters are active, we only want to return the year, value and indicator. Since we're using the data from
        // yearly_totals function, we get two 'year' columns (year and Tid). So we remove the 'Tid' duplicate year column.
        if (collect($output->first())->contains('Tid')) {
            $output = $this->removeColumn($output, 'Tid');
        }

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
     * Remove column + value from the output array.
     * 
     * @param  Illuminiate\Support\Collection $data
     * @param  string $column
     * @return Illuminiate\Support\Collection
     */
    private function removeColumn($data, $column) {
        $skipIndex = collect($data->first())->search($column);

        return $data->map(function($item) use($skipIndex) {
                        return collect($item)->filter(function($value, $key) use($skipIndex) {
                            return $key !== $skipIndex;
                        })->toArray();
                    });
    }

    /**
     * Extracts data by the current year.
     * 
     * @param  Illuminate\Support\Collection $data
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Support\Collection
     */
    private function extractCurrentYearData($data, $request)
    {
        return collect($data->first())
                ->get('totals')
                ->filter(function($item) use($request) {
                    return $item['year'] === $request->year;
                })->values();
    }

    private function setYear($indicator, $request)
    {
        if (empty($request->year)) {
            $lastPublishedDataset = DatasetHelper::lastPublishedDataset($indicator);
            if ($lastPublishedDataset) {
                return $lastPublishedDataset->year;
            } else {
                throw new \Exception('Indicator has no datasets.');
            }
        }

        return $request->year;
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
        //dd($filter);
        $name = $filter->title();
        if(is_null($name)) {
            $name = "";
        }
        return [
            'id'            => $indicator->id,
            'name'          => $filter->title(),
            'description'   => $indicator->description,
            'measurement'   => $indicator->name,
            'current_year'  => $year,
        ];
    }

    /**
     * Retrieve universities totals
     * @param  [type] $dataset
     * @return [type]
     */
    private function universities($dataset, $year, $gender, $groupSlug, $ageGroup, $filter)
    {
        $totals = Total::where('dataset_id', $dataset->id)
                    ->where('group_slug', $groupSlug)
                    ->where('gender', $gender)
                    ->where('university_id', '!=', 1)
                    ->with('values')
                    ->with('university')
                    ->groupBy('university_id');

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
                'value'  => isset($total->values->keyBy('column_id')[$ageGroup]) ? $total->values->keyBy('column_id')[$ageGroup]->value : 0,
                'url'   => $filter->updateUrl(['university' => $total->university_id]),
            ];
        })->values();
    }

    /**
     * Retrieve all groups including their total value
     * @param  [type] $dataset
     * @return [type]
     */
    private function groups($dataset, $university, $year, $gender, $groupSlug, $ageGroup, $filter)
    {
        // this is the default query that is being used when there are 1 top level group
        $totals = $dataset->totals()
                    ->where('university_id', $university)
                    ->where('year', $year)
                    ->where('gender', $gender)
                    ->where('group_parent_slug', $groupSlug)
                    ->whereHas('group')
                    ->with(['group.column', 'values.column'])
                    ->whereHas('values', function($query) use($ageGroup) {
                        $query->where('column_id', $ageGroup);
                    })
                    ->get();

        $parentColumnData = null;
        $currentGroup = Total::where('group_slug', $groupSlug)->first();
        $topGroupId = $currentGroup->top_group_id;

        // dd($totals->first()->top_group_id);
        // code below is intended for querying the top level group while viewing the second level group.
        // Example hiearchy is this: Studieform | Ämnesområde[0] | Ämnesdelsområde[1] | Ämnesgrupp[2]
        // In the GUI, both Studieform and Ämnesområde[0] will show in the sidebar acting as "top-level groups".
        // But Ämnesområde[0] is also a child of Stuedieform, that's why we run the query below while filtering on the
        // "top-level Ämnesområde[0] column".
        if ($groupSlug && is_null($currentGroup->top_group_id)) {
            $groupId = \DB::table('totals')->select('group_id')->where('group_slug', $groupSlug)->take(1);

            // parentColumn in this case is "Studieform" if we're handling an indicator with
            // the following structure: Studieform | Ämnesområde[0] | Ämnesdelsområde[1] | Ämnesgrupp[2]
            $parentColumn = Total::where('dataset_id', $dataset->id)
                            ->select([
                                'group_columns.name AS column_name', 
                                'groups.name AS group_name',
                                'totals.*', 
                                'total_values.value'
                            ])
                            ->leftJoin('groups', 'totals.top_group_id', '=', 'groups.id')
                            ->leftJoin('group_columns', 'groups.column_id', '=', 'group_columns.id')
                            ->leftJoin('total_values', 'totals.id', '=', 'total_values.total_id')
                            ->where('gender', $gender)
                            ->where('total_values.column_id', $ageGroup)
                            ->where('university_id', $university)
                            ->where('year', $year)
                            ->whereIn('group_id', function ($query) use ($groupSlug) {
                                $query->select('group_id')
                                      ->from('totals')
                                      ->where('group_slug', $groupSlug)
                                      ->groupBy('group_id');
                            })
                            ->whereNotNull('group_columns.name')
                            ->get();

            $parentColumnData = $parentColumn->groupBy('column_name')->map(function($items, $column) use($filter) {
                // if (! is_null($items->first()->top_group_id)) {
                //     return;
                // }

                $totals = $items->map(function($item) use($filter) {
                    return [
                        'id' => $item->group_slug,
                        'name' => $item->group_name,
                        'value' => $item->value,
                        'url'   => $filter->updateUrl([
                            'group_slug' => $item->group_slug,
                        ]),
                    ];
                });

                return  [
                    'column' => $column,
                    'top_parent_id' => null,
                    'totals' => $totals->toArray(),
                ];
            });
            // dd($parentColumnData);
        }

        return $totals->map(function($total) {
            $total->group_column = $total->group->column->name;
            $total->top_parent_id = $total->group->column->top_parent_id;
            return $total;
        })->groupBy('group_column')
        ->map(function($total, $groupColumn) use($filter, $ageGroup) {

            $allTotals = $total->map(function($item) use($filter, $ageGroup) {
                return [
                    'id'    => $item->group_slug,
                    'name'  => $item->group->name,
                    'value' => isset($item->values->keyBy('column_id')[$ageGroup]) ? $item->values->keyBy('column_id')[$ageGroup]->value : 0,
                    'url'   => $filter->updateUrl([
                        'group_slug' => $item->group_slug,
                    ]),
                ];
            });

            return [
                'column' => $groupColumn,
                'top_parent_id' => $total->first()->top_parent_id,
                'totals' => $allTotals->toArray(),
            ];
        // this code below will only be used with nested groups where the second level also acts as top level group in the frontend.
        })->when($parentColumnData, function($collection) use($parentColumnData, $groupSlug, $totals) {
            // 
            if (! empty($groupSlug)) {
                $topGroupId = null;
                // return empty if reached
                if (! $totals->isEmpty()) {
                    $topGroupId = $totals->first()->top_group_id;
                }

                // dd($collection);
                // dd($parentColumnData);
                // this will be true when filtering in the absolute top group. 
                // Studieform | Ämnesområde[0] | Ämnesdelsområde[1] | Ämnesgrupp[2]
                // When filtering on studieform we will return the 'regular' collection below
                if (! is_null($topGroupId)) {
                    return $collection;
                }

                // if (! is_null($topGroupId)) {
                //     $topGroupColumn = Group::find($topGroupId)->column;
                //     // Here we return the child of the
                //     if (is_null($topGroupColumn->top_parent_id)) {
                //         return $collection;
                //     }
                // }
            }

            if (! $collection->isEmpty()) {
                return $collection->merge($parentColumnData);
            }

            return $parentColumnData;
        });
    }

    /**
     * Retrieve gender types
     * @param  [type] $dataset
     * @return [type]
     */
    private function gender($dataset, $university, $year, $groupSlug, $ageGroup, $filter)
    {
        $totals = Total::where('dataset_id', $dataset->id)
                    ->where('group_slug', $groupSlug)
                    ->where('gender', '!=', 'Total')
                    ->where('university_id', $university)
                    ->with('values')
                    ->groupBy('gender')
                    ->get();

        return $totals->map(function($total) use($ageGroup, $filter) {
            return [
                'id'     => StringHelper::slugify($total->gender),
                'gender' => $total->gender,
                'value'  => isset($total->values->keyBy('column_id')[$ageGroup]) ? $total->values->keyBy('column_id')[$ageGroup]->value : 0,
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
    private function totalColumns($dataset, $university, $year, $gender, $groupSlug, $filter)
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
            ->where('totals.group_slug', $groupSlug)
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

    private function yearlyTotals(Indicator $indicator, $university, $gender, $groupSlug, $ageGroup, $status, $filter)
    {
        $totals = Total::whereHas('dataset', function($query) use($indicator, $status, $groupSlug) {
                        $query->where('indicator_id', $indicator->id);

                        $query->whereHas('statuses', function($query) use($status) {
                            $query->status($status);
                        });
                    })
                    ->where('group_slug', $groupSlug)
                    ->where('gender', $gender)
                    ->where('university_id', $university)
                    ->groupBy('year')
                    ->orderBy('year')
                    ->get();

        $yearlyTotals = $totals->map(function($total) use($indicator, $ageGroup, $filter) {
            return [
                'year' => trim($total->year),
                'value' => isset($total->values->keyBy('column_id')[$ageGroup]) ? $total->values->keyBy('column_id')[$ageGroup]->value : 0,
                'url'   => $filter->updateUrl(['year' => trim($total->year)]),
            ];
        });

        // code below sorts vt/ht years in the following order: VT2010, HT2010, VT2011, HT2011
        if (in_array(substr($yearlyTotals->first()['year'], 0, 2), ['HT', 'VT'])) {
            $yearlyTotals = $yearlyTotals->groupBy(function($item) { // sorts the year by ht/vt if exists in year format
                return substr($item['year'], 2, 4);
            })->pipe(function($collection) { // order by substr year index key
                $array = $collection->toArray();
                ksort($array);
                return collect($array);
            })->map(function($item) { // sort inside specific year (HT/VT)
                $item = collect($item);
                return $item->sortByDesc('year')->values();
            })->collapse();
        }

        return $yearlyTotals;
    }
}
