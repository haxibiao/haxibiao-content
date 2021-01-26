<?php

use Illuminate\Support\Facades\Route;

//文章 api
Route::get('/article', 'ArticleController@index');
Route::get('/article/{id}', 'ArticleController@show');
Route::get('/article/{id}/likes', 'ArticleController@likes');

Route::middleware('auth:api')->post('/article/create-post', 'ArticleController@createPost');
Route::middleware('auth:api')->post('/article/create', 'ArticleController@store');
Route::middleware('auth:api')->put('/article/{id}/update', 'ArticleController@update');
Route::middleware('auth:api')->delete('/article/{id}', 'ArticleController@delete');
Route::middleware('auth:api')->get('/article/{id}/restore', 'ArticleController@restore');
Route::middleware('auth:api')->get('/article/{id}/destroy', 'ArticleController@destroy');
Route::middleware('auth:api')->get('/article-trash', 'ArticleController@trash');
