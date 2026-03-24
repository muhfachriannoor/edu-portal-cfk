<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class PasswordReset extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;
    public $guard;

    /**
     * Create a notification instance.
     *
     * @param  string  $token
     * @param string $guard
     */
    public function __construct($token, $guard = 'web')
    {
        $this->token = $token;
        $this->guard = $guard;
    }

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = $this->getUrl($notifiable);
        $broker = $this->getBroker();

        return (new MailMessage)
            ->subject(Lang::get('Sarinah Account Password Reset Request'))
            ->line(Lang::get('We received a request to reset the password for your Sarinah E-Commerce account. Please click the link below to create a new password:'))
            ->action(Lang::get('Reset Password'), $url)
            // ->line(Lang::get('This password reset link will expire in :count minutes.', ['count' => config("auth.passwords.{$broker}.expire")]))
            // ->line(Lang::get('If you did not request a password reset, no further action is required.'));
            ->line(Lang::get('This link can only be used once. If you did not request a password reset, please ignore this email. Your account remains secure.'));
    }

    /**
     * @param $notifiable
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    protected function getUrl($notifiable)
    {
        $email = $notifiable->getEmailForPasswordReset();
        $queryParam = base64_encode([
            'token' => $this->token,
            'email' => $email,
        ]);

        $url = url(route("{$this->guard}.password.reset",[
            'fp' => $queryParam
        ] , false));

        return $url;
    }

    /**
     * @return string
     */
    protected function getBroker()
    {
        return $this->guard === 'api'
            ? 'users'
            : 'admins';
    }
}
