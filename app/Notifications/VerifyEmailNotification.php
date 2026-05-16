<?php

namespace App\Notifications;

use App\Channels\BrevoChannel;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

    public function via(mixed $notifiable): array
    {
        return [BrevoChannel::class];
    }

    public function toBrevo(mixed $notifiable): array
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        return [
            'subject' => 'Verify Your Email — ' . config('app.name'),
            'view'    => 'emails.verify-email',
            'data'    => [
                'url'     => $url,
                'appName' => config('app.name'),
            ],
        ];
    }
}
