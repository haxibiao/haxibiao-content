<?php

declare (strict_types = 1);

use haxibiao\content\Controllers\Api\CategoryController;
use Illuminate\Contracts\Routing\Registrar as RouteRegisterContract;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api'], function (RouteRegisterContract $api) {

    $api->group(['prefix' => 'category'], function (RouteRegisterContract $api) {

        Route::get('/{id}', CategoryController::class . '@show');

        #专题下视频
        Route::any('/{category_id}/videos', CategoryController::class . '@getCategoryVideos');

        $api->group(['middleware' => 'auth:api'], function (RouteRegisterContract $api) {

            //编辑专题
            Route::post('/new-logo', CategoryController::class . '@newLogo');
            Route::post('/{id}/edit-logo', CategoryController::class . '@editLogo');
            Route::post('/{id}', CategoryController::class . '@update');

        });
    });

    Route::get('recommend-categories', CategoryController::class . '@page');

    $api->group(['prefix' => 'categories'], function (RouteRegisterContract $api) {

        //分类
        Route::get('/', CategoryController::class . '@index');
        //搜索专题
        Route::get('/search-submit-for-article-{aid}', CategoryController::class . '@search');

        $api->group(['middleware' => 'auth:api'], function (RouteRegisterContract $api) {

            //专题投稿
            Route::get('/check-category-{id}', CategoryController::class . '@checkCategory');
            //投稿、撤销投稿
            Route::get('/{aid}/submit-category-{cid}', CategoryController::class . '@submitCategory');
            //收录，移除
            Route::get('/{aid}/add-category-{cid}', CategoryController::class . '@addCategory');
            //批准、拒绝、移除投稿请求
            Route::get('/approve-category-{cid}-{aid}', CategoryController::class . '@approveCategory');
            //文章加入推荐专题
            Route::get('/recommend-check-article-{aid}', CategoryController::class . '@recommendCategoriesCheckArticle');
            //文章加入管理的专题
            Route::get('/admin-check-article-{aid}', CategoryController::class . '@adminCategoriesCheckArticle');
            //新投稿请求列表
            Route::get('/new-requested', CategoryController::class . '@newReuqestCategories');
            //全部未处理投稿请求
            Route::get('/pending-articles', CategoryController::class . '@pendingArticles');
            //单个专题的所有投稿请求列表
            Route::get('/requested-articles-{cid}', CategoryController::class . '@requestedArticles');

        });
    });

});
