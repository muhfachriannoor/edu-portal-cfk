<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class SendOTP extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var
     */
    public $token;

    /**
     * @var
     */
    public $subject;

    /**
     * @var
     */
    public $body;

    /**
     * Create a new notification instance.
     *
     * @param string $token
     * @param string $subject
     * @param string $body
     */
    public function __construct($token, $subject, $body)
    {
        $this->token = $token;
        $this->subject = $subject;
        $this->body = $body;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {        
        return (new MailMessage)
            ->subject($this->subject)
            ->greeting('Dear our beloved Customer,')
            ->line($this->body)
            ->action($this->token, '#')
            ->line('Please do not share this code with anyone.')
            ->salutation(new HtmlString("Best Regards, <br> Sarinah"));
    }
}
