<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS Paths
    |--------------------------------------------------------------------------
    |
    | Đây là các đường dẫn mà middleware CORS sẽ áp dụng.
    | Bạn có thể để '*' hoặc liệt kê rõ như: ['api/*', 'sanctum/csrf-cookie']
    |
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    |
    | Các phương thức HTTP được phép.
    | Dùng ['*'] để cho phép tất cả, hoặc ví dụ: ['GET', 'POST']
    |
    */
    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Nguồn (origin) nào được phép truy cập API.
    | Dùng ['*'] để cho phép tất cả, hoặc chỉ định như: ['http://localhost:3000']
    |
    */
    'allowed_origins' => [
        'https://movie-ticket-murex.vercel.app',
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3000',
        'http://localhost:5173',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins Patterns
    |--------------------------------------------------------------------------
    |
    | Patterns cho phép các subdomain
    |
    */
    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.vercel\.app$/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    |
    | Các header được phép gửi trong request.
    | Dùng ['*'] để cho phép tất cả.
    |
    */
    'allowed_headers' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    |
    | Các header sẽ được hiển thị cho frontend.
    |
    */
    'exposed_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Max Age
    |--------------------------------------------------------------------------
    |
    | Thời gian trình duyệt cache preflight request (OPTIONS), tính bằng giây.
    |
    */
    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Cho phép gửi cookie hoặc Authorization header.
    | Nếu bật true, bạn **không được dùng '*' trong allowed_origins**.
    |
    */
    'supports_credentials' => true,

];
