<?php

use App\Http\Controllers\Api\V1\Room\RoomController;
use App\Http\Controllers\Api\V1\RoomType\RoomTypeController;
use App\Http\Controllers\Api\V1\Guest\GuestController;
use App\Http\Controllers\Api\V1\Reservation\ReservationController;
use App\Http\Controllers\Api\V1\Operation\OperationController;
use App\Http\Controllers\Api\V1\Service\ServiceController;
use App\Http\Controllers\Api\V1\Folio\FolioController;
use App\Http\Controllers\Api\V1\Folio\FolioChargeController;
use App\Http\Controllers\Api\V1\Payment\PaymentController;
use App\Http\Controllers\Api\V1\Payment\RefundController;
use App\Http\Controllers\Api\V1\Payment\InvoiceController;
use App\Http\Controllers\Api\V1\Payment\ReceiptController;
use App\Http\Controllers\Api\V1\Payment\MpesaController;
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
        Route::prefix('operations')->group(function () {
            Route::post('/walk-in', [OperationController::class, 'walkIn']);
            Route::post('/check-in', [OperationController::class, 'checkIn']);
            Route::post('/check-out', [OperationController::class, 'checkOut']);
        });
        Route::apiResource('services', ServiceController::class);
        Route::post('reservations/{reservation}/folio', [FolioController::class, 'store']);
        Route::get('reservations/{reservation}/folio', [FolioController::class, 'byReservation']);
        Route::get('folios/{folio}', [FolioController::class, 'show']);
        Route::post('folios/{folio}/close', [FolioController::class, 'close']);
        Route::get('folios/{folio}/charges', [FolioChargeController::class, 'index']);
        Route::post('folios/{folio}/charges', [FolioChargeController::class, 'store']);
        Route::get('folio-charges/{id}', [FolioChargeController::class, 'show']);
        Route::put('folio-charges/{id}', [FolioChargeController::class, 'update']);
        Route::get('folios/{folio}/payments', [PaymentController::class, 'index']);
        Route::post('folios/{folio}/payments', [PaymentController::class, 'store']);
        Route::get('payments/{id}', [PaymentController::class, 'show']);
        Route::patch('payments/{id}', [PaymentController::class, 'update']);
        Route::get('payments/{payment}/refunds', [RefundController::class, 'index']);
        Route::post('payments/{payment}/refunds', [RefundController::class, 'store']);
        Route::get('refunds/{id}', [RefundController::class, 'show']);
        Route::patch('refunds/{id}', [RefundController::class, 'update']);
        Route::get('invoices', [InvoiceController::class, 'index']);
        Route::post('invoices', [InvoiceController::class, 'store']);
        Route::get('invoices/{id}', [InvoiceController::class, 'show']);
        Route::patch('invoices/{id}', [InvoiceController::class, 'update']);
        Route::get('receipts', [ReceiptController::class, 'index']);
        Route::post('receipts', [ReceiptController::class, 'store']);
        Route::get('receipts/{id}', [ReceiptController::class, 'show']);
        Route::patch('receipts/{id}', [ReceiptController::class, 'update']);

        Route::post('mpesa/stk-push', [MpesaController::class, 'stkPush']);
        Route::post('mpesa/query/{payment}', [MpesaController::class, 'query']);
    });
    Route::post('mpesa/callback', [MpesaController::class, 'callback']);
});

require __DIR__ . '/auth.php';
