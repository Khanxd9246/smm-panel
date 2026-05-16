<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\ResendMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * OrderCompleted — sends order completion email via Resend HTTP API.
 * Same pattern as OrderPlaced — bypasses SMTP/Mailable stack entirely.
 *
 * Usage:
 *   dispatch(new OrderCompleted($order));
 */
class OrderCompleted implements ShouldQueue
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
            subject: "Order #{$this->order->id} Completed ✓ — " . config('app.name'),
            view:    'emails.order-completed',
            data:    ['order' => $this->order],
        );

        if (! $sent) {
            Log::warning('OrderCompleted: email failed', ['order_id' => $this->order->id]);
        }
    }
}
