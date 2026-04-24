<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Mail\ResetPasswordCustomMail;

class ResetPasswordCustomNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable)
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new ResetPasswordCustomMail(
            $resetUrl,
            $notifiable->first_name ?? 'usuario'
        ))->to($notifiable->getEmailForPasswordReset());
    }
}
