<?php

namespace App\Channels;

use App\Services\BrevoMailService;
use Illuminate\Notifications\Notification;

/**
 * ResendChannel (Now routing via Brevo HTTP API)
 *
 * A custom notification channel that sends via Brevo HTTP API.
 * Completely bypasses Laravel's MailChannel and SMTP stack.
 *
 * Notifications using this channel must implement toResend().
 */
class ResendChannel
{
    private $mailer;

    // Use a backward-compatible constructor for older PHP versions
    public function __construct(BrevoMailService $mailer)
    {
        $this->mailer = $mailer;
    }

    public function send($notifiable, Notification $notification): void
    {
        // Keep toResend() call so you don't have to change your Notification classes
        $message = $notification->toResend($notifiable);

        if (empty($message) || empty($notifiable->email)) {
            return;
        }

        // Passed strictly as ordered, positional arguments for PHP compatibility
        $this->mailer->send(
            $notifiable->email,
            $message['subject'],
            $message['view'],
            $message['data'] ?? []
        );
    }
}
