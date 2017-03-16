<?php

namespace App;

class TotalsFormatter
{

    protected $indicator;

    protected $dataset;

    protected $filter;

    protected $dynamicTitle;

    protected $totals;

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
        $this->data['groups'][] = [
            'column'    => $column,
            'totals'    => $group,
        ];
    }

    private function build()
    {
        return $this->data;
    }
}