<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ShowTime;
use App\Models\Movie;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowTimeController extends Controller
{
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

    // Lấy danh sách suất chiếu theo movie
    public function showByMovieId(Request $request, $id)
    {
        $perPage = $request->input('per_page', 10);

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
}
