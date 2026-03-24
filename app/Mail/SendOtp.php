<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\HtmlString;

class SendOtp extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $token;
    public $subjectText;
    public $body;
    public $emailAddress;

    /**
     * Create a new message instance.
     *
     * @param string $token
     * @param string $subject
     * @param string $body
     */
    public function __construct($token, $subject, $body, $emailAddress = null)
    {
        $this->token = $token;
        $this->subjectText = $subject;
        $this->body = $body;
        $this->emailAddress = $emailAddress;

        $this->onQueue('high');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subjectText)
                    ->view('emails.send_otp')
                    ->with([
                        'token' => $this->token,
                        'body' => $this->body,
                        'email' => $this->emailAddress,
                    ]);
    }
}
