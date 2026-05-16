<?php

namespace App\Channels;

use App\Services\ResendMailService;
use Illuminate\Notifications\Notification;

/**
 * ResendChannel
 *
 * A custom notification channel that sends via Resend HTTP API.
 * Completely bypasses Laravel's MailChannel and SMTP stack.
 *
 * Notifications using this channel must implement toResend().
 */
class ResendChannel
{
    public function __construct(private ResendMailService $mailer) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        $message = $notification->toResend($notifiable);

        if (empty($message) || empty($notifiable->email)) {
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
