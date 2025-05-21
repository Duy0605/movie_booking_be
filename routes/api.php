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

use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

// Room ++
Route::prefix('rooms')->group(function () {
    Route::get('/search', [RoomController::class, 'searchByRoomName']);                         // Tìm kiếm phòng theo tên
    Route::get('/', [RoomController::class, 'index']);                                          // Lấy danh sách phòng chưa bị xóa
    Route::post('/', [RoomController::class, 'store']);                                         // Tạo mới một phòng
    Route::get('/deleted', [RoomController::class, 'getDeletedRooms']);                         // Lấy danh sách phòng đã xóa mềm
    Route::get('/cinema/{cinema_id}', [RoomController::class, 'getRoomsByCinema']);             // Lấy danh sách phòng theo ID rạp
    Route::get('{id}', [RoomController::class, 'show']);                                        // Lấy thông tin một phòng theo ID
    Route::put('{id}', [RoomController::class, 'update']);                                      // Cập nhật thông tin phòng
    Route::put('update-capacity/{id}', [RoomController::class, 'updateCapacity']);              // Cập nhật sức chứa phòng
    Route::delete('soft-delete/{id}', [RoomController::class, 'softDelete']);                   // DELETE mềm
    Route::patch('restore/{id}', [RoomController::class, 'restore']);                           // Khôi phục phòng bị xóa mềm
    Route::delete('{id}', [RoomController::class, 'destroy']);                                  // DELETE vĩnh viễn
});

// Seat
Route::prefix('seats')->group(function () {
    Route::get('/search', [SeatController::class, 'searchSeatsBySeatNumber']);                       // Tìm kiếm ghế theo số ghế
    Route::get('/', [SeatController::class, 'index']);                                          // Lấy danh sách tất cả ghế
    Route::post('/', [SeatController::class, 'store']);                                         // Tạo mới ghế
    Route::get('/{id}', [SeatController::class, 'show']);                                       // Lấy thông tin một ghế theo ID
    Route::get('/room/{id}/seats', [SeatController::class, 'showSeatByRoomId']);                // Lấy danh sách ghế theo ID phòng
    Route::put('/{id}', [SeatController::class, 'update']);                                     // Cập nhật thông tin ghế
    Route::delete('/soft/{id}', [SeatController::class, 'softDelete']);                         // Xóa mềm ghế
    Route::patch('/restore/{id}', [SeatController::class, 'restore']);                          // Khôi phục ghế đã xóa mềm
    Route::delete('/{id}', [SeatController::class, 'destroy']);                                 // Xóa vĩnh viễn ghế
    Route::post('/batch', [SeatController::class, 'storeMultiple']);                            // Tạo mới nhiều ghế

});

// Review
Route::prefix('reviews')->group(function () {
    Route::get('/', [ReviewController::class, 'index']);                                        // Lấy danh sách tất cả đánh giá
    Route::post('/', [ReviewController::class, 'store']);                                       // Tạo mới đánh giá
    Route::get('/{id}', [ReviewController::class, 'show']);                                     // Lấy thông tin một đánh giá theo ID
    Route::put('/{id}', [ReviewController::class, 'update']);                                   // Cập nhật đánh giá
    Route::delete('/{id}', [ReviewController::class, 'destroy']);                               // Xóa đánh giá
    Route::get('/reviews/movie/{movieId}', [ReviewController::class, 'getReviewsByMovie']);     // Lấy danh sách đánh giá theo ID phim
    Route::get('/reviews/user/{userId}', [ReviewController::class, 'getReviewsByUser']);        // Lấy danh sách đánh giá theo ID người dùng

});

