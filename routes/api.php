<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => [
    // 'api',
    'cors',
    ]], function() {

    Route::get('indicators', 'IndicatorController@all');

    Route::get('totals/{indicator}', 'TotalsController@index')->name('totals');

    Route::get('search/{indicator}/{query}', 'SearchController@index');
});