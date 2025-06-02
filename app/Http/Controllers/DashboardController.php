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

class DashboardController extends Controller
{
    public function getDashboardData(Request $request)
    {
        try {
            $filter = $request->query('filter', 'month'); // Default to 'month'

            // Validate filter
            if (!in_array($filter, ['day', 'week', 'month'])) {
                return response()->json([
                    'message' => 'Invalid filter value',
                ], 400);
            }

            // Fetch dashboard data
            $totalBookings = $this->getTotalBookings($filter);
            $totalRevenue = $this->getTotalRevenue($filter);
            $recentBookings = $this->getRecentBookings();
            $revenueData = $this->getRevenueData($filter);
            $topMovies = $this->getTopMovies();
            $topCinemas = $this->getTopCinemas();

            return response()->json([
                'totalBookings' => $totalBookings,
                'totalRevenue' => $totalRevenue,
                'recentBookings' => $recentBookings,
                'revenueData' => $revenueData,
                'topMovies' => $topMovies,
                'topCinemas' => $topCinemas,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function getTotalBookings($filter)
    {
        $query = Booking::where('is_deleted', false)
            ->where('status', 'CONFIRMED');

        if ($filter === 'day') {
            $query->where('created_at', '>=', Carbon::today());
        } elseif ($filter === 'week') {
            $query->where('created_at', '>=', Carbon::now()->startOfWeek());
        } else {
            // Month
            $query->where('created_at', '>=', Carbon::now()->startOfMonth());
        }

        return $query->count();
    }

    private function getTotalRevenue($filter)
    {
        $query = Booking::where('is_deleted', false)
            ->where('status', 'CONFIRMED');

        if ($filter === 'day') {
            $query->where('created_at', '>=', Carbon::today());
        } elseif ($filter === 'week') {
            $query->where('created_at', '>=', Carbon::now()->startOfWeek());
        } else {
            // Month
            $query->where('created_at', '>=', Carbon::now()->startOfMonth());
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

    private function getRevenueData($filter)
    {
        $query = Booking::select(
            DB::raw('SUM(total_price) as revenue')
        )
            ->where('is_deleted', 0)
            ->where('status', 'CONFIRMED');

        if ($filter === 'day') {
            $query->selectRaw('DATE_FORMAT(MIN(created_at), "%b %d") as date')
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy(DB::raw('MIN(created_at)'), 'asc');
        } elseif ($filter === 'week') {
            $query->selectRaw('CONCAT(
                    DATE_FORMAT(DATE_SUB(MIN(created_at), INTERVAL DAYOFWEEK(MIN(created_at)) - 1 DAY), "%b %d"),
                    "-",
                    DATE_FORMAT(DATE_ADD(DATE_SUB(MIN(created_at), INTERVAL DAYOFWEEK(MIN(created_at)) - 1 DAY), INTERVAL 6 DAY), "%b %d")
                ) as date')
                ->where('created_at', '>=', Carbon::now()->subWeeks(4))
                ->groupBy(DB::raw('YEAR(created_at), WEEK(created_at, 1)'))
                ->orderBy(DB::raw('MIN(created_at)'), 'asc');
        } else {
            // Monthly
            $query->selectRaw('DATE_FORMAT(MIN(created_at), "%b %Y") as date')
                ->where('created_at', '>=', Carbon::now()->subMonths(5))
                ->groupBy(DB::raw('YEAR(created_at), MONTH(created_at)'))
                ->orderBy(DB::raw('MIN(created_at)'), 'asc');
        }

        return $query->get()->map(function ($item) {
            return [
                'date' => $item->date,
                'revenue' => (float) $item->revenue,
            ];
        })->toArray();
    }

    private function getTopMovies()
    {
        return Booking::select(
            'movie.title as movie_title',
            DB::raw('COUNT(booking.booking_id) as bookings')
        )
            ->leftJoin('showtime', 'booking.showtime_id', '=', 'showtime.showtime_id')
            ->leftJoin('movie', 'showtime.movie_id', '=', 'movie.movie_id')
            ->where('booking.is_deleted', false)
            ->where('booking.status', 'CONFIRMED')
            ->groupBy('movie.movie_id', 'movie.title')
            ->orderBy('bookings', 'desc')
            ->take(5)
            ->get()
            ->toArray();
    }

    private function getTopCinemas()
    {
        return Booking::select(
            'cinema.name as cinema_name',
            DB::raw('COUNT(booking.booking_id) as bookings')
        )
            ->leftJoin('showtime', 'booking.showtime_id', '=', 'showtime.showtime_id')
            ->leftJoin('room', 'showtime.room_id', '=', 'room.room_id')
            ->leftJoin('cinema', 'room.cinema_id', '=', 'cinema.cinema_id')
            ->where('booking.is_deleted', false)
            ->where('booking.status', 'CONFIRMED')
            ->groupBy('cinema.cinema_id', 'cinema.name')
            ->orderBy('bookings', 'desc')
            ->take(5)
            ->get()
            ->toArray();
    }
}