<?php

namespace App;

use App\Dataset;
use App\Helpers\StringHelper;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class DatasetImporter
{

    protected $file;

    protected $data;

    protected $indicatorSlug;

    protected $groupColumns;

    protected $totalColumns;

    protected $totalColumnIds;

    const UNIVERSITY_DEFAULT = 'Riket';

    public function __construct($file, $indicator)
    {
        $this->file = $file;
        $this->indicator = $indicator;

        $this->parse($file);
    }

    private function parse($file)
    {
        $csv = Reader::createFromPath($file);
        $csvData = collect($csv->setOffset(1)->fetchAll());
        $headers = $csv->fetchOne();

        // dd($headers);
        // dd($csvData);
        
        // $genderTitleSlug= StringHelper::slugify($headers[1]);
        // $subjectAreaTitleSlug= StringHelper::slugify($headers[2]);
        // $subjectSubAreaTitleSlug= StringHelper::slugify($headers[3]);
        // $subjectGroupTitleSlug= StringHelper::slugify($headers[4]);
        $dataset = [];
        $totals = [];

        foreach($csvData as $k => $line) {
            // dd($line);

            $term = $line[0];
            $termSlug = StringHelper::slugify($term);

            $year = $line[1];

            $university = $line[2];

            if (empty($university)) {
                $university = self::UNIVERSITY_DEFAULT;
            }

            $universitySlug = StringHelper::slugify($university);

            $subjectArea = $line[3];
            $subjectAreaSlug = StringHelper::slugify($subjectArea);

            $subjectSubArea = $line[4];
            $subjectSubAreaSlug = StringHelper::slugify($subjectSubArea);
            
            $subjectGroup = $line[5];
            $subjectGroupSlug = StringHelper::slugify($subjectGroup);

            $gender = $line[6];
            $genderSlug = StringHelper::slugify($gender);

            $ageGroup = $line[7];
            
            $currentTotal = end($line);

            if (! isset($dataset[$termSlug])) {
                $dataset[$termSlug][$genderSlug] = [];
            }

            $totals[] = $currentTotal;

            if ($university == self::UNIVERSITY_DEFAULT && empty($subjectArea)) {
                $dataset[$termSlug][$genderSlug][$universitySlug] = [
                    'title'  => $university,
                    'slug'   => $universitySlug,
                    'term'   => $term,
                    'gender' => $gender,
                    'year'  => $year,
                ];

                if ($ageGroup == 'Total') {
                    $dataset[$termSlug][$genderSlug][$universitySlug]['totals'] = $totals;
                    $totals = [];
                }

                // if ($k == 3) {
                //     echo 'test';
                //     dd($dataset);
                // }
                continue;
            }

            // add university
            if (! empty($university) && $university != self::UNIVERSITY_DEFAULT && empty($subjectArea)) {
                $dataset[$termSlug][$genderSlug][$universitySlug] = [
                    'title' => $university,
                    'slug'  => $universitySlug,
                    'term'   => $term,
                    'gender' => $gender,
                    'year'  => $year,
                    'level' => 0,
                ];

                // add totals
                if ($ageGroup == 'Total') {
                    $dataset[$termSlug][$genderSlug][$universitySlug]['totals'] = $totals;
                    $totals = [];
                }
                continue;
            }

            // create subject area entry
            if (! empty($subjectArea) && $subjectSubArea == '') {
                $dataset[$termSlug][$genderSlug][$universitySlug]['children'][$subjectAreaSlug] = [
                    'title' => $subjectArea,
                    'slug'  => StringHelper::slugify($subjectArea),
                    'term'   => $term,
                    'gender' => $gender,
                    'year'  => $year,
                    'level' => 1,
                ];

                // add totals
                if ($ageGroup == 'Total') {
                    $dataset[$termSlug][$genderSlug][$universitySlug]['children'][$subjectAreaSlug]['totals'] = $totals;
                    $totals = [];
                }
                continue;
            } elseif (! empty($subjectSubArea) && empty($subjectGroup)) {
                // create subject subarea entry
                $dataset[$termSlug][$genderSlug][$universitySlug]['children'][$subjectAreaSlug]['children'][$subjectSubAreaSlug] = [
                    'title' => $subjectSubArea,
                    'slug'  => StringHelper::slugify($subjectSubArea),
                    'term'   => $term,
                    'gender' => $gender,
                    'year'  => $year,
                    'level' => 2,
                ];

                // add totals
                if ($ageGroup == 'Total') {
                    $dataset[$termSlug][$genderSlug][$universitySlug]['children'][$subjectAreaSlug]['children'][$subjectSubAreaSlug]['totals'] = $totals;
                    $totals = [];
                }

            } elseif (! empty($subjectSubArea) && ! empty($subjectGroup)) {
                $dataset[$termSlug][$genderSlug][$universitySlug]['children'][$subjectAreaSlug]['children'][$subjectSubAreaSlug]['children'][$subjectGroupSlug] = [
                    'title' => $subjectGroup,
                    'slug'  => StringHelper::slugify($subjectGroup),
                    'term'   => $term,
                    'gender' => $gender,
                    'year'  => $year,
                    'level' => 3,
                ];

                // add totals
                if ($ageGroup == 'Total') {
                    $dataset[$termSlug][$genderSlug][$universitySlug]['children'][$subjectAreaSlug]['children'][$subjectSubAreaSlug]['children'][$subjectGroupSlug]['totals'] = $totals;
                    $totals = [];
                }

            }
        }
        dd($dataset['vt']['kvinnor']['beckmans-designhogskola']);
        $data['dataset'] = $dataset;

        $this->data = collect($data);

        return $this;
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

    private function createGroup($dataset, $prevData, $data, $parent = null, $level = -1)
    {
        foreach($data as $item) {
            if ($item == head($data)) {
                $level++;
            }

            $currentGroup = Group::firstOrCreate([
                'column_id'     => GroupColumn::where('name', $this->groupColumns[$level])->get()->first()->id,
                'parent_id'     => $parent,
                'name'          => $item['title'],
            ]);

            $total = Total::firstOrCreate([
                'dataset_id'    => $dataset->id,
                'group_id'      => $currentGroup->id,
                'term'          => $item['term'],
                'year'          => $item['year'],
                'gender'        => $item['gender'],
            ]);

            // $dataset->groups()->attach($currentGroup);
            $this->createTotalValues($total, $item['totals']);

            if (isset($item['children'])) {
                $this->createGroup($dataset, $prevData, $item['children'], $currentGroup->id, $level);
                continue;
            }
        }

        return $data;
    }

    private function createGroups($dataset)
    {
        $data = $this->data['dataset'];

        foreach($data as $term) {

            foreach($term as $group) {
                #foreach($gender as $group) {
                $this->createGroup($dataset, $group, $group);
                #}
            }

            // $total = Total::firstOrCreate([
            //     'dataset_id'    => $dataset->id,
            //     'group_id'      => null,
            //     'term'          => $genderData['term'],
            //     'year'          => $genderData['year'],
            //     'gender'        =>  $genderData['gender'],
            // ]);

            // $this->createTotalValues($total, $genderData['total']);
            // $this->createGroup($dataset, $genderData, $genderData['children']);
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