<?php

use App\Http\Controllers\RoomController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ShowTimeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\BookingSeatController;
use App\Http\Controllers\CinemaController;
use App\Http\Controllers\UserAccountController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SettingController;

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
    Route::get('/reviews/movie/{movieId}', [ReviewController::class, 'getReviewsByMovie']);
    Route::get('/reviews/user/{userId}', [ReviewController::class, 'getReviewsByUser']);

});

Route::prefix('bookings')->group(function () {
    Route::get('/', [BookingController::class, 'index']);              // GET /api/bookings
    Route::post('/', [BookingController::class, 'store']);             // POST /api/bookings
    Route::get('/{id}', [BookingController::class, 'show']);           // GET /api/bookings/{id}
    Route::put('/{id}', [BookingController::class, 'update']);         // PUT /api/bookings/{id}
    Route::delete('/soft/{id}', [BookingController::class, 'destroy']); // Xóa mềm booking
    Route::patch('/restore/{id}', [BookingController::class, 'restore']); // Khôi phục booking
    Route::delete('/{id}', [BookingController::class, 'forceDelete']); // Xóa vĩnh viễn booking

    Route::put('/bookings/{id}/total-price', [BookingController::class, 'updateTotalPrice']);
});

Route::prefix('movies')->group(function () {
    Route::get('/', [MovieController::class, 'index']);               // GET /api/movies
    Route::post('/', [MovieController::class, 'store']);              // POST /api/movies
    Route::get('/{id}', [MovieController::class, 'show']);            // GET /api/movies/{id}
    Route::put('/{id}', [MovieController::class, 'update']);          // PUT /api/movies/{id}
    Route::delete('/soft/{id}', [MovieController::class, 'destroy']);  // DELETE mềm
    Route::patch('/restore/{id}', [MovieController::class, 'restore']);   // PATCH khôi phục
    Route::get('/movies/deleted', [MovieController::class, 'getDeletedMovies']);
    Route::get('/movies/search', [MovieController::class, 'searchByTitle']);
    Route::get('/movies/search', [MovieController::class, 'searchByTitle']);
    Route::get('/movies/now-showing', [MovieController::class, 'getNowShowing']);
    Route::get('/movies/upcoming-movie', [MovieController::class, 'getUpcomingMovie']);
});


Route::prefix('showtimes')->group(function () {
    Route::get('/', [ShowTimeController::class, 'index']);
    Route::post('/', [ShowTimeController::class, 'store']);
    Route::get('/{id}', [ShowTimeController::class, 'show']);
    Route::put('/{id}', [ShowTimeController::class, 'update']);
    Route::delete('/soft/{id}', [ShowTimeController::class, 'destroy']);
    Route::get('/movieId/{id}', [ShowTimeController::class, 'showByMovieId']);


});




Route::prefix('payment')->group(function () {
    Route::get('/', [PaymentController::class, 'index']);
    Route::post('/', [PaymentController::class, 'store']);
    Route::get('/{id}', [PaymentController::class, 'show']);
    Route::put('/{id}', [PaymentController::class, 'update']);
    Route::delete('/{id}', [PaymentController::class, 'destroy']);
    Route::patch('/payments/{id}/complete', [PaymentController::class, 'markCompleted']);
    Route::post('/proxy-payos', [PaymentController::class, 'proxyPayOS']);

});
Route::prefix('booking-seats')->group(function () {
    Route::get('/showtimes/{showtimeId}/seats', [BookingSeatController::class, 'getSeatsByShowtime']);
    Route::get('/', [BookingSeatController::class, 'index']);
    Route::post('/', [BookingSeatController::class, 'store']);
    Route::get('/{id}', [BookingSeatController::class, 'show']);
    Route::put('/{id}', [BookingSeatController::class, 'update']);
    Route::delete('/soft/{id}', [BookingSeatController::class, 'destroy']);
    Route::delete('/{id}', [BookingSeatController::class, 'forceDelete']);
    Route::delete('/{id}/seats', [BookingSeatController::class, 'forceDelete']);

});


Route::prefix('coupons')->group(function () {
    Route::get('/', [CouponController::class, 'index']);
    Route::post('/', [CouponController::class, 'store']);
    Route::get('/{id}', [CouponController::class, 'show']);
    Route::put('/{id}', [CouponController::class, 'update']);
    Route::delete('/soft/{id}', [CouponController::class, 'softDelete']); // nếu dùng soft delete
    Route::delete('/{id}', [CouponController::class, 'forceDelete']); // xóa cứng
});

Route::prefix('cinemas')->group(function () {
    Route::get('/', [CinemaController::class, 'index']);           // Lấy danh sách tất cả rạp chiếu
    Route::post('/', [CinemaController::class, 'store']);          // Thêm rạp mới
    Route::get('/{id}', [CinemaController::class, 'show']);        // Lấy thông tin 1 rạp theo ID
    Route::put('/{id}', [CinemaController::class, 'update']);      // Cập nhật rạp
    Route::delete('/{id}', [CinemaController::class, 'destroy']);  // Xoá mềm rạp
});

Route::prefix('users')->group(function () {
    Route::get('/', [UserAccountController::class, 'index']);        // Danh sách người dùng
    Route::post('/', [UserAccountController::class, 'store']);       // Tạo người dùng mới
    Route::get('{id}', [UserAccountController::class, 'show']);      // Lấy chi tiết người dùng
    Route::put('{id}', [UserAccountController::class, 'update']);    // Cập nhật người dùng
    Route::delete('{id}', [UserAccountController::class, 'destroy']); // Xoá mềm người dùng
});

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/setting', [SettingController::class, 'show']);
Route::put('/setting', [SettingController::class, 'update']);