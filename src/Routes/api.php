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
Route::group(['prefix' => 'model'], function () {
    Route::get('/names', 'CrudController@models');
});

Route::group(['prefix' => 'crud'], function () {
    Route::post('/{model}', 'CrudController@store');
    Route::get('/{model}', 'CrudController@list');
    Route::get('/{model}/{id}', 'CrudController@show');
    Route::delete('/{model}/{id}', 'CrudController@destroy');
    Route::put('/{model}/{id}', 'CrudController@update');
});

Route::middleware('auth:api')->get('/crud', function (Request $request) {
    return $request->user();
});
