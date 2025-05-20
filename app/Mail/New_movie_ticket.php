<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class New_movie_ticket extends Mailable
{
    use Queueable, SerializesModels;

   public $customer_name, $booking_id, $movie_title, $cinema_name, $room_name, $showtime, $seats, $total_price, $barcode_url, $ticket_code;

public function __construct($customer_name, $booking_id, $movie_title, $cinema_name, $room_name, $showtime, $seats, $total_price, $barcode_url, $ticket_code)
{
    $this->customer_name = $customer_name;
    $this->booking_id = $booking_id;
    $this->movie_title = $movie_title;
    $this->cinema_name = $cinema_name;
    $this->room_name = $room_name;
    $this->showtime = $showtime;
    $this->seats = $seats;
    $this->total_price = $total_price;
    $this->barcode_url = $barcode_url;
    $this->ticket_code = $ticket_code;
}

public function build()
{
    return $this->view('emails.movie_ticket')
        ->with([
            'customer_name' => $this->customer_name,
            'booking_id' => $this->booking_id,
            'movie_title' => $this->movie_title,
            'cinema_name' => $this->cinema_name,
            'room_name' => $this->room_name,
            'showtime' => $this->showtime,
            'seats' => $this->seats,
            'total_price' => $this->total_price,
            'barcode_url' => $this->barcode_url,
            'ticket_code' => $this->ticket_code,
        ]);
}
}