// Booking ++
Route::prefix('bookings')->group(function () {
    Route::get('/search-by-phone', [BookingController::class, 'searchBookingByPhoneNumber']);          // Tìm kiếm booking theo số điện thoại
    Route::get('/', [BookingController::class, 'index']);                                       // Lấy danh sách tất cả booking
    Route::post('/', [BookingController::class, 'store']);                                      // Tạo mới booking
    Route::get('/{id}', [BookingController::class, 'show']);                                    // Lấy thông tin một booking theo ID
    Route::get('/userId/{id}', [BookingController::class, 'showByUserId']);                     // Lấy danh sách booking theo ID người dùng
    Route::put('/{id}', [BookingController::class, 'update']);                                  // Cập nhật booking
    Route::delete('/soft/{id}', [BookingController::class, 'destroy']);                         // Xóa mềm booking
    Route::patch('/restore/{id}', [BookingController::class, 'restore']);                       // Khôi phục booking
    Route::delete('/{id}', [BookingController::class, 'forceDelete']);                          // Xóa vĩnh viễn booking
    Route::put('/bookings/{id}/total-price', [BookingController::class, 'updateTotalPrice']);   // Cập nhật tổng tiền booking

});

// Movie
Route::prefix('movies')->group(function () {
    Route::get('/', [MovieController::class, 'index']);                                         // Lấy danh sách tất cả phim
    Route::post('/', [MovieController::class, 'store']);                                        // Tạo mới phim
    Route::get('/{id}', [MovieController::class, 'show']);                                      // Lấy thông tin 1 phim theo ID
    Route::put('/{id}', [MovieController::class, 'update']);                                    // Cập nhật phim
    Route::delete('/soft/{id}', [MovieController::class, 'destroy']);                           // DELETE mềm
    Route::patch('/restore/{id}', [MovieController::class, 'restore']);                         // Khôi phục phim đã bị xóa mềm
    Route::get('/movies/deleted', [MovieController::class, 'getDeletedMovies']);                // Lấy danh sách phim đã xóa mềm
    Route::get('/movies/search', [MovieController::class, 'searchByTitle']);                    // Tìm kiếm phim theo tiêu đề
    Route::get('/movies/now-showing', [MovieController::class, 'getNowShowing']);               // Lấy danh sách phim đang chiếu
    Route::get('/movies/upcoming-movie', [MovieController::class, 'getUpcomingMovie']);         // Lấy danh sách phim sắp chiếu
});

// ShowTime
Route::prefix('showtimes')->group(function () {
    Route::get('/', [ShowTimeController::class, 'index']);                                      // Lấy danh sách tất cả lịch chiếu
    Route::post('/', [ShowTimeController::class, 'store']);                                     // Tạo mới lịch chiếu
    Route::get('/{id}', [ShowTimeController::class, 'show']);                                   // Lấy thông tin 1 lịch chiếu theo ID
    Route::put('/{id}', [ShowTimeController::class, 'update']);                                 // Cập nhật lịch chiếu
    Route::delete('/soft/{id}', [ShowTimeController::class, 'destroy']);                        // Xóa mềm lịch chiếu
    Route::patch('/restore/{id}', [ShowTimeController::class, 'restore']);                      // Khôi phục lịch chiếu
    Route::get('/movieId/{id}', [ShowTimeController::class, 'showByMovieId']);                  // Lấy danh sách lịch chiếu theo ID phim
});

// Payment
Route::prefix('payment')->group(function () {
    Route::get('/', [PaymentController::class, 'index']);                                        // Lấy danh sách tất cả giao dịch
    Route::post('/', [PaymentController::class, 'store']);                                       // Tạo mới giao dịch
    Route::get('/{id}', [PaymentController::class, 'show']);                                     // Lấy thông tin một giao dịch theo ID
    Route::put('/{id}', [PaymentController::class, 'update']);                                   // Cập nhật giao dịch
    Route::delete('/{id}', [PaymentController::class, 'destroy']);                               // Xóa mềm giao dịch
    Route::patch('/payments/{id}/complete', [PaymentController::class, 'markCompleted']);        // Đánh dấu giao dịch là đã hoàn thành
    Route::post('/proxy-payos', [PaymentController::class, 'proxyPayOS']);                       // Tạo giao dịch mới
    Route::get('/proxy-payos/{id}', [PaymentController::class, 'getPaymentLinkInfo']);           // Lấy thông tin giao dịch theo ID
});

