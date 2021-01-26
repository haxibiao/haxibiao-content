<?php

use Illuminate\Support\Facades\Route;

//相似问答
Route::middleware('auth:api')->get('/suggest-question', 'IssueController@suggest');
//问答
Route::middleware('auth:api')->get('/question/{id}', 'IssueController@question');
// 举报
Route::middleware('auth:api')->get('/report-question-{id}', 'IssueController@reportQuestion');
// 收藏问题，内逻辑同收藏文章...
Route::middleware('auth:api')->get('/favorite-question-{id}', 'IssueController@favoriteQuestion');
// 回答
Route::middleware('auth:api')->get('/answer/{id}', 'IssueController@answer');
// 回答下按钮操作
Route::middleware('auth:api')->get('/like-answer-{id}', 'IssueController@likeAnswer');
Route::middleware('auth:api')->get('/unlike-answer-{id}', 'IssueController@unlikeAnswer');
Route::middleware('auth:api')->get('/report-answer-{id}', 'IssueController@reportAnswer');
Route::middleware('auth:api')->get('/delete-answer-{id}', 'IssueController@deleteAnswer');
// 邀请列表
Route::get('/question-{id}-uninvited', 'IssueController@questionUninvited');
// 点邀请
Route::middleware('auth:api')->get('/question-{qid}-invite-user-{id}', 'IssueController@questionInvite');
Route::middleware('auth:api')->post('/question-{id}-answered', 'IssueController@answered');
//删除问题
Route::middleware('auth:api')->get('/delete-question-{id}', 'IssueController@delete');

//commend question
Route::get('/commend-question', 'IssueController@commend');
