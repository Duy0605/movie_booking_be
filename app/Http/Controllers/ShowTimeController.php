<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ShowTime;
use App\Models\Movie;
use App\Response\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowTimeController extends Controller
{
    // Lấy danh sách suất chiếu chưa bị xóa 
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Số bản ghi/trang, mặc định 10

        $showtimes = ShowTime::with([
            'movie' => function ($query) {
                $query->select('movie_id', 'title', 'duration')->where('is_deleted', false);
            },
            'room.cinema' => function ($query) {
                $query->select('cinema_id', 'name')->where('is_deleted', false);
            }
        ])
            ->where('is_deleted', false)
            ->paginate($perPage);

        return response()->json([
            'code' => 200,
            'message' => 'Lấy danh sách suất chiếu thành công',
            'data' => $showtimes
        ]);
    }

    // Lọc suất chiếu theo rạp và ngày
    public function filterByCinemaAndDate($cinema_id, $date, Request $request)
    {
        $request->merge(['cinema_id' => $cinema_id, 'date' => $date]);

        $request->validate([
            'cinema_id' => 'required|string|exists:cinema,cinema_id',
            'date' => 'required|date_format:Y-m-d',
            'per_page' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
        ]);

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $query = ShowTime::with([
            'movie' => function ($query) {
                $query->select('movie_id', 'title', 'duration')->where('is_deleted', false);
            },
            'room.cinema' => function ($query) {
                $query->select('cinema_id', 'name')->where('is_deleted', false);
            }
        ])
            ->where('is_deleted', false)
            ->whereHas('room', function ($query) use ($cinema_id) {
                $query->where('cinema_id', $cinema_id)->where('is_deleted', false);
            })
            ->whereDate('start_time', Carbon::parse($date)->toDateString());

        $showtimes = $query->paginate($perPage, ['*'], 'page', $page);

        if ($showtimes->isEmpty()) {
            return response()->json([
                'code' => 404,
                'message' => 'Không tìm thấy suất chiếu cho rạp và ngày này'
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Lấy danh sách suất chiếu theo rạp và ngày thành công',
            'data' => $showtimes
        ]);
    }

    // Tạo mới suất chiếu
    public function store(Request $request)
    {
        $request->validate([
            'movie_id' => 'required|string|exists:movie,movie_id',
            'start_time' => 'required|date',
            'room_id' => 'required|string|exists:room,room_id',
            'price' => 'required|numeric|min:0',
        ]);

        // Lấy phim để biết duration
        $movie = Movie::where('movie_id', $request->movie_id)
            ->where('is_deleted', false)
            ->select('movie_id', 'title', 'duration')
            ->first();
        if (!$movie) {
            return response()->json([
                'code' => 404,
                'message' => 'Phim không tồn tại'
            ], 404);
        }

        $startTime = Carbon::parse($request->start_time);
        $endTime = (clone $startTime)->addMinutes($movie->duration);

        // Kiểm tra trùng suất chiếu trong cùng phòng
        $conflict = ShowTime::where('room_id', $request->room_id)
            ->where('is_deleted', false)
            ->get()
            ->filter(function ($showtime) use ($startTime, $endTime) {
                $movieDuration = $showtime->movie->duration ?? 0;
                $existingStart = Carbon::parse($showtime->start_time);
                $existingEnd = (clone $existingStart)->addMinutes($movieDuration);

                return $startTime < $existingEnd && $endTime > $existingStart;
            })
            ->isNotEmpty();

        if ($conflict) {
            return response()->json([
                'code' => 400,
                'message' => 'Phòng đã có suất chiếu trong khung giờ này.'
            ], 400);
        }

        $showtime = ShowTime::create([
            'showtime_id' => Str::uuid()->toString(),
            'movie_id' => $request->movie_id,
            'start_time' => $request->start_time,
            'room_id' => $request->room_id,
            'price' => $request->price,
            'is_deleted' => false,
        ]);

        $showtime->load([
            'movie' => function ($query) {
                $query->select('movie_id', 'title', 'duration')->where('is_deleted', false);
            },
            'room.cinema' => function ($query) {
                $query->select('cinema_id', 'name')->where('is_deleted', false);
            }
        ]);

        return response()->json([
            'code' => 201,
            'message' => 'Tạo suất chiếu thành công',
            'data' => $showtime
        ]);
    }

    // Lấy thông tin một suất chiếu cụ thể
    public function show($id)
    {
        try {
            $showtime = ShowTime::with([
                'movie' => function ($query) {
                    $query->select('movie_id', 'title', 'duration')->where('is_deleted', false);
                },
                'room.cinema' => function ($query) {
                    $query->select('cinema_id', 'name')->where('is_deleted', false);
                }
            ])
                ->where('showtime_id', $id)
                ->where('is_deleted', false)
                ->firstOrFail();

            return response()->json([
                'code' => 200,
                'message' => 'Lấy thông tin suất chiếu thành công',
                'data' => $showtime
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => 'Suất chiếu không tồn tại'
            ], 404);
        }
    }

    public function showByMovieId(Request $request, $id)
    {
        $perPage = $request->input('per_page', 10);

        $futureTime = Carbon::now()->addMinutes(30); 
        $maxTime = Carbon::now()->addDays(7); 

        $showtimes = ShowTime::with([
            'movie' => function ($query) {
                $query->select('movie_id', 'title', 'duration')->where('is_deleted', false);
            },
            'room.cinema' => function ($query) {
                $query->select('cinema_id', 'name')->where('is_deleted', false);
            }
        ])
            ->where('movie_id', $id)
            ->where('is_deleted', false)
            ->where('start_time', '>=', $futureTime)
            ->where('start_time', '<=', $maxTime) 
            ->paginate($perPage);

        if ($showtimes->isEmpty()) {
            return response()->json([
                'code' => 404,
                'message' => 'Không tìm thấy suất chiếu'
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Lấy thông tin suất chiếu thành công',
            'data' => $showtimes
        ]);
    }

    // Cập nhật thông tin suất chiếu
    public function update(Request $request, $id)
    {
        try {
            $showtime = ShowTime::where('showtime_id', $id)
                ->where('is_deleted', false)
                ->firstOrFail();

            $request->validate([
                'movie_id' => 'sometimes|string|exists:movie,movie_id',
                'start_time' => 'sometimes|date',
                'room_id' => 'sometimes|string|exists:room,room_id',
                'price' => 'sometimes|numeric|min:0',
            ]);

            // Kiểm tra trùng suất chiếu nếu cập nhật start_time hoặc room_id
            if ($request->has('start_time') || $request->has('room_id')) {
                $movie = Movie::where('movie_id', $request->movie_id ?? $showtime->movie_id)
                    ->where('is_deleted', false)
                    ->select('movie_id', 'title', 'duration')
                    ->first();
                if (!$movie) {
                    return response()->json([
                        'code' => 404,
                        'message' => 'Phim không tồn tại'
                    ], 404);
                }

                $startTime = Carbon::parse($request->start_time ?? $showtime->start_time);
                $endTime = (clone $startTime)->addMinutes($movie->duration);

                $conflict = ShowTime::where('room_id', $request->room_id ?? $showtime->room_id)
                    ->where('showtime_id', '!=', $id)
                    ->where('is_deleted', false)
                    ->get()
                    ->filter(function ($existingShowtime) use ($startTime, $endTime) {
                        $movieDuration = $existingShowtime->movie->duration ?? 0;
                        $existingStart = Carbon::parse($existingShowtime->start_time);
                        $existingEnd = (clone $existingStart)->addMinutes($movieDuration);

                        return $startTime < $existingEnd && $endTime > $existingStart;
                    })
                    ->isNotEmpty();

                if ($conflict) {
                    return response()->json([
                        'code' => 400,
                        'message' => 'Phòng đã có suất chiếu trong khung giờ này.'
                    ], 400);
                }
            }

            $showtime->fill($request->all());
            $showtime->save();

            // Eager load relationships for response
            $showtime->load([
                'movie' => function ($query) {
                    $query->select('movie_id', 'title', 'duration')->where('is_deleted', false);
                },
                'room.cinema' => function ($query) {
                    $query->select('cinema_id', 'name')->where('is_deleted', false);
                }
            ]);

            return response()->json([
                'code' => 200,
                'message' => 'Cập nhật suất chiếu thành công',
                'data' => $showtime
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => 'Suất chiếu không tồn tại'
            ], 404);
        }
    }

    // Tìm kiếm suất chiếu theo tên phim, tên rạp hoặc ngày
    public function searchShowtimes(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string',
            'per_page' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
        ]);

        $keyword = $request->input('keyword');
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $query = ShowTime::with([
            'movie' => function ($q) {
                $q->select('movie_id', 'title', 'duration')->where('is_deleted', false);
            },
            'room.cinema' => function ($q) {
                $q->select('cinema_id', 'name')->where('is_deleted', false);
            }
        ])
            ->where('is_deleted', false)
            ->where(function ($query) use ($keyword) {
                $query->whereHas('movie', function ($q) use ($keyword) {
                    $q->where('title', 'LIKE', '%' . $keyword . '%');
                })->orWhereHas('room.cinema', function ($q) use ($keyword) {
                    $q->where('name', 'LIKE', '%' . $keyword . '%');
                })->orWhereDate('start_time', $keyword); // nếu keyword là ngày
            });

        $results = $query->paginate($perPage, ['*'], 'page', $page);

        if ($results->isEmpty()) {
            return ApiResponse::error('Không tìm thấy suất chiếu phù hợp', 404);
        }

        return ApiResponse::success($results, 'Kết quả tìm kiếm suất chiếu');
    }

    // Xóa suất chiếu (soft delete)
    public function destroy($id)
    {
        try {
            $showtime = ShowTime::where('showtime_id', $id)
                ->where('is_deleted', false)
                ->firstOrFail();

            $showtime->is_deleted = true;
            $showtime->save();

            return response()->json([
                'code' => 200,
                'message' => 'Xóa suất chiếu thành công (soft delete)'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => 'Suất chiếu không tồn tại'
            ], 404);
        }
    }

    // Khôi phục suất chiếu đã bị xóa mềm
    public function restore($id)
    {
        try {
            $showtime = ShowTime::where('showtime_id', $id)
                ->where('is_deleted', true)
                ->firstOrFail();

            $showtime->is_deleted = false;
            $showtime->save();

            // Load lại quan hệ để trả về thông tin đầy đủ
            $showtime->load([
                'movie' => function ($query) {
                    $query->select('movie_id', 'title', 'duration')->where('is_deleted', false);
                },
                'room.cinema' => function ($query) {
                    $query->select('cinema_id', 'name')->where('is_deleted', false);
                }
            ]);

            return response()->json([
                'code' => 200,
                'message' => 'Khôi phục suất chiếu thành công',
                'data' => $showtime
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => 'Suất chiếu không tồn tại hoặc chưa bị xóa'
            ], 404);
        }
    }
}