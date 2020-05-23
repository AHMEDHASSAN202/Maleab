<?php

/**
 * This File Under
 * - API\Admin namespace
 * - api/v1/admin prefix
 */

Route::post('login', 'AuthController@login');

Route::group(['middleware' => ['auth:api', 'role:admin']], function () {

    Route::apiResource('user', 'UserController');

    Route::get('profile', 'AuthController@profile');

    //register new token
    Route::post('register-token', 'AuthController@createRegisterToken');

});
