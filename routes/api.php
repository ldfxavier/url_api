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
Route::group(['namespace' => 'Api', 'as' => 'api.'], function () {
    //Autenticação
    Route::post('/auth/login', 'AuthController@login')->name('login');

    //Usuário
    Route::post('/user', 'UserController@store')->name('user.store');

    //Url
    Route::get('/content/url', 'UrlController@content')->name('url.content');

    Route::group(['middleware' => ['apiProtected']], function () {
        //Autenticação
        Route::get('/auth/logout', 'AuthController@logout')->name('auth.logout');
        Route::get('/auth/update-token', 'AuthController@refresh')->name('auth.update.token');
        Route::get('/auth/me', 'AuthController@me')->name('auth.me');

        //Usuário
        Route::get('/user', 'UserController@index')->name('user.index');
        Route::get('/user/{id}', 'UserController@show')->name('user.show');
        Route::put('/user', 'UserController@update')->name('user.update');
        Route::put('/user/password', 'UserController@password')->name('user.password');

        //Url
        Route::post('/url', 'UrlController@store')->name('url.store');
        Route::get('/url', 'UrlController@index')->name('url.index');
        Route::get('/url/{id}', 'UrlController@show')->name('url.show');
        Route::put('/url/{id}', 'UrlController@update')->name('url.update');
    });
});
