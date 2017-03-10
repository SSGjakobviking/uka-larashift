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
    return 'URL till api: <a href="' . url("api/totals/1/2008") . '">' . url('api/totals/1/2008') . '</a>';
});

Auth::routes();

Route::get('/home', 'HomeController@index');

Route::get('parse', 'DatasetController@parse');

Auth::routes();
