<?php

namespace App;

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
        $this->config = isset(config('indicator')[$indicator->slug]) ? config('indicator')[$indicator->slug] : config('indicator')['default'];
        $this->title = $this->config['dynamic_title']['default'];
    }

    /**
     * Retrieves the dynamic value.
     * 
     * @return string
     */
    public function get($ignore)
    {
        $title = $this->title;
        
        // Retrieves nice values from indicator config file for each filter group
        $filters = $this->niceValue($this->filters->all()->forget($ignore));

        // Builds the dynamic string by replacing the placeholders for each group with their filter value
        $title = $this->build($title, $filters);

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

            $prefix = $this->config['dynamic_title'][$key];

            if ($key === 'group_slug' && ! is_null($value)) {
                $slug = $this->indicator
                        ->datasets
                        ->first()
                        ->totals()
                        ->where('group_slug', $value)
                        ->first()
                        ->group()
                        ->first();

                $group = $this->leftSpacing($slug->name);

                return $this->leftSpacing($prefix . strtolower($group));
            }

            if ($key == 'age_group') {

                $ageGroup = TotalColumn::find($value)->name;

                // skipp total
                if ($ageGroup == 'Total') {
                    return false;
                }

                $value = $prefix[StringHelper::slugify($ageGroup)];

                return $this->leftSpacing($value);
            }

            if ($key == 'university' && $value != strtolower('riket')) {
    
                $university = $this->leftSpacing(University::find($value)->name);

                if (trim(strtolower($university)) == 'riket') {
                    return false;
                }

                return $this->leftSpacing($prefix . $university);
            }
           
            $value = $prefix[StringHelper::slugify($value)];

            return $this->leftSpacing($value);
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
        // replaces placeholders for all the 'groups' that has been filtered.
        foreach($filters as $name => $value) {
            $title = str_replace('{' . $name . '}', $value, $title);
        }

        // remove placeholders that hasn't been filtered and return the string.
        // regexp targets curly brackets and it's value.
        return preg_replace('/{(.*?)}/i', '', $title);
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