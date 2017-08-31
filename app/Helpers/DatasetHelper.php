<?php

namespace App\Helpers;

use App\Dataset;
use App\Total;

class DatasetHelper
{
    /**
     * Retrieves last published dataset id and year (from totals table).
     * 
     * @param  [type] $indicator
     * @return [type]
     */
    public static function lastPublishedDataset($indicator)
    {
        return Dataset::where('indicator_id', $indicator->id)
                    ->orderBy('year', 'desc')
                    ->first(['year', 'id']);
    }
}