<?php

namespace App\Providers;

use App\Search;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('search', function($app, $arguments) {
            return 'hei';
            //return new Search(ClientBuilder::create()->build(), $indicator, $datasetId);
        });
    }
}
