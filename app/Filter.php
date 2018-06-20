<?php

namespace App;

use App\DynamicTitle;
use App\Helpers\UrlHelper;
use App\Indicator;

class Filter
{
    protected $filters;

    protected $indicator;

    protected $year;

    protected $filterUrl;

    protected $children;

    public function __construct(array $filters, $indicator, $year, $children = [])
    {
        $this->filters   = collect($filters);
        $this->indicator = $indicator;
        $this->year      = $year;
        $this->children  = (array) $children;
    }

    /**
     * Generates a string with the passed query vars from the api.
     * 
     * @param  App/Indicator $indicator
     * @param  integer $year
     * @return string
     */
    public function url()
    {
        $queryString = UrlHelper::queryString($this->all()->toArray());
        $url = $this->base($this->indicator);

        if (! empty($queryString)) {
            $url .= $queryString;
        }

        return $url;
    }

    /**
     * Retrieve totals base url.
     * 
     * @param  App\Indicator $indicator
     * @param  integer $year
     * @return string
     */
    public function base($indicator = null)
    {
        if (is_null($indicator)) {
            $indicator = $this->indicator;
        }

        return route('totals', ['indicator' => $indicator->id]);
    }

    public function updateUrl(array $group)
    {
        return $this->replaceQueryParams($this->url(), $group);
    }

    public function replaceQueryParams($url, $params)
    {
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $oldParams);

        if (empty($oldParams)) {
            return rtrim($url, '?') . '?' . http_build_query($params);
        }

        $params = array_merge($oldParams, $params);

        return preg_replace('#\?.*#', '?' . http_build_query($params), $url);
    }

    /**
     * Retrieves all of the filters.
     * 
     * @return Collection
     */
    public function all()
    {
        return $this->removeEmpty();
    }

    /**
     * Returns group children.
     * 
     * @return
     */
    public function children()
    {

        return collect($this->children)->map(function($item) {
            
            $filterArgs = [
                $item['_source']['group'] => $item['_source']['id'],
                'year' => $this->year,
            ];
            return new static($filterArgs, $this->indicator, $this->year, $item['children'] || []);
        });
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
     * Retrieve dynamic title based on filter.
     * 
     * @return string
     */
    public function title($ignore = [])
    {
        return (new DynamicTitle($this->indicator, $this))->get($ignore);
    }

    public function titleExclude($key = null)
    {
        return $this->title($key);
    }

}