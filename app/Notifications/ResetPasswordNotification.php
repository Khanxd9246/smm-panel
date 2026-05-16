<?php

namespace App\Notifications;

use App\Services\ResendMailService;
use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * ResetPasswordNotification
 *
 * Uses ResendMailService (HTTP API) instead of SMTP.
 * Bypasses Laravel's mail stack entirely — no SMTP port issues on Railway.
 */
class ResetPasswordNotification extends BaseResetPassword implements ShouldQueue
{
    use Queueable;

    /**
     * Handle the queued notification — sends via Resend HTTP API.
     * Called by the queue worker (or sync queue) instead of going
     * through Laravel's MailChannel → SMTP flow.
     */
    public function handle($notifiable): void
    {
        // Build the reset URL the same way Laravel does internally
        $url = $this->resetUrl($notifiable);

        /** @var ResendMailService $mailer */
        $mailer = app(ResendMailService::class);

        $sent = $mailer->send(
            to:      $notifiable->email,
            subject: 'Reset Your Password — ' . config('app.name'),
            view:    'emails.password-reset',
            data:    [
                'actionUrl' => $url,
                'userName'  => $notifiable->name ?? 'there',
            ],
        );

        if (! $sent) {
            Log::warning('ResetPasswordNotification: email failed to send via Resend', [
                'email' => $notifiable->email,
            ]);
        }
    }

    /**
     * Build the password reset URL.
     * Mirrors Laravel's base class logic.
     */
    protected function resetUrl($notifiable): string
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
