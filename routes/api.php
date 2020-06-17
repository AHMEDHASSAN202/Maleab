<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});


Route::post('verify-register-token', 'API\\AuthController@verifyRegisterToken');

Route::post('sign-up', 'API\\AuthController@signup');
Route::post('sign-in', 'API\\AuthController@signin');
Route::post('reset-password', 'API\\AuthController@resetPassword');
Route::post('reset-password/change', 'API\\AuthController@resetPasswordChange');

Route::get('playground', 'API\\PlaygroundController@index');
Route::get('playground/{playground_id}', 'API\\PlaygroundController@show');

Route::group(['middleware' => ['auth:api', 'role:user,playground']], function () {

    Route::get('profile', 'API\\AuthController@profile');
    Route::put('profile', 'API\\AuthController@editProfile');
    Route::delete('profile', 'API\\AuthController@deleteProfile');

});

Route::group(['middleware' => ['auth:api', 'role:playground']], function () {
    Route::delete('playground/images', 'API\\PlaygroundController@deleteImage');
});

Route::post('reservation', 'API\\ReservationsController@store')->middleware('auth:api');
Route::get('reservation/{id}', 'API\\ReservationsController@show')->middleware('auth:api');
Route::put('reservation/{reservation_id}', 'API\\ReservationsController@update')->middleware('auth:api');
Route::get('reservation', 'API\\ReservationsController@index');
