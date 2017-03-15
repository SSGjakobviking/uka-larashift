<?php

namespace App;

use App\Indicator;

class Filter
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = collect($filters);
    }

    /**
     * Stores every filter that is not null into the filters collection.
     * 
     * @param  integer $group
     * @param  integer $year
     * @param  string $gender
     * @return void
     */
    private function removeEmpty()
    {
        // remove empty filters
        return $this->filters->filter(function($value) {
            return ! is_null($value);
        });
    }

    /**
     * Retrieves all of the filters.
     * 
     * @return Collection
     */
    public function get()
    {
        $filters = $this->removeEmpty();

        return $filters;
    }
}