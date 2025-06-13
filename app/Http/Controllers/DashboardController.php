<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Movie;
use App\Models\Cinema;
use App\Models\ShowTime;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function getDashboardData(Request $request)
    {
        try {
            $filter = $request->query('filter', 'month'); // Default to 'month'
            $month = $request->query('month'); // Format: YYYY-MM, e.g., 2025-01

            // Log incoming parameters for debugging
            Log::info('Dashboard API called', [
                'filter' => $filter,
                'month' => $month,
                'raw_query' => $request->getQueryString(),
            ]);

            // Validate filter
            if (!in_array($filter, ['day', 'week', 'month'])) {
                Log::error('Invalid filter value', [
                    'filter' => $filter,
                    'request' => $request->all(),
                ]);
                return response()->json([
                    'message' => 'Invalid filter value',
                ], 400);
            }

            // Validate month if provided
            if ($month && !preg_match('/^\d{4}-\d{2}$/', $month)) {
                Log::error('Invalid month format', [
                    'month' => $month,
                    'request' => $request->all(),
                ]);
                return response()->json([
                    'message' => 'Invalid month format. Use YYYY-MM',
                ], 400);
            }

            // Fetch dashboard data
            $totalBookings = $this->getTotalBookings($filter, $month);
            $totalRevenue = $this->getTotalRevenue($filter, $month);
            $recentBookings = $this->getRecentBookings();
            $revenueData = $this->getRevenueData($filter, $month);
            $topMovies = $this->getTopMovies($filter, $month);
            $topCinemas = $this->getTopCinemas($filter, $month);
            $topMoviesByCinema = $this->getTopMoviesByCinema($filter, $month);

            return response()->json([
                'totalBookings' => $totalBookings,
                'totalRevenue' => $totalRevenue,
                'recentBookings' => $recentBookings,
                'revenueData' => $revenueData,
                'topMovies' => $topMovies,
                'topCinemas' => $topCinemas,
                'topMoviesByCinema' => $topMoviesByCinema,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Dashboard data fetch failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);
            return response()->json([
                'message' => 'Failed to load dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function getTotalBookings($filter, $month = null)
    {
        $query = Booking::where('is_deleted', false)
            ->where('status', 'CONFIRMED');

        if ($month) {
            $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();
            $query->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
        } else {
            if ($filter === 'day') {
                $query->whereDate('created_at', Carbon::today());
            } elseif ($filter === 'week') {
                $query->whereBetween('created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ]);
            } else {
                // Month
                $query->whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ]);
            }
        }

        return $query->count();
    }

    private function getTotalRevenue($filter, $month = null)
    {
        $query = Booking::where('is_deleted', false)
            ->where('status', 'CONFIRMED');

        if ($month) {
            $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();
            $query->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
        } else {
            if ($filter === 'day') {
                $query->whereDate('created_at', Carbon::today());
            } elseif ($filter === 'week') {
                $query->whereBetween('created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ]);
            } else {
                // Month
                $query->whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ]);
            }
        }

        return (float) $query->sum('total_price');
    }

    private function getRecentBookings()
    {
        return Booking::select(
            'booking.booking_id',
            'booking.user_id',
            'user_account.username',
            'user_account.full_name',
            'movie.title as movie_title',
            'cinema.name as cinema_name',
            'booking.total_price',
            'booking.status'
        )
            ->leftJoin('showtime', 'booking.showtime_id', '=', 'showtime.showtime_id')
            ->leftJoin('movie', 'showtime.movie_id', '=', 'movie.movie_id')
            ->leftJoin('room', 'showtime.room_id', '=', 'room.room_id')
            ->leftJoin('cinema', 'room.cinema_id', '=', 'cinema.cinema_id')
            ->leftJoin('user_account', 'booking.user_id', '=', 'user_account.user_id')
            ->where('booking.is_deleted', false)
            ->orderBy('booking.created_at', 'desc')
            ->take(5)
            ->get()
            ->toArray();
    }

    private function getRevenueData($filter, $month = null)
    {
        $query = Booking::select(
            DB::raw('SUM(total_price) as revenue'),
            DB::raw('MIN(created_at) as min_created_at')
        )
            ->where('is_deleted', false)
            ->where('status', 'CONFIRMED');

        if ($month) {
            $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();
            $query->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->selectRaw('DATE_FORMAT(created_at, "%b %d") as date')
                ->groupBy(DB::raw('DATE(created_at)'), 'date')
                ->orderBy(DB::raw('DATE(created_at)'), 'asc');
        } else {
            if ($filter === 'day') {
                $query->selectRaw('DATE_FORMAT(created_at, "%b %d") as date')
                    ->where('created_at', '>=', Carbon::now()->subDays(7))
                    ->groupBy(DB::raw('DATE(created_at)'), 'date')
                    ->orderBy(DB::raw('DATE(created_at)'), 'asc');
            } elseif ($filter === 'week') {
                $query->selectRaw('CONCAT(
                        DATE_FORMAT(DATE_SUB(created_at, INTERVAL DAYOFWEEK(created_at) - 1 DAY), "%b %d"),
                        "-",
                        DATE_FORMAT(DATE_ADD(DATE_SUB(created_at, INTERVAL DAYOFWEEK(created_at) - 1 DAY), INTERVAL 6 DAY), "%b %d")
                    ) as date')
                    ->where('created_at', '>=', Carbon::now()->subWeeks(4))
                    ->groupBy(DB::raw('YEAR(created_at), WEEK(created_at, 1)'), 'date')
                    ->orderBy(DB::raw('MIN(created_at)'), 'asc');
            } else {
                // Monthly
                $query->selectRaw('DATE_FORMAT(created_at, "%b %Y") as date')
                    ->where('created_at', '>=', Carbon::now()->subMonths(5))
                    ->groupBy(DB::raw('YEAR(created_at), MONTH(created_at)'), 'date')
                    ->orderBy(DB::raw('YEAR(created_at), MONTH(created_at)'), 'asc');
            }
        }

        return $query->get()->map(function ($item) {
            return [
                'date' => $item->date,
                'revenue' => (float) $item->revenue,
            ];
        })->toArray();
    }

    private function getTopMovies($filter, $month = null)
    {
        $query = Booking::select(
            'movie.title as movie_title',
            DB::raw('COUNT(booking.booking_id) as bookings')
        )
            ->leftJoin('showtime', 'booking.showtime_id', '=', 'showtime.showtime_id')
            ->leftJoin('movie', 'showtime.movie_id', '=', 'movie.movie_id')
            ->where('booking.is_deleted', false)
            ->where('booking.status', 'CONFIRMED');

        if ($month) {
            $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();
            $query->whereBetween('booking.created_at', [$startOfMonth, $endOfMonth]);
        } else {
            if ($filter === 'day') {
                $query->whereDate('booking.created_at', Carbon::today());
            } elseif ($filter === 'week') {
                $query->whereBetween('booking.created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ]);
            } else {
                // Month
                $query->whereBetween('booking.created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ]);
            }
        }

        return $query->groupBy('movie.movie_id', 'movie.title')
            ->orderBy('bookings', 'desc')
            ->take(5)
            ->get()
            ->toArray();
    }

    private function getTopCinemas($filter, $month = null)
    {
        $query = Booking::select(
            'cinema.name as cinema_name',
            DB::raw('COUNT(booking.booking_id) as bookings')
        )
            ->leftJoin('showtime', 'booking.showtime_id', '=', 'showtime.showtime_id')
            ->leftJoin('room', 'showtime.room_id', '=', 'room.room_id')
            ->leftJoin('cinema', 'room.cinema_id', '=', 'cinema.cinema_id')
            ->where('booking.is_deleted', false)
            ->where('booking.status', 'CONFIRMED');

        if ($month) {
            $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();
            $query->whereBetween('booking.created_at', [$startOfMonth, $endOfMonth]);
        } else {
            if ($filter === 'day') {
                $query->whereDate('booking.created_at', Carbon::today());
            } elseif ($filter === 'week') {
                $query->whereBetween('booking.created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ]);
            } else {
                // Month
                $query->whereBetween('booking.created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ]);
            }
        }

        return $query->groupBy('cinema.cinema_id', 'cinema.name')
            ->orderBy('bookings', 'desc')
            ->take(5)
            ->get()
            ->toArray();
    }

    private function getTopMoviesByCinema($filter, $month = null)
    {
        $subQuery = Booking::select(
            'cinema.cinema_id',
            'cinema.name as cinema_name',
            'movie.title as movie_title',
            DB::raw('COUNT(booking.booking_id) as bookings'),
            DB::raw('ROW_NUMBER() OVER (PARTITION BY cinema.cinema_id ORDER BY COUNT(booking.booking_id) DESC) as rn')
        )
            ->leftJoin('showtime', 'booking.showtime_id', '=', 'showtime.showtime_id')
            ->leftJoin('movie', 'showtime.movie_id', '=', 'movie.movie_id')
            ->leftJoin('room', 'showtime.room_id', '=', 'room.room_id')
            ->leftJoin('cinema', 'room.cinema_id', '=', 'cinema.cinema_id')
            ->where('booking.is_deleted', false)
            ->where('booking.status', 'CONFIRMED');

        if ($month) {
            $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();
            $subQuery->whereBetween('booking.created_at', [$startOfMonth, $endOfMonth]);
        } else {
            if ($filter === 'day') {
                $subQuery->whereDate('booking.created_at', Carbon::today());
            } elseif ($filter === 'week') {
                $subQuery->whereBetween('booking.created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ]);
            } else {
                // Month
                $subQuery->whereBetween('booking.created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ]);
            }
        }

        $subQuery->groupBy('cinema.cinema_id', 'cinema.name', 'movie.movie_id', 'movie.title');

        return DB::table(DB::raw("({$subQuery->toSql()}) as sub"))
            ->mergeBindings($subQuery->getQuery())
            ->select('cinema_name', 'movie_title', 'bookings')
            ->where('rn', 1)
            ->orderBy('bookings', 'desc')
            ->take(5)
            ->get()
            ->toArray();
    }
}