<?php

use Illuminate\Support\Facades\Route;

//管理cms防腾讯拦截地址
Route::post('/cms/redirect_urls', 'CmsController@putRedirectUrls');
Route::get('/cms/redirect_urls', 'CmsController@getRedirectUrls');
