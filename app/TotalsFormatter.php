<?php

namespace App;

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

    public function addGroup($column, $group)
    {
        // needs refactor
        $hierarchical = ['Ämnesområden', 'Ämnesdelsområden', 'Ämnesgrupp'];

        $data = [
            'column'    => $column,
            'totals'    => $group,
        ];

        if (in_array($column, $hierarchical)) {
            $data['top_parent_id'] = GroupColumn::first()->id;
        }

        $this->data['groups'][] = $data;
    }

    private function build()
    {
        return $this->data;
    }
}