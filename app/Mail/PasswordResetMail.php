<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customer_name;
    public $new_password;

    public function __construct($customer_name, $new_password)
    {
        $this->customer_name = $customer_name;
        $this->new_password = $new_password;
    }

    public function build()
    {
        return $this->subject("🔒 Your Password Has Been Reset")
            ->view('emails.password_reset')
            ->with([
                'customerName' => $this->customer_name,
                'newPassword' => $this->new_password,
            ]);
    }
}