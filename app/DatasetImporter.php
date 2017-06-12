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

    protected $dataset;

    protected $data;

    protected $groupColumns;

    protected $totalColumns;

    protected $totalColumnIds;

    protected $user;

    const UNIVERSITY_DEFAULT = 'Riket';

    public function __construct($dataset)
    {
        $this->dataset = $dataset;
        Log::info('Start importing...');
        $this->data = $this->parse($dataset);
    }

    public function parse($dataset)
    {
        $filePath = public_path('uploads/' . $dataset->file);

        $csv = Reader::createFromPath($filePath)->setDelimiter(';');

        $header = collect($csv->fetchOne());

        $groups = (clone $header)->splice(2, -3);

        // retrieve hierarchical group columns
        $groupColumns = $groups->map(function($item) {
            if (preg_match('/\[(\d+)\]$/', $item)) {
                return preg_replace('/\[(\d+)\]$/', '', $item);
            }
        })->toArray();

        // create group columns
        $this->groupColumns($groupColumns);

        $data = collect($csv->setOffset(1)->fetchAll())
                ->map(function ($line) use ($header, $groups) {
                    return $header->combine($line)
                            ->map(function($value, $key) {
                                // return default Lärosäte 'Riket' if Lärosäte is empty
                                return ($key == 'Lärosäte' && $value == null) ? self::UNIVERSITY_DEFAULT : $value;
                            });
                })
                ->groupBy($header->get(1))
                ->map(function ($university) use ($header, $groups) {
                    return $university
                        ->groupBy($header->get(0))
                        ->map(function ($year) use ($header, $groups) {
                            return $year
                                ->groupBy($header->reverse()->values()->get(2))
                                ->map(function ($gender) use ($groups) {
                                    return $this->doTheTotalThing($groups->first(), $groups, $this->iterateOverGroups($gender, $groups));
                                });
                        });
                });

        return $data;
    }

    private function doTheTotalThing($group, $groups, $children) {
        $total = $children->has('') ? $children->get('') : $children;

        $children = $children->forget('');

        $response = collect([
            'total' => collect($total->pluck('Åldersgrupp'))->combine($total->pluck('Antal')),
            'children' => $children,
        ]);

        $response = $response->put('group', (boolean) preg_match('/\[(\d+)\]$/', $group));

        if ($groups->isEmpty()) {
            $response->forget('children');
        }

        return $response;
    }

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
        
        $this->createGroupColumns($this->groupColumns);

        // $this->createTotalColumns($this->totalColumns);

        $this->createGroups($this->dataset);
        
        $this->updateStatus($this->dataset);
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
            $column = TotalColumn::firstOrCreate(['name' => $totalColumn]);
            $this->totalColumnIds[] = $column->id;
        }
    }

    private function createGroup($dataset, $prevData, $data, $university, $year, $gender, $parent = null, $level = -1)
    {
        foreach($data as $groupName => $item) {
            $firstInLevel = $data->keys()->first();

            // check to see if groupName is in first level
            if (isset($item['children']) && $firstInLevel == $groupName) {
                $level++;
            }

            $currentGroup = Group::firstOrCreate([
                'column_id'     => GroupColumn::where('name', $this->groupColumns[$level])->get()->first()->id,
                'parent_id'     => $parent,
                'name'          => $groupName,
            ]);

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
                $this->createGroup($dataset, $prevData, $item['children'], $university, $year, $gender, $currentGroup->id, $level);
                continue;
            }
        }
        
        return $data;
    }

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