<?php

namespace App;

use App\Dataset;
use App\Helpers\StringHelper;
use Illuminate\Support\Facades\Storage;

class DatasetImporter
{

    protected $file;

    protected $data;

    protected $indicatorSlug;

    protected $groupColumns;

    protected $totalColumns;

    protected $totalColumnIds;

    public function __construct($file, $indicator)
    {
        $this->file = $file;
        $this->indicator = $indicator;

        $this->parse($file);
    }

    private function parse($file)
    {
        $csv = \League\Csv\Reader::createFromPath($file);
        $csvData = collect($csv->setOffset(1)->fetchAll());
        $headers = $csvData[0];

        $data = [
            'id'            => 1,
            'title'         => 'Antal registrerade studenter',
            'description'   => 'Mäter antalet registrerade studenter per år.',
            'slug'          => 'registrerade-studenter',
            'measurement'   => 'Antal registrerade studenter.',
            'time_unit'     => 'År',
            'age_groups'    => ['Antal', '-21 år', '22-24 år', '25-29 år', '30-34 år', '35- år'],
            'version'       => '1',
            'year_totals'   => [
                2003 => 23292,
                2004 => 43292,
                2005 => 53292,
                2006 => 132923,
                2007 => 33292,
            ],
        ];

        // dd($headers);
        // dd($csvData);
        
        $genderTitleSlug= StringHelper::slugify($headers[1]);
        $subjectAreaTitleSlug= StringHelper::slugify($headers[2]);
        $subjectSubAreaTitleSlug= StringHelper::slugify($headers[3]);
        $subjectGroupTitleSlug= StringHelper::slugify($headers[4]);
        $dataset = [];

        $ignoreHeaders = $data['age_groups'];

        foreach($csvData as $k => $line) {
            $gender = $line[1];
            $genderSlug = StringHelper::slugify($line[1]);

            $subjectArea = $line[2];
            $subjectAreaSlug = StringHelper::slugify($line[2]);

            $subjectSubArea = $line[3];
            $subjectSubAreaSlug = StringHelper::slugify($line[3]);
            
            $subjectGroup = $line[4];
            $subjectGroupSlug = StringHelper::slugify($line[4]);

            $currentHeader = isset($headers[$k]) ? $headers[$k] : '';
            $currentTotal = array_slice($line, 5, 6);

            // skip first line (the headers)
            if ($k == 0) continue;

            if (empty($subjectArea)) {
                $dataset[$genderSlug] = [
                    'gender' => $gender,
                    'total' => str_replace(' ', '', $currentTotal),
                    'year'  => $line[0],
                ];
                continue;
            }

            // create subject area entry
            if (! empty($subjectArea) && $subjectSubArea == '') {
                $dataset[$genderSlug]['children'][$subjectAreaSlug] = [
                    'title' => $subjectArea,
                    'slug'  => StringHelper::slugify($subjectArea),
                    'total' => str_replace(' ', '', $currentTotal),
                    'level' => 0,
                ];
                continue;
            } elseif (! empty($subjectSubArea) && empty($subjectGroup)) {
                // create subject subarea entry
                $dataset[$genderSlug]['children'][$subjectAreaSlug]['children'][$subjectSubAreaSlug] = [
                    'title' => $subjectSubArea,
                    'slug'  => StringHelper::slugify($subjectSubArea),
                    'total' => str_replace(' ', '', $currentTotal),
                    'level' => 1,
                ];
            }

            // create subject group entry
            if (! empty($subjectSubArea) && ! empty($subjectGroup)) {
                $dataset[$genderSlug]['children'][$subjectAreaSlug]['children'][$subjectSubAreaSlug]['children'][$subjectGroupSlug] = [
                    'title' => $subjectGroup,
                    'slug'  => StringHelper::slugify($subjectGroup),
                    'total' => str_replace(' ', '', $currentTotal),
                    'level' => 2,
                ];
            }
        }

        $data['dataset'] = $dataset;

        $this->data = collect($data);

        return $this;
    }

    /**
     * Retrieve csv data in a Collection format.
     * 
     * @return Collection
     */
    public function get()
    {
        return $this->data;
    }

    /**
     * Store every group column in a property.
     * 
     * @param  array  $groupColumns
     * @return void
     */
    public function groupColumns(array $groupColumns)
    {
        $this->groupColumns = $groupColumns;
    }

    /**
     * Store every total column in a property.
     * 
     * @param  array  $totalColumns
     * @return void
     */
    public function totalColumns(array $totalColumns)
    {
        $this->totalColumns = $totalColumns;
    }

    public function make()
    {
        // dd($this->data);
        $dataset = $this->createDataset($this->indicator, $this->file);

        $this->createGroupColumns($this->groupColumns);

        $this->createTotalColumns($this->totalColumns);

        $this->createGroups($dataset);
    }

