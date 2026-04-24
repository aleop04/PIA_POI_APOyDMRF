<?php

namespace App\Notifications;

use App\Mail\VerifyEmailCustomMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class VerifyEmailCustomNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable)
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        return (new VerifyEmailCustomMail(
            $verificationUrl,
            $notifiable->first_name ?? 'usuario'
        ))->to($notifiable->getEmailForVerification());
    }
}