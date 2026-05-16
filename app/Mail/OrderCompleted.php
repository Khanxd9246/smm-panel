<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\BrevoMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderCompleted implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function handle(BrevoMailService $mailer): void
    {
        $user = $this->order->user;

        if (! $user?->email) {
            return;
        }

        $sent = $mailer->send(
            to:      $user->email,
            subject: "Order #{$this->order->id} Completed ✓ — " . config('app.name'),
            view:    'emails.order-completed',
            data:    ['order' => $this->order],
        );

        if (! $sent) {
            Log::warning('OrderCompleted: email failed', ['order_id' => $this->order->id]);
        }
    }
}
