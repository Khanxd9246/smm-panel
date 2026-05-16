<?php

namespace App\Channels;

use App\Services\ResendMailService;
use Illuminate\Notifications\Notification;

class ResendChannel
{
    protected $mailer;

    public function __construct(ResendMailService $mailer)
    {
        $this->mailer = $mailer;
    }

    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toResend($notifiable);

        if (empty($message) || empty($notifiable->email)) {
            return;
        }

        $this->mailer->send(
            $notifiable->email,
            $message['subject'],
            $message['view'],
            isset($message['data']) ? $message['data'] : []
        );
    }
}
