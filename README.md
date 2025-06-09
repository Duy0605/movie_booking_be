# Movie Booking Backend

## Mô tả
Dự án Movie Booking Backend là một ứng dụng web mạnh mẽ, cho phép người dùng đặt vé xem phim trực tuyến một cách tiện lợi. Ứng dụng cung cấp các tính năng quản lý phim, đặt vé, thanh toán an toàn và gửi email xác nhận, mang đến trải nghiệm mượt mà cho cả người dùng và quản trị viên.
## Công nghệ sử dụng

Dự án Movie Booking Backend được xây dựng với các công nghệ và công cụ sau:

### Ngôn ngữ lập trình:
- **PHP:** Ngôn ngữ chính được sử dụng để phát triển ứng dụng.

### Framework:
- **Laravel:** Framework PHP mạnh mẽ giúp xây dựng ứng dụng web một cách nhanh chóng và hiệu quả.

### Cơ sở dữ liệu:
- **MySQL hoặc MariaDB:** Hệ quản trị cơ sở dữ liệu được sử dụng để lưu trữ thông tin về phim, vé và người dùng.

### Quản lý gói:
- **Composer:** Công cụ quản lý gói cho PHP, giúp cài đặt và quản lý các thư viện bên ngoài.

### API:
- **RESTful API:** Dự án sử dụng kiến trúc REST để xây dựng các endpoint cho việc tương tác giữa frontend và backend.

### Xác thực:
- **JWT (JSON Web Token):** Được sử dụng để xác thực người dùng và bảo vệ các endpoint của API.

## Tính năng chính

 **Quản lý phim**:
   - Dễ dàng thêm, chỉnh sửa hoặc xóa thông tin phim.
   - Cung cấp chi tiết đầy đủ về phim: tiêu đề, mô tả, lịch chiếu, thể loại, mang đến trải nghiệm khám phá phong phú.

 **Đặt vé trực tuyến**:
   - Lựa chọn phim, suất chiếu và số lượng vé chỉ với vài cú nhấp chuột.
   - Hỗ trợ chọn ghế ngồi trực quan, kèm theo kiểm tra trạng thái ghế trống theo thời gian thực.

 **Theo dõi lịch chiếu**:
   - Xem lịch chiếu chi tiết theo ngày và giờ, được cập nhật liên tục.
   - Dễ dàng nắm bắt thông tin các suất chiếu sắp tới để lên kế hoạch xem phim.

 **Xác thực người dùng**:
   - Đăng ký và đăng nhập an toàn, nhanh chóng.
   - Tận dụng JWT (JSON Web Token) để bảo mật các endpoint API, đảm bảo quyền truy cập được kiểm soát chặt chẽ.

 **Thông báo qua email**:
   - Gửi email xác nhận đặt vé tự động với đầy đủ thông tin: phim, thời gian, ghế ngồi.
   - Mang đến sự tiện lợi và chuyên nghiệp cho người dùng sau mỗi giao dịch.

 **Quản lý vé**:
   - Theo dõi và quản lý lịch sử đặt vé một cách dễ dàng.
   - Hỗ trợ hủy vé linh hoạt trong các trường hợp được phép.

 **Thanh toán qua PayOS**:
   - Tích hợp thanh toán trực tuyến an toàn, nhanh chóng qua PayOS.
   - Cung cấp thông tin giao dịch rõ ràng và xác nhận tức thì sau khi thanh toán thành công.

 **Thanh toán tự động**:
   - Tính năng thanh toán tự động qua PayOS, loại bỏ thao tác xác nhận thủ công.
   - Xử lý giao dịch liền mạch, cập nhật trạng thái vé ngay khi thanh toán hoàn tất.

 **API RESTful mạnh mẽ**:
   - Cung cấp các endpoint RESTful linh hoạt, dễ dàng tích hợp với ứng dụng frontend hoặc di động.
   - Đảm bảo hiệu suất cao và khả năng mở rộng.

 **Bảo mật tối ưu**:
    - Áp dụng các biện pháp bảo mật tiên tiến để bảo vệ dữ liệu người dùng và thông tin giao dịch.
    - Hệ thống xác thực và phân quyền chặt chẽ, đảm bảo an toàn tuyệt đối.

## Cài đặt dự án

#### Bước 1: Clone repository
Mở terminal và chạy lệnh sau để clone dự án về máy:
```bash
git clone https://github.com/Duy0605/movie_booking_be.git
```

#### Bước 2: Chuyển đến thư mục dự án
```bash
cd movie_booking_be
```

#### Bước 3: Cài đặt các phụ thuộc
Chạy lệnh sau để cài đặt tất cả các thư viện cần thiết:
```bash
composer install
```

#### Bước 4: Tạo file cấu hình
Sao chép file .env.example thành file .env:
```bash
cp .env.example .env
```

#### Bước 5: Cấu hình cơ sở dữ liệu
Mở file .env và cấu hình thông tin kết nối cơ sở dữ liệu của bạn:
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

#### Bước 6: Cấu hình Mailer
Trong file .env, bạn cũng cần cấu hình thông tin gửi email. Dưới đây là một ví dụ cấu hình cho SMTP:
```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=your_email@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### Bước 7: Cấu hình PayOS
Thêm các thông tin cấu hình cho PayOS vào file .env:
```bash
PAYOS_MERCHANT_ID=your_merchant_id
PAYOS_SECRET_KEY=your_secret_key
PAYOS_API_URL=https://api.payos.vn/v1/transaction
```
Thay thế your_merchant_id và your_secret_key bằng thông tin thực tế của bạn từ tài khoản PayOS.

#### Bước 8: Chạy migrations
Chạy lệnh sau để tạo các bảng trong cơ sở dữ liệu:
```bash
php artisan migrate
```

#### Bước 9: Khởi động server
Cuối cùng, khởi động server bằng lệnh:
```bash
php artisan serve
```

## Support

For support, please send an email to manhduc889@gmail.com.

