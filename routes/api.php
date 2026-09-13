<?php

use App\Http\Controllers\Api\V1\Room\RoomController;
use App\Http\Controllers\Api\V1\RoomType\RoomTypeController;
use App\Http\Controllers\Api\V1\Guest\GuestController;
use App\Http\Controllers\Api\V1\Reservation\ReservationController;
use App\Http\Controllers\Api\V1\Operation\OperationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::apiResource('room-types', RoomTypeController::class);
        Route::apiResource('rooms', RoomController::class);
        Route::apiResource('guests', GuestController::class);
        Route::apiResource('reservations', ReservationController::class);

        // Operations
        Route::prefix('operations')->group(function () {
            Route::post('/walk-in', [OperationController::class, 'walkIn']);
            Route::post('/check-in', [OperationController::class, 'checkIn']);
            Route::post('/check-out', [OperationController::class, 'checkOut']);
        });
    });
});

require __DIR__ . '/auth.php';
