<?php

use App\Http\Controllers\ApiProxyController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\SavedRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api')->group(function () {
    Route::post('/proxy',          [ApiProxyController::class, 'send']);
    Route::get('/history',         [ApiProxyController::class, 'history']);
    Route::get('/history/{apiHistory}',    [ApiProxyController::class, 'show']);
    Route::delete('/history/{apiHistory}', [ApiProxyController::class, 'destroy']);
    Route::delete('/history',      [ApiProxyController::class, 'clearAll']);

    Route::get('/collections',                [CollectionController::class, 'index']);
    Route::post('/collections',               [CollectionController::class, 'store']);
    Route::put('/collections/{collection}',   [CollectionController::class, 'update']);
    Route::delete('/collections/{collection}', [CollectionController::class, 'destroy']);

    Route::get('/saved-requests',                 [SavedRequestController::class, 'index']);
    Route::post('/saved-requests',                [SavedRequestController::class, 'store']);
    Route::post('/saved-requests/import',         [SavedRequestController::class, 'import']);
    Route::get('/saved-requests/export',          [SavedRequestController::class, 'export']);
    Route::get('/saved-requests/{savedRequest}',  [SavedRequestController::class, 'show']);
    Route::put('/saved-requests/{savedRequest}',  [SavedRequestController::class, 'update']);
    Route::delete('/saved-requests/{savedRequest}', [SavedRequestController::class, 'destroy']);
});
