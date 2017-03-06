<?php

use App\DatasetImporter;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $indicator = App\Indicator::first();
    // return $indicator->name;
    $dataset = $indicator->datasets->first();

    $group = $dataset->groups;
    $firstGroup = $group->first()->totals->first()->values;
    // $groupsTotal = $group->totals->first()->values->where('column_id', 1);
    return $group;
    $total = $dataset->totals->first();
  
    return $total->values->first();
});

Auth::routes();

Route::get('/home', 'HomeController@index');

Route::get('parse', 'DatasetController@parse');

Auth::routes();
