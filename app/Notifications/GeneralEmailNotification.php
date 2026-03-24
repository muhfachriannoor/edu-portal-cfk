<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class GeneralEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $model;
    protected $content;
    protected $roleName;

    /**
     * $content structure: subject, title, message, path
     */
    public function __construct($model, array $content)
    {
        $this->model = $model;
        $this->content = $content;
        $this->roleName = $content['role_name'] ?? null;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // $paymentUrl = config('app.url') . '/checkout/payment/' . $this->model->order_number;
        $urlPath = config('services.default.frontend.url') . '/id' . $this->content['path'];
        $supportUrl = 'https://sarinah.co.id/contact';

        $invoiceUrl = URL::temporarySignedRoute(
            'api.order.invoice.public', 
            now()->addDays(30), // 30 days
            [
                'order_id' => $this->model->id,
                'format' => 'pdf'
            ]
        );

        return (new MailMessage)
            ->subject($this->content['subject'])
            ->view($this->content['view'], [
                'title'       => $this->content['title'],
                'body'        => $this->content['body'],
                'model'       => $this->model,
                'roleName'    => $this->roleName ?? ($notifiable->name ?? 'User'),
                'statusLabel' => $this->model->email_status_label,
                'urlPath'     => $urlPath,
                'invoiceUrl'  => $invoiceUrl,
                'supportUrl'  => $supportUrl,
            ]);
    }

    public function failed(\Throwable $exception)
    {
        Log::error("Email dispatch failed. Model ID: " . ($this->model->id ?? 'N/A') . ". Error: " . $exception->getMessage());
    }
}