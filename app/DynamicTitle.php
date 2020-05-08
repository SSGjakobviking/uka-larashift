<?php

namespace App;

use App\Helpers\DatasetHelper;
use App\Helpers\StringHelper;
use App\TotalColumn;

class DynamicTitle
{
    private $indicator;

    private $filters;

    private $title;

    private $config;

    public function __construct(Indicator $indicator, Filter $filters)
    {
        $this->indicator = $indicator;
        $this->filters = $filters;
        $this->stringConfig = config('strings');
    }

    /**
     * Retrieves the dynamic value.
     * 
     * @return string
     */
    public function get($ignore)
    {   
        // Retrieves nice values from indicator config file for each filter group
        $filters = $this->niceValue($this->filters->all()->forget($ignore));
        
        // Builds the dynamic string by replacing the placeholders for each group with their filter value
        $title = $this->build($this->indicator->title_config, $filters);

        return $title;
    }

    /**
     * Retrieves filter group nice values from the indicator config file.
     * 
     * @param  Illuminate\Support\Collection $filters
     * @return Illuminate\Support\Collection
     */
    private function niceValue($filters)
    {

        return $filters->map(function($value, $key) {

            // year doesn't need a nice value from the config so we return it directly.
            if ($key === 'year') {
                return;
            }

            $prefix = isset($this->stringConfig[$key]) ? $this->stringConfig[$key] : null;

            if ($key === 'group_slug' && ! is_null($value)) {
                $dataset = DatasetHelper::lastPublishedDataset($this->indicator);
                // if no published dataset exists, retrieve lastpreviewed dataset
                if (! $dataset) {
                    $dataset = DatasetHelper::lastPublishedDataset($this->indicator, 'preview');
                }
                $slug = Total::where('dataset_id', $dataset->id)->where('group_slug', $value);
                
                if($slug->first() == null) {
                    return null;
                }
                
                $slug = $slug
                    ->first()
                    ->group()
                    ->first()
                    ->name;
                return mb_strtolower($slug);
            }

            if ($key == 'age_group') {

                $ageGroup = TotalColumn::find($value)->name;

                // skipp total
                if ($ageGroup == 'Total') {
                    return false;
                }

                $value = 'i åldersgruppen ' . $ageGroup . ' år';

                return $value;
            }

            if ($key == 'university' && $value != strtolower('riket')) {
    
                $university = $this->leftSpacing(University::find($value)->name);

                if (trim(mb_strtolower($university)) == 'riket') {
                    return false;
                }

                return ' ' . $prefix . $university;
            }
           
            $value = $prefix[StringHelper::slugify($value)];

            return $value;
        });
    }

    /**
     * Replaces the dynamic title placeholders with the filtering values.
     * Default title is defined in config/indicator.php file.
     * 
     * @param  string $title
     * @param  Illuminate\Support\Collection $filters
     * @return string
     */
    private function build($title, $filters)
    {
        // remove group prefix if group filter is not active.
        if (! isset($filters['group_slug'])) {
            $title = $this->removeGroupPrefix($title);
        }
        // replaces placeholders for all the 'groups' that has been filtered.
        foreach($filters as $name => $value) {
            $title = str_replace('{' . $name . '}', $value, $title);
        }

        // remove placeholders that hasn't been filtered and return the string.
        // regexp targets curly brackets and it's value.
        $title = preg_replace('/{(.*?)}/i', '', $title);
        $title = $this->removeWhiteSpace($title);

        return $title;
    }

    /**
     * Removes group prefix when no group filter has been done.
     * 
     * @param  [type] $title
     * @return [type]
     */
    private function removeGroupPrefix($title)
    {
        $pieces = collect(explode(' ', $title));
        $search = $pieces->search('{group_slug}{university}');

        if (! $search) {
            return $title;
        }
        
        $prefix = $pieces[$search - 1];
        $title = str_replace($prefix, '', $title);

        return $title;
    }

    function removeWhiteSpace($text) {
        $text = preg_replace('/[\t\n\r\0\x0B]/', '', $text);
        $text = preg_replace('/([\s])\1+/', ' ', $text);
        $text = trim($text);
        return $text;
    }

    /**
     * Adds left spacing for the filter value.
     * 
     * @param  string $value
     * @return string
     */
    private function leftSpacing($value = null)
    {
        if (is_null($value) || strlen($value) == 0) {
            return null;
        }

        return str_pad($value, strlen($value) + 1, ' ', STR_PAD_LEFT);
    }
}