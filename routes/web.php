<?php

declare (strict_types = 1);

use Illuminate\Contracts\Routing\Registrar as RouteRegisterContract;
use Illuminate\Support\Facades\Route;

//搜索
Route::get('/searchQuery', 'SearchController@searchQuery');
Route::get('/search', 'SearchController@search');
Route::get('/search/users', 'SearchController@searchUsers');
Route::get('/search/movies', 'SearchController@searchMovies');
Route::get('/search/video', 'SearchController@searchVideos');
Route::get('/search/categories', 'SearchController@searchCategories');
Route::get('/search/collections', 'SearchController@searchCollections');

//管理专题
Route::get('/category/list', 'CategoryController@list');
Route::middleware(config('content.category.middleware', []))->resource('/category', 'CategoryController');

// 动态
Route::resource('/post', 'PostController');
//因为APP二维码分享用了 /post/{id} - 需要兼容article 查询
// Route::resource('/post', 'ArticleController');

/**
 * Article
 */
//文章动态
Route::post('/post/new', 'ArticleController@storePost');

//文章
Route::get('/drafts', 'ArticleController@drafts');
Route::middleware(config('content.article.middleware', []))->resource('/article', 'ArticleController');
Route::any('/share/post/{id}', 'ArticleController@shareVideo');

Route::resource('/collection', 'CollectionController');
Route::get('/share/collection/{id}', 'CollectionController@shareCollection');
/**
 * 问答
 */
Route::post('/question/updateBackground', 'IssueController@add')->name('question.updateBackground');
Route::resource('/issue', 'IssueController');
Route::resource('/answer', 'SolutionController');
Route::get('/categories-for-question', 'IssueController@categories');
Route::get('/question-bonused', 'IssueController@bonused');

/**
 * 创作编辑器
 */
Route::view('/write', 'write')->middleware('auth');

//调试和日志查看
Route::get('/logshow', 'LogController@logShow');
Route::get('/logclear', 'LogController@logClear');
Route::get('/debug', 'LogController@debug');

/**
 * 站点seo管理
 */
Route::group(['prefix' => 'seo'], function (RouteRegisterContract $route) {
    // 百度收录查询
    Route::get('/baidu', 'SeoController@baiduInclude');
    Route::get('/baidu/include', 'SeoController@baiduInclude');
    //收录反馈查询
    Route::get('/pushResult', 'SeoController@pushResult');
});

//站点地图索引
Route::get('/sitemap', 'SitemapController@index');
Route::get('/sitemap.xml', 'SitemapController@index');
//单个地图
Route::get('/sitemap/{name_en}', 'SitemapController@name_en');

// robots
Route::get('/robots.txt', 'SeoController@robot');

// robots
Route::get('/shenma-site-verification.txt', 'SeoController@verification');
Route::get('/sogousiteverification.txt', 'SeoController@verification');
