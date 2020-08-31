<?php

declare (strict_types = 1);

use Haxibiao\Content\Controllers\ArticleController;
use Haxibiao\Content\Controllers\CategoryController;
use Illuminate\Contracts\Routing\Registrar as RouteRegisterContract;
use Illuminate\Support\Facades\Route;
/**
 * Category
 */
Route::group(['prefix' => 'category'], function (RouteRegisterContract $route) {
    //管理专题
    Route::get('/list', CategoryController::class . '@list');
});

Route::resource('/category', CategoryController::class);

/**
 * Article
 */
//动态
Route::post('/post/new', ArticleController::class .'@storePost');
//文章
Route::get('/drafts', ArticleController::class .'@drafts');
Route::resource('/article', ArticleController::class);
//因为APP二维码分享用了 /post/{id}
Route::resource('/post', ArticleController::class);
Route::any('/share/post/{id}',ArticleController::class .'@shareVideo');

/**
 * 问答
 */
Route::resource('/question', 'IssueController');
Route::resource('/answer', 'SolutionController');
Route::get('/categories-for-question', 'IssueController@categories');
Route::get('/question-bonused', 'IssueController@bonused');
Route::post('/question-add', 'IssueController@add')->name('question.add');

//TODO 这个里面还有梗,注意这个category的匹配顺序
//Route::get('/{name_en}', CategoryController::class.'@name_en')->where('name_en', '(?!nova).*');
