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
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

// Các route không yêu cầu xác thực
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']); // Đăng ký người dùng
    Route::post('/login', [AuthController::class, 'login']);       // Đăng nhập người dùng
});

// Các route công khai (không yêu cầu JWT)
Route::prefix('movies')->group(function () {
    Route::get('/', [MovieController::class, 'index']);                    // Lấy danh sách tất cả phim
    Route::get('/get-all-movies', [MovieController::class, 'getAllMovies']);    // Lấy danh sách tất cả phim (bao gồm đã xóa mềm)
    Route::get('/search', [MovieController::class, 'searchByTitleFE']);    // Tìm kiếm phim theo tiêu đề (frontend)
    Route::get('/now-showing', [MovieController::class, 'getNowShowing']); // Lấy danh sách phim đang chiếu
    Route::get('/upcoming-movie', [MovieController::class, 'getUpcomingMovie']); // Lấy danh sách phim sắp chiếu
    Route::get('/{id}', [MovieController::class, 'show']);                 // Lấy thông tin 1 phim theo ID
});

Route::prefix('showtimes')->group(function () {
    Route::get('/', [ShowTimeController::class, 'index']);                 // Lấy danh sách tất cả lịch chiếu
    Route::get('/{id}', [ShowTimeController::class, 'show']);              // Lấy thông tin 1 lịch chiếu theo ID
    Route::get('/search', [ShowTimeController::class, 'searchShowtimes']); // Tìm kiếm lịch chiếu
    Route::get('/movieId/{id}', [ShowTimeController::class, 'showByMovieId']); // Lấy danh sách lịch chiếu theo ID phim
    Route::get('/cinema/{cinema_id}/date/{date}', [ShowTimeController::class, 'filterByCinemaAndDate']);
});

Route::prefix('cinemas')->group(function () {
    Route::get('/', [CinemaController::class, 'index']);                   // Lấy danh sách tất cả rạp chiếu
    Route::get('/{id}', [CinemaController::class, 'show']);                // Lấy thông tin 1 rạp theo ID
    Route::get('/search-by-address', [CinemaController::class, 'searchCinemaByAddress']); // Tìm kiếm rạp theo địa chỉ
    Route::get('/search-by-name', [CinemaController::class, 'searchCinemaByName']);       // Tìm kiếm rạp theo tên
});

Route::prefix('reviews')->group(function () {
    Route::get('/', [ReviewController::class, 'index']);                   // Lấy danh sách tất cả đánh giá
    Route::get('/{id}', [ReviewController::class, 'show']);                // Lấy thông tin một đánh giá theo ID
    Route::get('/reviews/movie/{movieId}', [ReviewController::class, 'getReviewsByMovie']); // Lấy danh sách đánh giá theo ID phim
});

Route::prefix('setting')->group(function () {
    Route::get('/', [SettingController::class, 'show']);                        // Lấy thông tin cài đặt
});

