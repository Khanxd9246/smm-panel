<?php

namespace App\Jobs;

use App\Mail\OrderCompleted;
use App\Models\ApiProvider;
use App\Models\Order;
use App\Services\ProviderApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SyncOrderStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 3;
    public $timeout = 120;

    public function __construct(
        protected ApiProvider $provider,
        protected array $apiOrderIds
    ) {}

    public function handle(): void
    {
        $api = new ProviderApiService($this->provider);

        // Try bulk first; fall back to individual calls if provider doesn't support it
        $response = $this->fetchStatuses($api, $this->apiOrderIds);

        if (empty($response)) {
            Log::warning("SyncOrderStatusJob: empty response from provider {$this->provider->id}");
            return;
        }

        $orders = Order::with('user', 'service')
            ->whereIn('api_order_id', $this->apiOrderIds)
            ->whereIn('status', ['pending', 'in progress'])
            ->get();

        foreach ($orders as $order) {
            // Match response by api_order_id — providers return string or int keys
            $data = $response[(string) $order->api_order_id]
                 ?? $response[(int)   $order->api_order_id]
                 ?? null;

            if (!$data || !isset($data['status'])) {
                Log::debug("SyncOrderStatusJob: no data for order#{$order->id} api_id={$order->api_order_id}");
                continue;
            }

            $oldStatus = $order->status;
            $newStatus = $this->mapStatus($data['status']);

            $order->update([
                'status'      => $newStatus,
                'remains'     => array_key_exists('remains', $data)     ? (int) $data['remains']     : $order->remains,
                'start_count' => array_key_exists('start_count', $data) ? (int) $data['start_count'] : $order->start_count,
            ]);

            Log::info("SyncOrderStatusJob: order#{$order->id} {$oldStatus} → {$newStatus}");

            // Send completion email when status changes to completed or partial
            if ($oldStatus !== $newStatus && in_array($newStatus, ['completed', 'partial'])) {
                $this->sendCompletionEmail($order);
            }
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function fetchStatuses(ProviderApiService $api, array $ids): array
    {
        // First try bulk (one API call for all IDs)
        try {
            $result = $api->getStatusBulk($ids);
            if (is_array($result) && !empty($result)) {
                return $result;
            }
        } catch (\Throwable $e) {
            Log::info("SyncOrderStatusJob: bulk failed for provider {$this->provider->id}, falling back to individual calls. Error: " . $e->getMessage());
        }

        // Fall back: individual getStatus() calls
        $results = [];
        foreach ($ids as $id) {
            try {
                $data = $api->getStatus((int) $id);
                if (!empty($data)) {
                    $results[(string) $id] = $data;
                }
            } catch (\Throwable $e) {
                Log::warning("SyncOrderStatusJob: getStatus failed for api_order#{$id}: " . $e->getMessage());
            }
        }
        return $results;
    }

    private function sendCompletionEmail(Order $order): void
    {
        try {
            if ($order->user && $order->user->email) {
                Mail::to($order->user->email)->queue(new OrderCompleted($order));
                Log::info("SyncOrderStatusJob: completion email queued for order#{$order->id} → {$order->user->email}");
            }
        } catch (\Throwable $e) {
            // Never let email failure break sync
            Log::warning("SyncOrderStatusJob: could not send completion email for order#{$order->id}: " . $e->getMessage());
        }
    }

    private function mapStatus(string $raw): string
    {
        return match (strtolower(trim($raw))) {
            'completed', 'complete'                            => 'completed',
            'partial'                                          => 'partial',
            'cancelled', 'canceled'                           => 'cancelled',
            'processing', 'in progress', 'inprogress',
            'active', 'in_progress'                           => 'in progress',
            'error', 'fail', 'failed'                         => 'error',
            default                                            => 'pending',
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SyncOrderStatusJob permanently failed for provider {$this->provider->id}: " . $exception->getMessage());
    }
}
