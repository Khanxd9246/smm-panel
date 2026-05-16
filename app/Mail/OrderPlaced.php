<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\BrevoMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderPlaced implements ShouldQueue
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
            subject: "Order #{$this->order->id} Confirmed — " . config('app.name'),
            view:    'emails.order-placed',
            data:    ['order' => $this->order],
        );

        if (! $sent) {
            Log::warning('OrderPlaced: email failed', ['order_id' => $this->order->id]);
        }
    }
}
