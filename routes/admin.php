<?php

/**
 * This File Under
 * - API\Admin namespace
 * - api/v1/admin prefix
 */

Route::post('sign-up', 'AuthController@signup');
Route::post('sign-in', 'AuthController@signin');

Route::group(['middleware' => ['auth:api', 'role:admin']], function () {

    Route::get('profile', 'AuthController@profile');
    Route::put('profile', 'AuthController@editProfile');

    //register new token
    Route::post('register-token', 'AuthController@createRegisterToken');

});
