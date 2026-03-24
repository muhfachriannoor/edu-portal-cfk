<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class PasswordResetDeeplink extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;
    public $email;
    public $guard;
    public $localization;

    /**
     * Create a notification instance.
     *
     * @param  string  $token
     * @param string $guard
     */
    public function __construct($token, $email, $localization, $guard = 'web')
    {
        $this->token        = $token;
        $this->email        = $email;
        $this->guard        = $guard;
        $this->localization = $localization;
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
        // Deep link for mobile app (change as needed)
        $deepLink = config('app.frontend_url') . "/{$this->localization}/reset-password?fp=" . urlencode(base64_encode(json_encode([
            'token' => $this->token,
            'email' => $this->email,
        ])));

        $broker = $this->getBroker();

        return (new MailMessage)
            ->subject(Lang::get('Sarinah Account Password Reset Request'))
            ->line(Lang::get('We received a request to reset the password for your Sarinah E-Commerce account. Please click the link below to create a new password:'))
            ->action(Lang::get('Reset Password'), $deepLink)
            // ->line(Lang::get('This password reset link will expire in :count minutes.', ['count' => config("auth.passwords.{$broker}.expire")]))
            // ->line(Lang::get('If you did not request a password reset, no further action is required.'));
            ->line(Lang::get('This link can only be used once. If you did not request a password reset, please ignore this email. Your account remains secure.'));
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
