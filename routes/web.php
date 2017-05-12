<?php

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

// Authentication Routes...
Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

// Registration Routes...
Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
Route::post('register', 'Auth\RegisterController@register');

// Password Reset Routes...
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset');

Route::resource('dataset', 'DatasetController');
Route::get('dataset/{id}/delete', 'DatasetController@destroy');
Route::get('dataset/{id}/unattach', 'DatasetController@unAttach');
Route::post('dataset/addTag', 'DatasetController@addTag');
Route::post('dataset/deleteTag', 'DatasetController@deleteTag');

// Route::get('indicator', 'IndicatorController@index');
// Route::get('indicator/{id}/edit', 'IndicatorController@edit');
Route::resource('indicator', 'IndicatorController');
Route::post('indicator/{id}/dataset', 'IndicatorController@saveDataset');

Route::resource('users', 'UserController');
Route::get('users/{id}/delete', 'UserController@destroy');

Route::get('/home', 'HomeController@index');

Route::get('parse', 'DatasetController@parse');

Route::get('search', 'SearchController@index');

