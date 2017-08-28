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

Route::group(['middleware' => ['api']], function() {

    Route::get('indicators', 'IndicatorController@index');

    Route::get('totals/{indicator}', 'TotalsController@index')->name('totals');

    Route::get('search/{indicator}/{query}', 'SearchController@index');
});

// Route::group(['prefix' => 'indicator-groups', 'middleware' => 'api'], function() {

//     Route::get('/', function(Request $request) {
//         $apiPath = storage_path('app/api/indicator-groups/');

//         $indicatorGroups = include $apiPath. 'all.php';

//         return $indicatorGroups;
//     });

// });

// Route::middleware('api')->get('/registrerade-studenter/totals', function(Request $request) {
//     $apiPath = storage_path('app/api/');
//     $studenter = include $apiPath.'registrerade-studenter.php';

//     return $studenter['year_totals'];
// });
