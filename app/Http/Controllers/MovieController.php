<?php

namespace App\Http\Controllers;

use App\Mail\NewMovieMail;
use Illuminate\Http\Request;
use App\Models\Movie;
use App\Models\UserAccount;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon; // Added import
use Illuminate\Support\Facades\Mail;




class MovieController extends Controller
{
    // Lấy danh sách phim chưa bị xóa
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $movies = Movie::where('is_deleted', false)->paginate($perPage);

        return response()->json([
            'code' => 200,
            'message' => 'Lấy danh sách phim thành công',
            'data' => $movies
        ]);
    }


    // Tạo mới phim
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable|integer|min:1',
            'release_date' => 'nullable|date',
            'director' => 'nullable|string|max:255',
            'cast' => 'nullable|string',
            'genre' => 'nullable|string|max:255',
            'rating' => 'nullable|numeric|min:0|max:10',
            'poster_url' => 'nullable|url|max:255',
        ]);

        // Kiểm tra trùng tên phim (chưa bị xóa)
        $exists = Movie::where('title', $request->title)
            ->where('is_deleted', false)
            ->exists();

        if ($exists) {
            return response()->json([
                'code' => 409,
                'message' => 'Tên phim đã tồn tại'
            ], 409);
        }

        $movie = Movie::create([
            'movie_id' => Str::uuid()->toString(),
            'title' => $request->title,
            'description' => $request->description,
            'duration' => $request->duration,
            'release_date' => $request->release_date,
            'director' => $request->director,
            'cast' => $request->cast,
            'genre' => $request->genre,
            'rating' => $request->rating,
            'poster_url' => $request->poster_url,
            'is_deleted' => false,
        ]);
        // Gửi email cho tất cả user có email
        $users = UserAccount::whereNotNull('email')->get();
        foreach ($users as $user) {
            Mail::to($user->email)->queue(new NewMovieMail(
                $user->full_name,                  // customer_name
                $movie->title,
                $movie->description,
                $movie->release_date,
                $movie->genre,
                $movie->poster_url,
                $movie->movie_id
            ));
        }



        return response()->json([
            'code' => 201,
            'message' => 'Tạo phim thành công',
            'data' => $movie
        ]);
    }

    // Lấy chi tiết phim theo id
    public function show($id)
    {
        try {
            $movie = Movie::where('movie_id', $id)
                ->where('is_deleted', false)
                ->firstOrFail();

            return response()->json([
                'code' => 200,
                'message' => 'Lấy thông tin phim thành công',
                'data' => $movie
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => 'Phim không tồn tại'
            ]);
        }
    }

    // Cập nhật phim
    public function update(Request $request, $id)
    {
        try {
            $movie = Movie::where('movie_id', $id)
                ->where('is_deleted', false)
                ->firstOrFail();

            $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'duration' => 'nullable|integer|min:1',
                'release_date' => 'nullable|date',
                'director' => 'nullable|string|max:255',
                'cast' => 'nullable|string',
                'genre' => 'nullable|string|max:255',
                'rating' => 'nullable|numeric|min:0|max:10',
                'poster_url' => 'nullable|url|max:255',
            ]);

            if ($request->has('title')) {
                // Kiểm tra trùng tên với phim khác (không tính phim này)
                $exists = Movie::where('title', $request->title)
                    ->where('is_deleted', false)
                    ->where('movie_id', '<>', $id)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'code' => 409,
                        'message' => 'Tên phim đã tồn tại'
                    ], 409);
                }
            }

            $movie->fill($request->all());
            $movie->save();

            return response()->json([
                'code' => 200,
                'message' => 'Cập nhật phim thành công',
                'data' => $movie
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => 'Phim không tồn tại'
            ]);
        }
    }

    // Xóa mềm phim (is_deleted = true)
    public function destroy($id)
    {
        try {
            $movie = Movie::where('movie_id', $id)
                ->where('is_deleted', false)
                ->firstOrFail();

            $movie->is_deleted = true;
            $movie->save();

            return response()->json([
                'code' => 200,
                'message' => 'Xóa phim thành công (soft delete)'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => 'Phim không tồn tại'
            ]);
        }
    }
    // Khôi phục phim đã xóa mềm
    public function restore($id)
    {
        try {
            $movie = Movie::where('movie_id', $id)
                ->where('is_deleted', true)
                ->firstOrFail();

            $movie->is_deleted = false;
            $movie->save();

            return response()->json([
                'code' => 200,
                'message' => 'Khôi phục phim thành công',
                'data' => $movie
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => 'Phim không tồn tại hoặc chưa bị xóa'
            ]);
        }
    }
    // Lấy danh sách phim đã bị xóa mềm
    public function getDeletedMovies(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $deletedMovies = Movie::where('is_deleted', true)->paginate($perPage);

        return response()->json([
            'code' => 200,
            'message' => 'Lấy danh sách phim đã xoá thành công',
            'data' => $deletedMovies
        ]);
    }


    // Tìm kiếm phim theo tên
    // Tìm kiếm phim theo tên
    public function searchByTitle(Request $request)
    {
        $keyword = $request->query('title');
        $perPage = $request->input('per_page', 10);

        if (!$keyword) {
            return response()->json([
                'code' => 400,
                'message' => 'Thiếu tham số tìm kiếm tên phim'
            ]);
        }

        $movies = Movie::where('is_deleted', false)
            ->where('title', 'like', '%' . $keyword . '%')
            ->paginate($perPage);

        return response()->json([
            'code' => 200,
            'message' => 'Tìm kiếm phim theo tên thành công',
            'data' => $movies
        ]);
    }


    // Lấy danh sách phim đang chiếu
    public function getNowShowing(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $currentDate = Carbon::today();

        $movies = Movie::where('is_deleted', false)
            ->where('release_date', '<=', $currentDate)
            ->whereHas('showtimes', function ($query) use ($currentDate) {
                $query->where('is_deleted', false)
                    ->whereRaw('DATE_ADD(start_time, INTERVAL 7 DAY) >= ?', [$currentDate]);
            })
            ->with([
                'showtimes' => function ($query) use ($currentDate) {
                    $query->where('is_deleted', false)
                        ->whereRaw('DATE_ADD(start_time, INTERVAL 7 DAY) >= ?', [$currentDate])
                        ->select('showtime_id', 'movie_id', 'start_time', 'room_id'); // ✅ Thêm 'room_id'
                },
                'showtimes.room.cinema' => function ($query) {
                    $query->select('cinema_id', 'name')->where('is_deleted', false);
                }
            ])

            ->select('movie_id', 'title', 'description', 'duration', 'release_date', 'director', 'cast', 'genre', 'rating', 'poster_url', 'is_deleted')
            ->paginate($perPage);

        // Transform the response to include cinema and showtime information
        $movies->getCollection()->transform(function ($movie) {
            $cinemas = $movie->showtimes->map(function ($showtime) {
                return [
                    'cinema_id' => $showtime->room->cinema->cinema_id ?? null,
                    'name' => $showtime->room->cinema->name ?? null,
                ];
            })->filter()->unique('cinema_id')->values();

            $showtimes = $movie->showtimes->map(function ($showtime) {
                return [
                    'showtime_id' => $showtime->showtime_id,
                    'start_time' => $showtime->start_time->toIso8601String(),
                ];
            });

            return [
                'movie_id' => $movie->movie_id,
                'title' => $movie->title,
                'description' => $movie->description,
                'duration' => $movie->duration,
                'release_date' => $movie->release_date,
                'director' => $movie->director,
                'cast' => $movie->cast,
                'genre' => $movie->genre,
                'rating' => $movie->rating,
                'poster_url' => $movie->poster_url,
                'is_deleted' => $movie->is_deleted,
                'cinemas' => $cinemas,
                'showtimes' => $showtimes,
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => 'Lấy danh sách phim đang chiếu thành công',
            'data' => $movies
        ], 200);
    }

    // Lấy danh sách phim sắp chiếu
    public function getUpcomingMovie(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $currentDate = Carbon::today();

        $movies = Movie::where('is_deleted', false)
            ->where('release_date', '>', $currentDate)
            ->whereHas('showtimes', function ($query) use ($currentDate) {
                $query->where('is_deleted', false)
                    ->whereRaw('DATE_ADD(start_time, INTERVAL 7 DAY) < ?', [$currentDate]);
            })
            ->with([
                'showtimes' => function ($query) use ($currentDate) {
                    $query->where('is_deleted', false)
                        ->whereRaw('DATE_ADD(start_time, INTERVAL 7 DAY) >= ?', [$currentDate])
                        ->select('showtime_id', 'movie_id', 'start_time', 'room_id'); // ✅ Thêm 'room_id'
                },
                'showtimes.room.cinema' => function ($query) {
                    $query->select('cinema_id', 'name')->where('is_deleted', false);
                }
            ])

            ->select('movie_id', 'title', 'description', 'duration', 'release_date', 'director', 'cast', 'genre', 'rating', 'poster_url', 'is_deleted')
            ->paginate($perPage);

        // Transform the response to include cinema and showtime information
        $movies->getCollection()->transform(function ($movie) {
            $cinemas = $movie->showtimes->map(function ($showtime) {
                return [
                    'cinema_id' => $showtime->room->cinema->cinema_id ?? null,
                    'name' => $showtime->room->cinema->name ?? null,
                ];
            })->filter()->unique('cinema_id')->values();

            $showtimes = $movie->showtimes->map(function ($showtime) {
                return [
                    'showtime_id' => $showtime->showtime_id,
                    'start_time' => $showtime->start_time->toIso8601String(),
                ];
            });

            return [
                'movie_id' => $movie->movie_id,
                'title' => $movie->title,
                'description' => $movie->description,
                'duration' => $movie->duration,
                'release_date' => $movie->release_date,
                'director' => $movie->director,
                'cast' => $movie->cast,
                'genre' => $movie->genre,
                'rating' => $movie->rating,
                'poster_url' => $movie->poster_url,
                'is_deleted' => $movie->is_deleted,
                'cinemas' => $cinemas,
                'showtimes' => $showtimes,
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => 'Lấy danh sách phim sắp chiếu thành công',
            'data' => $movies
        ], 200);
    }

    // Method for getAllMovies (if needed, same as index but renamed for clarity)
    public function getAllMovies(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);

        $movies = Movie::where('is_deleted', false)
            ->whereHas('showtimes', function ($query) {
                $query->where('is_deleted', false);
            })
            ->with([
                'showtimes.room.cinema' => function ($query) {
                    $query->select('cinema_id', 'name')->where('is_deleted', false);
                }
            ])
            ->select('movie_id', 'title', 'description', 'duration', 'release_date', 'director', 'cast', 'genre', 'rating', 'poster_url', 'is_deleted')
            ->paginate($perPage, ['*'], 'page', $page);

        // Transform the response to include cinema and showtime information
        $movies->getCollection()->transform(function ($movie) {
            $cinemas = $movie->showtimes->map(function ($showtime) {
                return [
                    'cinema_id' => $showtime->room->cinema->cinema_id ?? null,
                    'name' => $showtime->room->cinema->name ?? null,
                ];
            })->filter()->unique('cinema_id')->values();

            $showtimes = $movie->showtimes->map(function ($showtime) {
                return [
                    'showtime_id' => $showtime->showtime_id,
                    'start_time' => $showtime->start_time->toIso8601String(),
                ];
            });

            return [
                'movie_id' => $movie->movie_id,
                'title' => $movie->title,
                'description' => $movie->description,
                'duration' => $movie->duration,
                'release_date' => $movie->release_date,
                'director' => $movie->director,
                'cast' => $movie->cast,
                'genre' => $movie->genre,
                'rating' => $movie->rating,
                'poster_url' => $movie->poster_url,
                'is_deleted' => $movie->is_deleted,
                'cinemas' => $cinemas,
                'showtimes' => $showtimes,
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => 'Lấy danh sách phim thành công',
            'data' => $movies,
        ], 200);
    }

    public function searchByTitleFE(Request $request)
    {
        // Lấy tham số từ query string
        $title = $request->query('title', ''); // Chuỗi tìm kiếm tiêu đề
        $perPage = $request->query('per_page', 10); // Số bản ghi mỗi trang
        $page = $request->query('page', 1); // Trang hiện tại

        // Xây dựng query tìm kiếm
        $movies = Movie::where('is_deleted', false)
            ->where('title', 'LIKE', '%' . $title . '%') // Tìm kiếm tiêu đề không phân biệt hoa thường
            ->whereHas('showtimes', function ($query) {
                $query->where('is_deleted', false);
            })
            ->with([
                'showtimes.room.cinema' => function ($query) {
                    $query->select('cinema_id', 'name')->where('is_deleted', false);
                }
            ])
            ->select('movie_id', 'title', 'description', 'duration', 'release_date', 'director', 'cast', 'genre', 'rating', 'poster_url', 'is_deleted')
            ->paginate($perPage, ['*'], 'page', $page);

        // Transform dữ liệu để định dạng phản hồi
        $movies->getCollection()->transform(function ($movie) {
            $cinemas = $movie->showtimes->map(function ($showtime) {
                return [
                    'cinema_id' => $showtime->room->cinema->cinema_id ?? null,
                    'name' => $showtime->room->cinema->name ?? null,
                ];
            })->filter()->unique('cinema_id')->values();

            $showtimes = $movie->showtimes->map(function ($showtime) {
                return [
                    'showtime_id' => $showtime->showtime_id,
                    'start_time' => $showtime->start_time->toIso8601String(),
                ];
            });

            return [
                'movie_id' => $movie->movie_id,
                'title' => $movie->title,
                'description' => $movie->description,
                'duration' => $movie->duration,
                'release_date' => $movie->release_date,
                'director' => $movie->director,
                'cast' => $movie->cast,
                'genre' => $movie->genre,
                'rating' => $movie->rating,
                'poster_url' => $movie->poster_url,
                'is_deleted' => $movie->is_deleted,
                'cinemas' => $cinemas,
                'showtimes' => $showtimes,
            ];
        });

        // Trả về phản hồi JSON
        return response()->json([
            'code' => 200,
            'message' => 'Tìm kiếm phim theo tiêu đề thành công',
            'data' => $movies,
        ], 200);
    }
}
