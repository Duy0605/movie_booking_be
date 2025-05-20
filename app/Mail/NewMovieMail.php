<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewMovieMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customer_name;
    public $movie_title;
    public $movie_description;
    public $release_date;
    public $movie_genre;
    public $movie_poster_url;
    public $movie_id;

    public function __construct($customer_name, $movie_title, $movie_description, $release_date, $movie_genre, $movie_poster_url, $movie_id)
    {
        $this->customer_name = $customer_name;
        $this->movie_title = $movie_title;
        $this->movie_description = $movie_description;
        $this->release_date = $release_date;
        $this->movie_genre = $movie_genre;
        $this->movie_poster_url = $movie_poster_url;
        $this->movie_id = $movie_id;
    }

    public function build()
    {
        return $this->subject("🎬 New Movie: {$this->movie_title}")
            ->view('emails.new_movie');
    }
}
