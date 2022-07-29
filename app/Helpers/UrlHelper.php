<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;

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
            return '?'.http_build_query($query);
        }

        return $query;
    }

    /**
     * Returns controller name from the current route.
     *
     * @return string
     */
    public static function rootRoute()
    {
        return kebab_case(collect(explode('.', Route::currentRouteName()))->first());
    }
}
