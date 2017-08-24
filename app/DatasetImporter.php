<?php

namespace App;

use App\Dataset;
use App\University;
use League\Csv\Reader;
use App\Helpers\StringHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DatasetImporter
{
    /**
     * The dataset object.
     * 
     * @var App\Dataset
     */
    protected $dataset;

    /**
     * This var contains structured parsed csv data..
     * 
     * @var Illuminate\Support\Collection
     */
    protected $data;

    /**
     * Stores the group columns.
     * 
     * @var App\GroupColumn
     */
    protected $groupColumns;

    /**
     * Columns with square brackets.
     * 
     * @var [type]
     */
    protected $originalGroupColumns;

    /**
     * Columns with square brackets.
     * 
     * @var [type]
     */
    protected $originalGroups;

    /**
     * Stores the total column name (which will always be the last column).
     * 
     * @var [type]
     */
    protected $totalColumn;

    /**
     * Stores the total columns
     * @var App\TotalColumn
     */
    protected $totalColumns;

    /**
     * Stores the age group
     * @var array
     */
    protected $ageGroups = [];

    /**
     * This is the default "university" / all universities
     */
    const UNIVERSITY_DEFAULT = 'Riket';

    public function __construct($dataset)
    {
        $this->dataset = $dataset;
        Log::info('Start importing...');
        $this->data = $this->parse($dataset);
    }

    /**
     * Parses the dataset (csv) and builds a collection with all of the data structured.
     * 
     * @param  App\Dataset $dataset
     * @return Illuminate\Support\Collection
     */
    public function parse($dataset)
    {
        // $filePath = public_path('uploads/' . $dataset->file);
        $filePath = $dataset;
        $csv = Reader::createFromPath($filePath)->setDelimiter(';');

        // retrieve the header columns
        $header = collect($csv->fetchOne());
        $this->totalColumn = $header->last();
        // retrieve all columns with start pos 2 and end pos -3 
        // (all columns between university(pos 2) and gender(pos -3) columns are considered groups)
        $groups = (clone $header)->splice(2, -3);

        $this->originalGroupColumns = $groups;

        // retrieve hierarchical group columns
        $groupColumns = $groups->map(function($item) {
            if (preg_match('/\[(\d+)\]$/', $item)) {
                return preg_replace('/\[(\d+)\]$/', '', $item);
            }

            return $item;
        })->toArray();

        // create group columns
        $this->groupColumns($groupColumns);

        $this->originalGroups = clone $this->originalGroupColumns;

        // start parsing the csv here and build the "data" object
        $data = collect($csv->setOffset(1)->fetchAll())
                ->map(function ($line) use ($header, $groups) {
                    return $header->combine($line) // combine csv columns with the current line of values
                            ->map(function($value, $key) {

                                // return default Lärosäte 'Riket' if Lärosäte is empty
                                return ($key == 'Lärosäte' && $value == null) ? self::UNIVERSITY_DEFAULT : $value;
                            });
                })
                ->groupBy($header->get(1)) // group by university
                ->map(function ($university) use ($header, $groups) {
                    
                    // store age group keys with default value of 0 because of some indicators could be missing
                    // one age group so we set the default value here.
                    if (empty($this->ageGroups)) {
                        $this->ageGroups = $university->groupBy('Åldersgrupp')->mapWithKeys(function($item, $value) {
                            return [$value => 0];
                        });
                    }

                    return $university
                        ->groupBy($header->get(0)) // group by year
                        ->map(function ($year) use ($header, $groups) { // loop through every year
                            return $year
                                ->groupBy($header->reverse()->values()->get(2)) // group by gender (always pos -2 from the end of csv headers)
                                ->map(function ($gender, $key) use ($groups) {
                                    return $this->iterateOverGender($gender);
                                });
                        });
                });
        
        dd(
            $data->get('Riket')->first()->first(), 
            $data->get('Blekinge tekniska högskola')->first()->first()
        );

        return $data;
    }









    public function iterateOverGender($data)
    {
        $groups = $this->originalGroups->toBase();

        $collection = $this->iterateOverGroups($data, $groups);

        if ($collection->has('')) {
            $this->iterateOverTotal($collection->get(''));
        }

        return $collection;
    }

    public function iterateOverTotal($data)
    {
        // CALCULATE THE TOTAL
        // Total columns - current column index = amount to navigate
        // 
        // Total columns: 3, Current column index: 1
        //      3 - 1 = 2
        //      ->get('')->get('')
        //      
        // Total columns: 4, Current column index: 1
        //      4 - 1 = 3
        //      ->get('')->get('')->get('')
        //      
        // Total columns: 4, Current column index: 4
        //      4 - 4 = 0
        //      DO NOTHING, YOU ARE ALREADY INSIDE TOTAL
        //      
        // Total columns: 4, Current column index: 3
        //      4 - 3 = 1
        //      ->get('')
    }

    public function iterateOverGroups($data, $groups, $activeGroup = null)
    {
        // Check if we've gone through all of the groups
        if ($groups->isEmpty()) {
            // Create a unique groups collection with ALL of the groups available
            $original = $this->originalGroups->toBase();

            // If the currently active group is NOT the last group available
            if ($activeGroup !== $original->last()) {
                // Retrieve all of the remaining groups and set the $groups variable to it
                // We add +1 to the index to remove the active group from the new groups list
                $groups = $groups->merge(
                    $original->splice($original->search($activeGroup) + 1)
                );
            } else {
                // We've reached the end of the line, return the data
                return $data;
            }
        }

        // Pop out the first group from the groups list
        $group = $groups->shift();

        // Group the data by the popped out group
        $collection = $data->groupBy($group);

        // Loop over each item in the collection
        return $collection->map(function ($subdata, $category) use ($groups, $group) {
            // Repeat this whole function for each item
            return $this->iterateOverGroups($subdata, $groups, $group);
        });
    }








    private function removeStartChar($item, $char) {
        if (strpos($item, $char) === 0) {
            return (string) substr($item, 1, strlen($item));
        }

        return $item;
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

    /**
     * Executes the import.
     * 
     * @return void
     */
    public function make()
    {
        $this->createGroupColumns($this->groupColumns);

        $this->createGroups($this->dataset);
        
        $this->updateStatus($this->dataset);
    }

    private function createGroupColumns(array $groupColumns)
    {
        foreach($groupColumns as $groupColumn) {
            GroupColumn::firstOrCreate(['name' => $groupColumn]);
        }
    }

    /**
     * Creates groups recursively (if group has any children).
     * 
     * @param  [type]  $dataset
     * @param  [type]  $prevData
     * @param  [type]  $data
     * @param  [type]  $university
     * @param  [type]  $year
     * @param  [type]  $gender
     * @param  [type]  $parent
     * @param  integer $level
     * @return [type]
     */
    private function createGroup($dataset, $prevData, $data, $university, $year, $gender, $parent = null, $level = -1, $topParentId = null)
    {
        foreach($data as $groupName => $item) {
            $firstInLevel = $data->keys()->first();

            // check to see if groupName is in first level
            if ($firstInLevel == $groupName) {
                $level++;
            }

            $currentGroup = Group::firstOrCreate([
                'column_id'     => GroupColumn::where('name', $this->groupColumns[$level])->get()->first()->id,
                'parent_id'     => $parent,
                'name'          => $groupName,
            ]);

            if (isset($item['children']) && $firstInLevel == $groupName) {
                if ($level === 0) {
                    $topParentId = $currentGroup->column->id;
                }
            }

            $currentGroup->column()->update(['top_parent_id' => $topParentId]);

            // attach a group to a university
            $university->groups()->attach($currentGroup);

            // create the total
            $total = $this->createTotal(
                $dataset,
                $university,
                $year,
                $gender,
                $item->get('total'),
                $currentGroup
            );

            if (isset($item['children'])) {
                $this->createGroup($dataset, $prevData, $item['children'], $university, $year, $gender, $currentGroup->id, $level, $topParentId);
                continue;
            }
        }

        return $data;
    }

    /**
     * Loops through our structured csv data and inserts it into the db.
     * 
     * @param  App\Dataset $dataset
     * @return [type]
     */
    private function createGroups($dataset)
    {
        // dd($this->data['Riket'][2014]['Kvinnor']);
        foreach($this->data as $university => $years) {

            foreach($years as $year => $genders) {

                foreach($genders as $gender => $group) {

                    $createdUniversity = $this->createUniversity($university, StringHelper::slugify($university));

                    $this->createTotal($dataset, $createdUniversity, $year, $gender, $group->get('total'));

                    $this->createGroup($dataset, $group['children'], $group['children'], $createdUniversity, $year, $gender);

                }
            }
        }

        return $this->data;
    }

    /**
     * Creates a total entry in the Totals table.
     * @param  [type] $dataset
     * @param  [type] $group
     * @param  [type] $university
     * @param  [type] $term
     * @param  [type] $year
     * @param  [type] $gender
     * @return [type]
     */
    private function createTotal($dataset, $university, $year, $gender, $totals, $group = null)
    {
        if (! is_null($group)) {
            $group = $group->id;
        }

        $total = Total::firstOrCreate([
            'dataset_id'    => $dataset->id,
            'group_id'      => $group,
            'university_id' => $university->id,
            'year'          => $year,
            'gender'        => $gender,
        ]);

        $this->createTotalValues($total, $totals);

        return $total;
    }

    /**
     * Inserts the total columns and values into the database.
     * 
     * @param  array $totals
     * @return [type]
     */
    private function createTotalValues($total, $totals)
    {

        $totals->each(function($value, $column) use($total) {
            $column = TotalColumn::firstOrCreate(['name' => $column]);

            TotalValue::create([
                'total_id' => $total->id,
                'column_id' => $column->id,
                'value'    => $value,
            ]);

        });
    }

    /**
     * Removes the 'processing' status and sets the status to null which means it's ready for use.
     * 
     * @param  App\Dataset $dataset
     * @return App\Dataset
     */
    private function updateStatus($dataset)
    {
        return Dataset::where('id', $dataset->id)
        ->update([
            'status' => null,
        ]);
    }

    /**
     * Create a university if it doesn't already exist
     * @param  string $name
     * @param  string $slug
     * @return Illuminate\Support\Collection
     */
    private function createUniversity($name, $slug)
    {
        return University::firstOrCreate([
            'name' => $name,
            'slug' => $slug
        ]);
    }
}