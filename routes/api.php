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

Route::group(['middleware' => 'auth:api'], function () {

    Route::get('profile', 'API\\AuthController@profile');
    Route::put('profile', 'API\\AuthController@editProfile');

});
