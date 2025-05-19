<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Response\ApiResponse;
use App\Models\UserAccount;

class ReviewController extends Controller
{
    // Danh sách các review chưa bị xóa
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $reviews = Review::with([
            'user' => function ($query) {
                $query->select('user_id', 'full_name', 'profile_picture_url');
            }
        ])
            ->where('is_deleted', false)
            ->paginate($perPage, ['*'], 'page', $page);

        $formatted = $reviews->getCollection()->map(function ($review) {
            return [
                'review_id' => $review->review_id,
                'movie_id' => $review->movie_id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at,
                'user' => [
                    'user_id' => $review->user->user_id ?? null,
                    'full_name' => $review->user->full_name ?? null,
                    'profile_picture_url' => $review->user->profile_picture_url ?? null,
                ],
            ];
        });

        // Gán lại collection đã map vào paginator
        $reviews->setCollection($formatted);

        return ApiResponse::success($reviews, 'Danh sách đánh giá kèm thông tin người dùng (phân trang)');
    }

    // Lấy danh sách đánh giá theo movie
    public function getReviewsByMovie(Request $request, $movieId)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $reviews = Review::with([
            'user' => function ($query) {
                $query->select('user_id', 'full_name', 'profile_picture_url');
            }
        ])
            ->where('movie_id', $movieId)
            ->where('is_deleted', false)
            ->paginate($perPage, ['*'], 'page', $page);

        $formatted = $reviews->getCollection()->map(function ($review) {
            return [
                'review_id' => $review->review_id,
                'movie_id' => $review->movie_id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at,
                'user' => [
                    'user_id' => $review->user->user_id ?? null,
                    'full_name' => $review->user->full_name ?? null,
                    'profile_picture_url' => $review->user->profile_picture_url ?? null,
                ],
            ];
        });

        $reviews->setCollection($formatted);

        return ApiResponse::success($reviews, 'Danh sách đánh giá theo phim');
    }

    // Lấy danh sách đánh giá theo user
    public function getReviewsByUser(Request $request, $userId)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $reviews = Review::with('user')
            ->where('user_id', $userId)
            ->where('is_deleted', false)
            ->paginate($perPage, ['*'], 'page', $page);

        $formatted = $reviews->getCollection()->map(function ($review) {
            return [
                'review_id' => $review->review_id,
                'movie_id' => $review->movie_id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at,
                'user' => [
                    'user_id' => $review->user->user_id ?? null,
                    'full_name' => $review->user->full_name ?? null,
                    'profile_picture_url' => $review->user->profile_picture_url ?? null,
                ],
            ];
        });

        $reviews->setCollection($formatted);

        return ApiResponse::success($reviews, 'Danh sách đánh giá của người dùng');
    }



    // Tạo mới một đánh giá

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string|exists:user_account,user_id',
            'movie_id' => 'required|string|exists:movie,movie_id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string',
        ]);

        // Kiểm tra xem người dùng đã đánh giá phim này chưa
        $existingReview = Review::where('user_id', $request->user_id)
            ->where('movie_id', $request->movie_id)
            ->where('is_deleted', false)
            ->first();

        if ($existingReview) {
            return ApiResponse::error('Người dùng đã đánh giá phim này rồi', 409);
        }

        // Tạo review mới
        $review = Review::create([
            'review_id' => (string) Str::uuid(),
            'user_id' => $request->user_id,
            'movie_id' => $request->movie_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return ApiResponse::success($review, 'Đánh giá được tạo thành công', 201);
    }


    // Lấy chi tiết một đánh giá
    public function show($id)
    {
        $review = Review::where('review_id', $id)->where('is_deleted', false)->first();

        if (!$review) {
            return ApiResponse::error('Không tìm thấy đánh giá', 404);
        }

        return ApiResponse::success($review, 'Chi tiết đánh giá');
    }

    // Cập nhật đánh giá
    public function update(Request $request, $id)
    {
        $review = Review::find($id);

        if (!$review || $review->is_deleted) {
            return ApiResponse::error('Đánh giá không tồn tại', 404);
        }

        $request->validate([
            'rating' => 'sometimes|integer|between:1,5',
            'comment' => 'sometimes|nullable|string',
        ]);

        $review->update($request->only(['rating', 'comment']));

        return ApiResponse::success($review, 'Cập nhật đánh giá thành công');
    }

    // Xóa mềm đánh giá
    public function destroy($id)
    {
        $review = Review::find($id);

        if (!$review || $review->is_deleted) {
            return ApiResponse::error('Không tìm thấy đánh giá', 404);
        }

        $review->is_deleted = true;
        $review->save();

        return ApiResponse::success(null, 'Đánh giá đã được xóa');
    }

    // Tính trung bình rating cho một bộ phim
    public function averageRatingByMovie($movieId)
    {
        $average = Review::where('movie_id', $movieId)
            ->where('is_deleted', false)
            ->avg('rating');

        // Nếu chưa có đánh giá nào thì trả về null hoặc 0
        return ApiResponse::success([
            'movie_id' => $movieId,
            'average_rating' => $average ? round($average, 2) : null
        ], 'Trung bình đánh giá cho phim');
    }

}
