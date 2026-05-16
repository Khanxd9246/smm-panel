<?php

namespace App\Channels;

use App\Services\BrevoMailService;
use Illuminate\Notifications\Notification;

/**
 * BrevoChannel
 *
 * Custom notification channel that sends via Brevo HTTP API.
 * Completely bypasses Laravel's MailChannel and SMTP stack.
 *
 * Notifications using this channel must implement toBrevo().
 */
class BrevoChannel
{
    public function __construct(private BrevoMailService $mailer) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (empty($notifiable->email)) {
            return;
        }

        $message = $notification->toBrevo($notifiable);

        if (empty($message)) {
            return;
        }

        $this->mailer->send(
            to:      $notifiable->email,
            subject: $message['subject'],
            view:    $message['view'],
            data:    $message['data'] ?? [],
        );
    }
}
