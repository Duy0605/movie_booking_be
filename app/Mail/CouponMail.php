<?php namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CouponMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customerName;
    public $couponCode;
    public $expiryDate;
    public $couponDescription;

    public function __construct($customerName, $couponCode, $expiryDate, $couponDescription)
    {
        $this->customerName = $customerName;
        $this->couponCode = $couponCode;
        $this->expiryDate = $expiryDate;
        $this->couponDescription = $couponDescription;
    }

    public function build()
    {
        return $this->subject('Your Special Coupon from Our Website')
                    ->view('emails.coupon');
    }
}
