<?php

use Illuminate\Support\Facades\Route;

Route::get('/category/{id}', 'CategoryController@show');

#专题下视频
Route::any('/category/{category_id}/videos', 'CategoryController@getCategoryVideos');

//编辑专题
Route::post('/category/new-logo', 'CategoryController@newLogo');
Route::post('/category/{id}/edit-logo', 'CategoryController@editLogo');
Route::post('/category/{id}', 'CategoryController@update');

Route::get('/category/recommend-categories', 'CategoryController@page');

//分类
Route::get('/category', 'CategoryController@index');

//首页分类推荐vue(兼容旧网站,发布动态，选择专题)
Route::get('/categories', 'CategoryController@index');
Route::get('/recommend-categories', 'CategoryController@page');

//搜索专题
Route::get('/category/search-submit-for-article-{aid}', 'CategoryController@search');

//专题投稿
Route::get('/category/check-category-{id}', 'CategoryController@checkCategory');
//投稿、撤销投稿
Route::get('/category/{aid}/submit-category-{cid}', 'CategoryController@submitCategory');
//收录，移除
Route::get('/category/{aid}/add-category-{cid}', 'CategoryController@addCategory');
//批准、拒绝、移除投稿请求
Route::get('/category/approve-category-{cid}-{aid}', 'CategoryController@approveCategory');
//文章加入推荐专题
Route::get('/category/recommend-check-article-{aid}', 'CategoryController@recommendCategoriesCheckArticle');
//文章加入管理的专题
Route::get('/category/admin-check-article-{aid}', 'CategoryController@adminCategoriesCheckArticle');
//新投稿请求列表
Route::get('/category/new-requested', 'CategoryController@newReuqestCategories');
//全部未处理投稿请求
Route::get('/category/pending-articles', 'CategoryController@pendingArticles');
//单个专题的所有投稿请求列表
Route::get('/category/requested-articles-{cid}', 'CategoryController@requestedArticles');
