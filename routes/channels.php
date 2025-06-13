<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('showtime.{showtimeId}', function ($user, $showtimeId) {
    return true; // Public channel for seat booking
});