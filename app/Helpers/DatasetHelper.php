<?php

namespace App\Helpers;

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
        $latest = Total::whereHas('dataset', function($query) use($indicator) {
            $query->where('status', 'published');
            $query->where('indicator_id', $indicator->id);
        })
        ->where('gender', 'Total')
        ->orderBy('year', 'desc')
        ->first(['year', 'dataset_id']);

        return $latest;
    }
}