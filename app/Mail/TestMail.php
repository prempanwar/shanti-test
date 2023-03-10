<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;


class TestMail extends Mailable
{
    use Queueable, SerializesModels;
    
    public $userDetails;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($userDetails)
    {
        $this->userDetails = $userDetails;
    }

    /**
     * Build the message.
     *
     * @return $this
     */

    public function build()
    {
        return $this->view('emails.user.otp')
                    ->subject($this->userDetails['subject'])
                    ->with(['user' => $this->userDetails]);
    }
    
}
