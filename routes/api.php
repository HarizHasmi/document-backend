<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MasterDataController;

Route::prefix('v1')->group(function () {

    Route::post('/register',[AuthController::class,'register']);
    Route::post('/login',[AuthController::class,'login']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout',[AuthController::class,'logout']);
        Route::get('/user',fn(Request $r)=>$r->user());

        Route::get('/documents',[DocumentController::class,'index']);
        Route::get('/documents/{document}',[DocumentController::class,'show']);
        Route::post('/documents',[DocumentController::class,'store']);
        Route::patch('/documents/{document}',[DocumentController::class,'update']);
        Route::delete('/documents/{document}',[DocumentController::class,'destroy']);
        Route::get('/documents/{document}/download',[DocumentController::class,'download']);

        Route::get('/departments',[MasterDataController::class,'departments']);
        Route::get('/categories',[MasterDataController::class,'categories']);
    });
});
