<?php

namespace App\Helpers;

class UrlHelper
{

    /**
     * Construct a query string url.
     * 
     * @param  array  $query
     * @return [type]
     */
    public static function queryString(array $query)
    {
        if (! empty($query)) {
            return '?' . http_build_query($query);
        }

        return $query;
    }
}