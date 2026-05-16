<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\ResendMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * OrderPlaced — sends order confirmation via Resend HTTP API.
 *
 * Does NOT extend Mailable — extends nothing, implements ShouldQueue
 * so it can be dispatched to queue via Mail::to()->queue() or directly
 * via dispatch(). Uses ResendMailService to avoid SMTP entirely.
 *
 * Usage (same as before):
 *   Mail::to($user->email)->queue(new OrderPlaced($order));
 *   // or
 *   dispatch(new OrderPlaced($order));
 */
class OrderPlaced implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    /**
     * Handle the job — called by the queue worker.
     */
    public function handle(ResendMailService $mailer): void
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
