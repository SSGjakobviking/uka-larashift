<?php

namespace App;

use App\Dataset;
use App\Helpers\StringHelper;
use App\University;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class DatasetImporter
{

    /**
     * The dataset object.
     * @var App\Dataset
     */
    protected $dataset;

    /**
     * This var contains structured parsed csv data.
     * @var Illuminate\Support\Collection
     */
    protected $data;

    /**
     * Stores the group columns
     * @var App\GroupColumn
     */
    protected $groupColumns;

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
        $filePath = public_path('uploads/' . $dataset->file);

        $csv = Reader::createFromPath($filePath)->setDelimiter(';');

        // retrieve the header columns
        $header = collect($csv->fetchOne());

        // retrieve all columns with start pos 2 and end pos -3 
        // (all columns between university(pos 2) and gender(pos -3) columns are considered groups)
        $groups = (clone $header)->splice(2, -3);

        // retrieve hierarchical group columns
        $groupColumns = $groups->map(function($item) {
            if (preg_match('/\[(\d+)\]$/', $item)) {
                return preg_replace('/\[(\d+)\]$/', '', $item);
            }

            return $item;
        })->toArray();

        // create group columns
        $this->groupColumns($groupColumns);

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
                                ->map(function ($gender) use ($groups) {
                                    return $this->doTheTotalThing($groups->first(), $groups, $this->iterateOverGroups($gender, $groups));
                                });
                        });
                });
        // dd($data->first()->first());
        return $data;
    }

    /**
     * Creates a total/children container for every group.
     * 
     * @param  [type] $group
     * @param  [type] $groups
     * @param  [type] $children
     * @return [type]
     */
    private function doTheTotalThing($group, $groups, $children) {
        $total = $children->has('') ? $children->get('') : $children;
        $children = $children->forget('');

        $combined = collect($total->pluck('Åldersgrupp'))->combine($total->pluck('Antal'));

        // merge current age group totals with the default age groups array
        $ageGroupTotals = $combined->union($this->ageGroups);

        // Union above can mess up the order of age groups
        // so we make sure that Total always is the last item in the array
        $totals = $ageGroupTotals->pull('Total');
        $ageGroupTotals->put('Total', $totals);

        $response = collect([
            'total' => $ageGroupTotals,
            'children' => $children,
        ]);

        // set group index to true or false depending on if the true is hierarchical
        $response = $response->put('group', (boolean) preg_match('/\[(\d+)\]$/', $group));

        // removes the 'children' index if no children exists in the current group
        if ($groups->isEmpty()) {
            $response->forget('children');
        }

        return $response;
    }

    /**
     * Iterates over all groups recursively.
     * 
     * @param  Illuminate\Support\Collection $items holds all the csv data
     * @param  Illuminate\Support\Collection $groups holds all the group columns
     * @return Illuminate\Support\Collection
     */
    private function iterateOverGroups($items, $groups) {
        if ($groups->isEmpty()) {
            return $items;
        }

        $items = $items->groupBy($group = $groups->first());

        $groups = $groups->values()->forget(0)->values();

        return $items->map(function ($item, $key) use ($group, $groups) {
            return $key === '' ? $item : $this->doTheTotalThing($group, $groups, $this->iterateOverGroups($item, $groups));
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
                    $topParentId = $currentGroup->id;
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