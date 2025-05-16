<?php
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\ReviewController;

Route::prefix('rooms')->group(function () {
    Route::get('/', [RoomController::class, 'index']);             // GET /api/rooms
    Route::post('/', [RoomController::class, 'store']);            // POST /api/rooms
    Route::get('{id}', [RoomController::class, 'show']);           // GET /api/rooms/{id}
    Route::put('{id}', [RoomController::class, 'update']); 
    Route::put('update-capacity/{id}', [RoomController::class, 'updateCapacity']);  // PUT /api/rooms/{id}
    Route::delete('soft-delete/{id}', [RoomController::class, 'softDelete']); // DELETE mềm
    Route::patch('restore/{id}', [RoomController::class, 'restore']);          // PATCH khôi phục
    Route::delete('{id}', [RoomController::class, 'destroy']);     // DELETE vĩnh viễn
});

Route::prefix('seats')->group(function () {
    Route::get('/', [SeatController::class, 'index']);
    Route::post('/', [SeatController::class, 'store']);
    Route::get('/{id}', [SeatController::class, 'show']);
    Route::put('/{id}', [SeatController::class, 'update']);
    Route::delete('/soft/{id}', [SeatController::class, 'softDelete']);
    Route::put('/restore/{id}', [SeatController::class, 'restore']);
    Route::delete('/{id}', [SeatController::class, 'destroy']);
    Route::post('/batch', [SeatController::class, 'storeMultiple']);
});



Route::prefix('reviews')->group(function () {
    Route::get('/', [ReviewController::class, 'index']);
    Route::post('/', [ReviewController::class, 'store']);
    Route::get('/{id}', [ReviewController::class, 'show']);
    Route::put('/{id}', [ReviewController::class, 'update']);
    Route::delete('/{id}', [ReviewController::class, 'destroy']);
});