// Các route yêu cầu xác thực JWT
Route::middleware('auth:api')->group(function () {
    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'getDashboardData']); // Lấy dữ liệu cho Admin Dashboard
    });

    // Room
    Route::prefix('rooms')->group(function () {
        Route::get('/search', [RoomController::class, 'searchByRoomName']);         // Tìm kiếm phòng theo tên
        Route::get('/', [RoomController::class, 'index']);                          // Lấy danh sách phòng chưa bị xóa
        Route::post('/', [RoomController::class, 'store']);                         // Tạo mới một phòng
        Route::get('/deleted', [RoomController::class, 'getDeletedRooms']);         // Lấy danh sách phòng đã xóa mềm
        Route::get('/cinema/{cinema_id}', [RoomController::class, 'getRoomsByCinema']); // Lấy danh sách phòng theo ID rạp
        Route::get('{id}', [RoomController::class, 'show']);                        // Lấy thông tin một phòng theo ID
        Route::put('{id}', [RoomController::class, 'update']);                      // Cập nhật thông tin phòng
        Route::put('update-capacity/{id}', [RoomController::class, 'updateCapacity']); // Cập nhật sức chứa phòng
        Route::put('soft-delete/{id}', [RoomController::class, 'softDelete']);      // DELETE mềm
        Route::patch('restore/{id}', [RoomController::class, 'restore']);           // Khôi phục phòng bị xóa mềm
        Route::delete('{id}', [RoomController::class, 'destroy']);                  // DELETE vĩnh viễn
    });

    // Seat
    Route::prefix('seats')->group(function () {
        Route::get('/', [SeatController::class, 'index']);                          // Lấy danh sách tất cả ghế
        Route::post('/', [SeatController::class, 'store']);                         // Tạo mới ghế
        Route::get('/{id}', [SeatController::class, 'show']);                       // Lấy thông tin một ghế theo ID
        Route::get('/roomId/{id}', [SeatController::class, 'showSeatByRoomId']);    // Lấy danh sách ghế theo ID phòng
        Route::put('/{id}', [SeatController::class, 'update']);                     // Cập nhật thông tin ghế
        Route::delete('/soft/{id}', [SeatController::class, 'softDelete']);         // Xóa mềm ghế
        Route::patch('/restore/{id}', [SeatController::class, 'restore']);          // Khôi phục ghế đã xóa mềm
        Route::delete('/{id}', [SeatController::class, 'destroy']);                 // Xóa vĩnh viễn ghế
        Route::post('/batch', [SeatController::class, 'storeMultiple']);            // Tạo mới nhiều ghế
        Route::post('/softDeleteMultipe', [SeatController::class, 'softDeleteMultiple']); // Xóa mềm nhiều ghế
    });

    // Review
    Route::prefix('reviews')->group(function () {
        Route::post('/', [ReviewController::class, 'store']);                       // Tạo mới đánh giá
        Route::put('/{id}', [ReviewController::class, 'update']);                   // Cập nhật đánh giá
        Route::delete('/{id}', [ReviewController::class, 'destroy']);               // Xóa đánh giá
        Route::get('/reviews/user/{userId}', [ReviewController::class, 'getReviewsByUser']); // Lấy danh sách đánh giá theo ID người dùng
    });

    // Booking
    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);                       // Lấy danh sách tất cả booking chưa bị xóa mềm
        Route::post('/', [BookingController::class, 'store']);                      // Tạo mới booking
        Route::get('/search', [BookingController::class, 'searchBooking']);         // Tìm kiếm bookings
        Route::get('/{id}', [BookingController::class, 'show']);                    // Lấy thông tin một booking theo ID
        Route::get('/userId/{id}', [BookingController::class, 'showByUserId']);     // Lấy danh sách booking theo ID người dùng
        Route::put('/{id}', [BookingController::class, 'update']);                  // Cập nhật thông tin booking
        Route::put('/{id}/order-code', [BookingController::class, 'updateOrderCode']); // Update order code
        Route::put('/{booking_id}/coupon', [BookingController::class, 'updateCoupon']); // Cập nhật mã giảm giá cho booking
        Route::patch('/{bookingId}/barcode', [BookingController::class, 'updateBarcode']); // Cập nhật mã vạch cho booking
        Route::delete('/soft/{id}', [BookingController::class, 'destroy']);         // Xóa mềm booking
        Route::patch('/restore/{id}', [BookingController::class, 'restore']);       // Khôi phục booking
        Route::delete('/{id}', [BookingController::class, 'forceDelete']);          // Xóa vĩnh viễn booking
        Route::put('/bookings/{id}/total-price', [BookingController::class, 'updateTotalPrice']); // Cập nhật tổng giá tiền của booking
    });

    // Movie
    Route::prefix('movies')->group(function () {
        Route::post('/', [MovieController::class, 'store']);                        // Tạo mới phim
        Route::put('/{id}', [MovieController::class, 'update']);                    // Cập nhật phim
        Route::delete('/soft/{id}', [MovieController::class, 'destroy']);           // Xóa mềm phim
        Route::patch('/restore/{id}', [MovieController::class, 'restore']);         // Khôi phục phim đã bị xóa mềm
        Route::get('/deleted', [MovieController::class, 'getDeletedMovies']);       // Lấy danh sách phim đã xóa mềm
    });

    // ShowTime
    Route::prefix('showtimes')->group(function () {
        Route::post('/', [ShowTimeController::class, 'store']);                     // Tạo mới lịch chiếu
        Route::put('/{id}', [ShowTimeController::class, 'update']);                 // Cập nhật lịch chiếu
        Route::delete('/soft/{id}', [ShowTimeController::class, 'destroy']);        // Xóa mềm lịch chiếu
        Route::patch('/restore/{id}', [ShowTimeController::class, 'restore']);      // Khôi phục lịch chiếu
    });

    // Payment
    Route::prefix('payment')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);                       // Lấy danh sách tất cả giao dịch
        Route::post('/', [PaymentController::class, 'store']);                      // Tạo mới giao dịch
        Route::get('/{id}', [PaymentController::class, 'show']);                    // Lấy thông tin một giao dịch theo ID
        Route::put('/{id}', [PaymentController::class, 'update']);                  // Cập nhật giao dịch
        Route::delete('/{id}', [PaymentController::class, 'destroy']);              // Xóa mềm giao dịch
        Route::patch('/payments/{id}/complete', [PaymentController::class, 'markCompleted']); // Đánh dấu giao dịch là đã hoàn thành
        Route::post('/proxy-payos', [PaymentController::class, 'proxyPayOS']);       // Tạo giao dịch mới
        Route::get('/proxy-payos/{id}', [PaymentController::class, 'getPaymentLinkInfo']); // Lấy thông tin giao dịch theo ID
    });

    // BookingSeat
    Route::prefix('booking-seats')->group(function () {
        Route::get('/showtimes/{showtimeId}/seats', [BookingSeatController::class, 'getSeatsByShowtime']); // Lấy danh sách ghế theo ID lịch chiếu
        Route::get('/', [BookingSeatController::class, 'index']);                   // Lấy danh sách tất cả ghế đã đặt
        Route::post('/', [BookingSeatController::class, 'store']);                  // Tạo mới ghế đã đặt
        Route::post('/lock', [BookingSeatController::class, 'lockSeat']);           // Khóa ghế
        Route::post('/unlock', [BookingSeatController::class, 'unlockSeat']);       // Mở khóa ghế
        Route::get('/{id}', [BookingSeatController::class, 'show']);                // Lấy thông tin một ghế đã đặt theo ID
        Route::put('/{id}', [BookingSeatController::class, 'update']);              // Cập nhật ghế đã đặt
        Route::delete('/soft/{id}', [BookingSeatController::class, 'destroy']);     // Xóa mềm ghế đã đặt
        Route::delete('/{id}', [BookingSeatController::class, 'forceDelete']);      // Xóa cứng ghế đã đặt
        Route::delete('/{id}/seats', [BookingSeatController::class, 'forceDelete']); // Xóa cứng ghế đã đặt
        Route::patch('/restore/{id}', [BookingSeatController::class, 'restore']);   // Khôi phục ghế đã đặt
    });

    // Coupon
    Route::prefix('coupons')->group(function () {
        Route::get('/search/code', [CouponController::class, 'searchCouponByCode']); // Tìm kiếm mã giảm giá theo mã
        Route::get('/', [CouponController::class, 'index']);                        // Lấy danh sách tất cả mã giảm giá
        Route::post('/', [CouponController::class, 'store']);                       // Tạo mới mã giảm giá
        Route::get('/{id}', [CouponController::class, 'show']);                     // Lấy thông tin một mã giảm giá theo ID
        Route::get('/deleted', [CouponController::class, 'getDeletedCoupons']);     // Lấy danh sách mã giảm giá đã xóa mềm
        Route::put('/{id}', [CouponController::class, 'update']);                   // Cập nhật mã giảm giá
        Route::delete('/soft/{id}', [CouponController::class, 'softDelete']);       // soft delete
        Route::delete('/{id}', [CouponController::class, 'forceDelete']);           // xóa cứng
        Route::patch('/restore/{id}', [CouponController::class, 'restore']);        // khôi phục xóa mềm
        Route::post('{coupon_id}/usage', [CouponController::class, 'updateUsage']);
        Route::get('/search/exact-code', [CouponController::class, 'searchByExactCode']);
    });

    // Cinema
    Route::prefix('cinemas')->group(function () {
        Route::post('/', [CinemaController::class, 'store']);                       // Thêm rạp mới
        Route::put('/{id}', [CinemaController::class, 'update']);                   // Cập nhật rạp
        Route::delete('/{id}', [CinemaController::class, 'destroy']);               // Xoá mềm rạp
        Route::put('/restore/{id}', [CinemaController::class, 'restore']);          // Khôi phục rạp
        Route::get('/deleted', [CinemaController::class, 'getDeleted']);            // Lấy danh sách rạp đã xóa mềm
    });

    // User
    Route::prefix('users')->group(function () {
        Route::get('/search', [UserAccountController::class, 'search']);            // Tìm kiếm người dùng theo tên hoặc số điện thoại
        Route::get('/', [UserAccountController::class, 'index']);                   // Lấy danh sách người dùng
        Route::post('/', [UserAccountController::class, 'store']);                  // Tạo người dùng mới
        Route::get('/{id}', [UserAccountController::class, 'show']);                // Lấy chi tiết thông tin người dùng
        Route::put('/{id}', [UserAccountController::class, 'update']);              // Cập nhật thông tin người dùng
        Route::delete('/soft/{id}', [UserAccountController::class, 'destroy']);     // Xoá mềm người dùng
        Route::patch('/restore/{id}', [UserAccountController::class, 'restore']);   // Khôi phục người dùng
        Route::delete('/{id}', [UserAccountController::class, 'forceDelete']);      // Xoá vĩnh viễn người dùng
        Route::post('/{id}/change-password', [UserAccountController::class, 'changePassword']); // Đổi mật khẩu người dùng
        Route::post('/forgot-password', [UserAccountController::class, 'forgotPassword']); // Quên mật khẩu
    });

    // Setting
    Route::prefix('setting')->group(function () {
        Route::put('/', [SettingController::class, 'update']);                      // Cập nhật cài đặt
    });
});