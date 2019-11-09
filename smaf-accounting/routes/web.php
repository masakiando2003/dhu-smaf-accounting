<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// ダッシュボード
Route::get('/', 'AdminController@RedirectToDashboard');
Route::get('/admin/home', 'AdminController@index');

// アイテム一覧
Route::get('/admin/items', 'ItemController@index');
// アイテム新規作成
Route::get('/admin/items/create', 'ItemController@Create');
// アイテム詳細
Route::get('/admin/items/detail', 'ItemController@Show');

// 注文一覧
Route::get('/admin/orders', 'OrderController@index');
// 注文新規作成
Route::get('/admin/orders/create', 'OrderController@Create');
// 注文詳細
Route::get('/admin/orders/detail', 'OrderController@Show');

// 現金出納帳一覧
Route::get('/admin/cashier', 'CashierController@index');
// 現金出納帳新規作成
Route::get('/admin/cashier/create', 'CashierController@Create');
// 現金出納帳詳細
Route::get('/admin/cashier/detail', 'CashierController@Show');

// レポート
Route::get('/admin/report', 'ReportController@index');