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
    public static function lastPublishedDataset($indicator, $status = 'published')
    {
        return Dataset::where('indicator_id', $indicator->id)
                    ->whereHas('statuses', function($query) use($status) {
                        $query->where('name', $status);
                    })
                    ->orderBy('year', 'desc')
                    ->first(['year', 'id']);
    }
}