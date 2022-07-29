<?php

namespace App\Helpers;

use App\Dataset;

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
        $datasets = Dataset::where('indicator_id', $indicator->id)
                ->whereHas('statuses', function ($query) use ($status) {
                    $query->where('name', $status);
                })
                ->orderByDesc('year')
                ->get(['year', 'id']);

        if ($datasets->isEmpty()) {
            return;
        }

        // sort HT/VT years
        if (in_array(substr($datasets->first()->year, 0, 2), ['HT', 'VT'])) {
            $datasets = static::sortHtVt($datasets);
        }

        return $datasets->first();
    }

    private static function sortHtVt($datasets)
    {
        return $datasets->sort(function ($a, $b) {
            return substr($b->year, 2, 4) - substr($a->year, 2, 4);
        })
                ->values()
                ->take(2)
                ->sort(function ($a, $b) {
                    $aYear = substr($a->year, 2, 4);
                    $bYear = substr($b->year, 2, 4);

                    if ($aYear === $bYear) {
                        return $aYear + $bYear;
                    }

                    return $bYear - $aYear;
                });
    }
}
