<?php

namespace App\Notifications;

use App\Channels\BrevoChannel;
use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResetPasswordNotification extends BaseResetPassword implements ShouldQueue
{
    use Queueable;

    public function via(mixed $notifiable): array
    {
        return [BrevoChannel::class];
    }

    public function toBrevo(mixed $notifiable): array
    {
        $url = $this->resetUrl($notifiable);

        return [
            'subject' => 'Reset Your Password — ' . config('app.name'),
            'view'    => 'emails.password-reset',
            'data'    => [
                'actionUrl' => $url,
                'userName'  => $notifiable->name ?? 'there',
            ],
        ];
    }

    protected function resetUrl(mixed $notifiable): string
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable, $this->token);
        }

        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
