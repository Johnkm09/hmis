<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Rooms\RoomController;



Route::middleware('auth:sanctum')->group(function(){
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /*Route::prefix('v1')->group(function(){
        Route::apiResource('rooms',RoomController::class);
    });*/
});

require __DIR__.'/auth.php';