    private function createGroupColumns(array $groupColumns)
    {
        foreach($groupColumns as $groupColumn) {
            GroupColumn::firstOrCreate(['name' => $groupColumn]);
        }
    }

    private function createTotalColumns(array $totalColumns)
    {
        foreach($totalColumns as $totalColumn) {
            $column = totalColumn::firstOrCreate(['name' => $totalColumn]);
            $this->totalColumnIds[] = $column->id;
        }
    }

    private function createGroup($datasetId, $genderData, $data, $parent = null)
    {
        foreach($data as $item) {
 
            $currentGroup = Group::firstOrCreate([
                'dataset_id'    => $datasetId,
                'column_id'     => GroupColumn::where('name', $this->groupColumns[0])->get()->first()->id,
                'parent_id'     => $parent,
                'name'          => $item['title'],
            ]);

            $total = Total::firstOrCreate([
                'relation_id'   => $currentGroup->id,
                'relation_type' => Group::class,
                'year'          => $genderData['year'],
                'gender'        => $genderData['gender'],
            ]);

            $this->createTotalValues($total, $item['total']);

            if (isset($item['children'])) {
                $this->createGroup($datasetId, $genderData, $item['children'], $currentGroup->id);
                continue;
            }
 
        }

        return $data;
    }

    private function createGroups($dataset)
    {
        $data = $this->data['dataset'];
        
        foreach($data as $genderData) {

            $total = Total::firstOrCreate([
                'relation_id'   => $dataset->id,
                'relation_type' => Dataset::class,
                'year'          => $genderData['year'],
                'gender'        =>  $genderData['gender'],
            ]);

            $this->createTotalValues($total, $genderData['total']);
            $this->createGroup($dataset->id, $genderData, $genderData['children']);

            // foreach($genderData['subject_area'] as $subjectArea) {
  
            //     $currentSubjectArea = Group::firstOrCreate([
            //         'dataset_id'    => $dataset->id,
            //         'column_id'     => GroupColumn::where('name', $this->groupColumns[0])->get()->first()->id,
            //         'parent_id'     => null,
            //         'name'          => $subjectArea['title'],
            //     ]);

            //     $total = Total::create([
            //         'relation_id'   => $currentSubjectArea->id,
            //         'relation_type' => Group::class,
            //         'year'          => $genderData['year'],
            //         'gender'        =>  $genderData['gender'],
            //     ]);

            //     $this->createTotalValues($total, $subjectArea['total']);

            //     foreach($subjectArea['subject_subarea'] as $subjectSubarea) {

            //         $currentSubjectSubarea = Group::firstOrCreate([
            //             'dataset_id'    => $dataset->id,
            //             'column_id'     => GroupColumn::where('name', $this->groupColumns[1])->get()->first()->id,
            //             'parent_id'     => $currentSubjectArea->id,
            //             'name'          => $subjectSubarea['title'],
            //         ]);

            //         $total = Total::firstOrCreate([
            //             'relation_id'   => $currentSubjectSubarea->id,
            //             'relation_type' => Group::class,
            //             'year'          => $genderData['year'],
            //             'gender'        =>  $genderData['gender'],
            //         ]);

            //         $this->createTotalValues($total, $subjectSubarea['total']);

            //         foreach($subjectSubarea['subject_group'] as $subjectGroup) {
            //             $currentSubjectGroup = Group::firstOrCreate([
            //                 'dataset_id'    => $dataset->id,
            //                 'column_id'     => GroupColumn::where('name', $this->groupColumns[2])->get()->first()->id,
            //                 'parent_id'     => $currentSubjectSubarea->id,
            //                 'name'          => $subjectGroup['title'],
            //             ]);

            //             $total = Total::firstOrCreate([
            //                 'relation_id'   => $currentSubjectGroup->id,
            //                 'relation_type' => Group::class,
            //                 'year'          => $genderData['year'],
            //                 'gender'        =>  $genderData['gender'],
            //             ]);

            //             $this->createTotalValues($total, $subjectGroup['total']);

            //         }
            //     }
            // }
        }
        return $this->data;
    }

    /**
     * Inserts the total values into the database
     * @param  array $totals
     * @return [type]
     */
    private function createTotalValues($total, $values)
    {
        collect($this->totalColumnIds)
            ->combine($values)
            ->each(function($value, $columnId) use($total) {

                return TotalValue::firstOrCreate([
                    'total_id'  => $total->id,
                    'column_id' => $columnId,
                    'value'     => $value,
                ]);
            });
    }

    private function createDataset($indicator, $file)
    {
        return Dataset::firstOrCreate([
            'indicator_id'  => 1,
            'file'          => basename($file),
            'version'       => 1,
            'status'        => '',
        ]);
    }
}