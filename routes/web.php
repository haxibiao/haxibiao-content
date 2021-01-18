<?php

declare (strict_types = 1);

use Illuminate\Contracts\Routing\Registrar as RouteRegisterContract;
use Illuminate\Support\Facades\Route;

/**
 * Category
 */
Route::group(['prefix' => 'category'], function (RouteRegisterContract $route) {
    //管理专题
    Route::get('/list', 'CategoryController@list');
});

Route::resource('/category', 'CategoryController');

/**
 * Article
 */
//动态
Route::post('/post/new', 'ArticleController@storePost');
//文章
Route::get('/drafts', 'ArticleController@drafts');
Route::resource('/article', 'ArticleController');
//因为APP二维码分享用了 /post/{id}
Route::resource('/post', 'ArticleController');
Route::any('/share/post/{id}', 'ArticleController@shareVideo');

Route::get('/share/collection/{id}', 'CollectionController@shareCollection');
/**
 * 问答
 */
Route::post('/question/updateBackground', 'IssueController@add')->name('question.updateBackground');
Route::resource('/question', 'IssueController');
Route::resource('/answer', 'SolutionController');
Route::get('/categories-for-question', 'IssueController@categories');
Route::get('/question-bonused', 'IssueController@bonused');

//创作
Route::middleware('auth')->get('/write', 'ArticleController@write');

//TODO 这个里面还有梗,注意这个category的匹配顺序
//Route::get('/{name_en}', CategoryController::class.'@name_en')->where('name_en', '(?!nova).*');
