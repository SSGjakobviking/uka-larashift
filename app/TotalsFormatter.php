<?php

namespace App;
use Illuminate\Support\Collection;

class TotalsFormatter
{
    
    protected $data;

    public function get()
    {
        return $this->build();
    }

    public function add($column, $totals)
    {
        $this->data['yearly_totals'] = [
            'column' => $column,
            'totals' => $totals,
        ];
    }

    /**
     * Adds group of data to the 'groups' item in the total object.
     * 
     * @param array $data
     */
    public function addGroup(array $data)
    {
        $this->data['groups'][] = $data;
    }

    /**
     * Adds multiple groups to the 'group' item in the total object.
     * 
     * @param Collection $groups
     */
    public function addGroups(Collection $groups)
    {
        $groups->each(function($group) {
            $this->addGroup($group);
        });
    }

    private function build()
    {
        return $this->data;
    }
}