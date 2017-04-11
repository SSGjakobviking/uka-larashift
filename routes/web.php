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

Route::get('/', 'Auth\LoginController@showLoginForm');

Auth::routes();

Route::get('dataset/{id}/delete', 'DatasetController@destroy');
Route::resource('dataset', 'DatasetController', ['only' => [
    'index',
    'store',
]]);

// Route::get('indicator', 'IndicatorController@index');
// Route::get('indicator/{id}/edit', 'IndicatorController@edit');
Route::resource('indicator', 'IndicatorController');
// Route::post('indicator/{id}/dataset', 'IndicatorController@saveDataset');

Route::get('/home', 'HomeController@index');

Route::get('parse', 'DatasetController@parse');