// BookingSeat
Route::prefix('booking-seats')->group(function () {
    Route::get('/showtimes/{showtimeId}/seats', [BookingSeatController::class, 'getSeatsByShowtime']);  // Lấy danh sách ghế theo ID lịch chiếu
    Route::get('/', [BookingSeatController::class, 'index']);                                           // Lấy danh sách tất cả ghế đã đặt
    Route::post('/', [BookingSeatController::class, 'store']);                                          // Tạo mới ghế đã đặt
    Route::get('/{id}', [BookingSeatController::class, 'show']);                                        // Lấy thông tin một ghế đã đặt theo ID
    Route::put('/{id}', [BookingSeatController::class, 'update']);                                      // Cập nhật ghế đã đặt
    Route::delete('/soft/{id}', [BookingSeatController::class, 'destroy']);                             // Xóa mềm ghế đã đặt
    Route::delete('/{id}', [BookingSeatController::class, 'forceDelete']);                              // Xóa cứng ghế đã đặt
    Route::delete('/{id}/seats', [BookingSeatController::class, 'forceDelete']);                        // Xóa cứng ghế đã đặt
    Route::patch('/restore/{id}', [BookingSeatController::class, 'restore']);                           // Khôi phục ghế đã đặt
});

// Coupon ++
Route::prefix('coupons')->group(function () {
    Route::get('/', [CouponController::class, 'index']);                                         // Lấy danh sách tất cả mã giảm giá
    Route::post('/', [CouponController::class, 'store']);                                        // Tạo mới mã giảm giá
    Route::get('/{id}', [CouponController::class, 'show']);                                      // Lấy thông tin một mã giảm giá theo ID
    Route::put('/{id}', [CouponController::class, 'update']);                                    // Cập nhật mã giảm giá
    Route::delete('/soft/{id}', [CouponController::class, 'softDelete']);                        // soft delete
    Route::delete('/{id}', [CouponController::class, 'forceDelete']);                            // xóa cứng
    Route::patch('/restore/{id}', [CouponController::class, 'restore']);                         // khôi phục xóa mềm
});

// Cinema
Route::prefix('cinemas')->group(function () {
    Route::get('/deleted', [CinemaController::class, 'getDeleted']);                            // Lấy danh sách rạp đã xóa mềm
    Route::get('/', [CinemaController::class, 'index']);                                        // Lấy danh sách tất cả rạp chiếu
    Route::post('/', [CinemaController::class, 'store']);                                       // Thêm rạp mới
    Route::get('/{id}', [CinemaController::class, 'show']);                                     // Lấy thông tin 1 rạp theo ID
    Route::put('/{id}', [CinemaController::class, 'update']);                                   // Cập nhật rạp
    Route::delete('/{id}', [CinemaController::class, 'destroy']);                               // Xoá mềm rạp
    Route::put('/restore/{id}', [CinemaController::class, 'restore']);                          // Khôi phục rạp
});

// User ++
Route::prefix('users')->group(function () {
    Route::get('/', [UserAccountController::class, 'index']);                                   // Lấy danh sách người dùng
    Route::post('/', [UserAccountController::class, 'store']);                                  // Tạo người dùng mới
    Route::get('/{id}', [UserAccountController::class, 'show']);                                // Lấy chi tiết thông tin người dùng
    Route::put('/{id}', [UserAccountController::class, 'update']);                              // Cập nhật thông tin người dùng
    Route::delete('/soft/{id}', [UserAccountController::class, 'destroy']);                     // Xoá mềm người dùng
    Route::patch('/restore/{id}', [UserAccountController::class, 'restore']);                   // Khôi phục người dùng
    Route::delete('/{id}', [UserAccountController::class, 'forceDelete']);                      // Xoá vĩnh viễn người dùng
});

// Auth
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);                       // Đăng ký người dùng
    Route::post('/login', [AuthController::class, 'login']);                             // Đăng nhập người dùng
});

// Setting
Route::prefix('setting')->group(function () {
    Route::get('/', [SettingController::class, 'show']);                                 // Lấy thông tin cài đặt
    Route::put('/', [SettingController::class, 'update']);                               // Cập nhật cài đặt
});