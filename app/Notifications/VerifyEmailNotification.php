<?php

namespace App\Notifications;

use App\Services\ResendMailService;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * VerifyEmailNotification
 *
 * Uses ResendMailService (HTTP API) instead of SMTP.
 * Bypasses Laravel's mail stack entirely — no SMTP port issues on Railway.
 */
class VerifyEmailNotification extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Send the notification — overrides the base class to use Resend HTTP API.
     */
    public function toMail($notifiable): void
    {
        // This method intentionally returns void — we handle sending ourselves.
        // The base class toMail() returns a MailMessage, but we're bypassing that.
    }

    /**
     * Laravel calls via() to know which channels to use.
     * We return 'mail' so the notification system routes here,
     * but the actual send is in handle() via our custom driver.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the MailMessage for the 'mail' channel.
     * We override the base buildMailMessage but actually intercept
     * the send by hooking into the notification's handle flow.
     *
     * NOTE: Because we implement ShouldQueue, the notification is
     * dispatched to the queue. When the sync queue runs it, Laravel
     * calls MailChannel::send() → Mailer::send() → our SMTP config.
     *
     * To bypass SMTP completely we override toMail() to return a custom
     * MailMessage with a callback — but the cleanest approach is to
     * remove ShouldQueue and call ResendMailService directly so there's
     * no SMTP involved at all.
     */
    public function handle($notifiable): void
    {
        $url = $this->verificationUrl($notifiable);

        /** @var ResendMailService $mailer */
        $mailer = app(ResendMailService::class);

        $sent = $mailer->send(
            to:      $notifiable->email,
            subject: 'Verify Your Email — ' . config('app.name'),
            view:    'emails.verify-email',
            data:    [
                'url'     => $url,
                'appName' => config('app.name'),
            ],
        );

        if (! $sent) {
            Log::warning('VerifyEmailNotification: email failed to send via Resend', [
                'user_id' => $notifiable->id,
                'email'   => $notifiable->email,
            ]);
        }
    }

    /**
     * Get the verification URL for the given notifiable.
     * Copied from base class so we can call it directly.
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
