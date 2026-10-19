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
Route::get('/admin/items/create', 'ItemController@CreateItem');
// アイテム詳細
Route::get('/admin/items/detail/{id}', 'ItemController@ShowItemDetail');
// アイテム登録
Route::post('/admin/items/register', 'ItemController@RegisterItem');
// アイテム編集
Route::post('/admin/items/edit', 'ItemController@EditItem');
// アイテム削除
Route::delete('/admin/items/delete/', 'ItemController@DeleteItem');
// アイテム名称自動入力
Route::get('item_autocomplete', 'ItemAutoCompleteController@searchItem');
// アイテム価格取得
Route::get('item_getprice', 'ItemAutoCompleteController@getItemPrice');

// 注文一覧
Route::get('/admin/orders', 'OrderController@index');
// 注文新規作成
Route::get('/admin/orders/create', 'OrderController@CreateOrder');
// 注文詳細
Route::get('/admin/orders/detail/{id}', 'OrderController@ShowOrderDetail');
// 注文登録
Route::post('/admin/orders/register', 'OrderController@RegisterOrder');
// 注文編集
Route::post('/admin/orders/edit', 'OrderController@EditOrder');
// 注文削除
Route::delete('/admin/orders/delete/', 'OrderController@DeleteOrder');


// 現金出納帳一覧
Route::get('/admin/cashier', 'CashierController@index');
// 現金出納帳新規作成
Route::get('/admin/cashier/create', 'CashierController@CreateCashier');
// 現金出納帳詳細
Route::get('/admin/cashier/detail/{id}', 'CashierController@ShowCashierDetail');
// 現金出納帳登録
Route::post('/admin/cashier/register', 'CashierController@RegisterCashier');
// 現金出納帳編集
Route::post('/admin/cashier/edit/{id}', 'CashierController@EditCashier');
// 現金出納帳削除
Route::delete('/admin/cashier/delete/', 'CashierController@DeleteCashier');

// レポート
Route::get('/admin/report', 'ReportController@index');