<?php

namespace App\Services;

use App\Models\ApiProvider;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * SupplierHealthService — Monitor API provider uptime, reliability, and order performance.
 */
class SupplierHealthService
{
    /**
     * Run health check for all active providers.
     * Returns summary array.
     */
    public function checkAll(): array
    {
        $results = [];

        ApiProvider::where('status', 'active')->get()->each(function ($provider) use (&$results) {
            $results[$provider->id] = $this->checkProvider($provider);
        });

        return $results;
    }

    /**
     * Check a single provider: ping API + pull order stats.
     */
    public function checkProvider(ApiProvider $provider): array
    {
        $stats = $this->getProviderStats($provider->id);
        $ping  = $this->pingProvider($provider);

        $healthScore = $this->computeHealthScore($stats, $ping);

        // Persist health data
        $provider->update([
            'health_score'     => $healthScore['score'],
            'health_status'    => $healthScore['status'],
            'last_checked_at'  => now(),
            'api_response_ms'  => $ping['response_ms'],
        ]);

        // Auto-disable if critically unhealthy
        if ($healthScore['score'] <= 2 && $provider->status === 'active') {
            $provider->update(['status' => 'inactive']);
            Log::warning("Auto-disabled provider {$provider->id} ({$provider->name}) due to health score {$healthScore['score']}");

            // Notify admin
            $this->notifyAdminUnhealthyProvider($provider, $healthScore);
        }

        Cache::put("provider_health_{$provider->id}", $healthScore, 900);

        return array_merge($healthScore, ['ping' => $ping, 'stats' => $stats]);
    }

    /**
     * Get aggregated order stats for a provider.
     */
    public function getProviderStats(int $providerId): array
    {
        return Cache::remember("provider_stats_{$providerId}", 1800, function () use ($providerId) {
            $stats = DB::selectOne("
                SELECT
                    COUNT(*) AS total_orders,
                    COUNT(*) FILTER (WHERE o.status = 'completed') AS completed,
                    COUNT(*) FILTER (WHERE o.status = 'cancelled') AS cancelled,
                    COUNT(*) FILTER (WHERE o.status IN ('pending','in progress')
                        AND o.created_at < NOW() - INTERVAL '6 hours') AS delayed,
                    COALESCE(AVG(
                        CASE WHEN o.status IN ('completed','partial')
                        THEN EXTRACT(EPOCH FROM (o.updated_at - o.created_at))/60
                        END
                    ), 0) AS avg_completion_minutes
                FROM orders o
                JOIN services s ON s.id = o.service_id
                WHERE s.api_provider_id = ?
                AND o.created_at >= NOW() - INTERVAL '7 days'
            ", [$providerId]);

            $total = (int) ($stats->total_orders ?? 0);

            return [
                'total_orders'           => $total,
                'completed_orders'       => (int) ($stats->completed ?? 0),
                'cancelled_orders'       => (int) ($stats->cancelled ?? 0),
                'delayed_orders'         => (int) ($stats->delayed ?? 0),
                'success_rate'           => $total > 0 ? round(((int)$stats->completed / $total) * 100, 1) : 100,
                'cancel_rate'            => $total > 0 ? round(((int)$stats->cancelled / $total) * 100, 1) : 0,
                'avg_completion_minutes' => round((float)($stats->avg_completion_minutes ?? 0), 1),
            ];
        });
    }

    /**
     * Ping the provider API to check uptime + response time.
     */
    private function pingProvider(ApiProvider $provider): array
    {
        $start = microtime(true);

        try {
            $response = Http::timeout(10)->post($provider->url, [
                'key'    => $provider->api_key,
                'action' => 'balance',
            ]);

            $ms = round((microtime(true) - $start) * 1000);

            return [
                'online'      => $response->successful(),
                'response_ms' => $ms,
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'online'      => false,
                'response_ms' => 9999,
                'status_code' => 0,
                'error'       => $e->getMessage(),
            ];
        }
    }

    /**
     * Compute a 1–10 health score.
     */
    private function computeHealthScore(array $stats, array $ping): array
    {
        $score  = 10;
        $issues = [];

        if (!$ping['online']) {
            $score -= 5;
            $issues[] = 'API offline';
        } elseif ($ping['response_ms'] > 5000) {
            $score -= 2;
            $issues[] = "Slow API response ({$ping['response_ms']}ms)";
        } elseif ($ping['response_ms'] > 2000) {
            $score -= 1;
            $issues[] = "Slow API response ({$ping['response_ms']}ms)";
        }

        if ($stats['cancel_rate'] > 20) { $score -= 3; $issues[] = "High cancel rate ({$stats['cancel_rate']}%)"; }
        elseif ($stats['cancel_rate'] > 10) { $score -= 1; $issues[] = "Elevated cancel rate ({$stats['cancel_rate']}%)"; }

        if ($stats['delayed_orders'] > 10) { $score -= 2; $issues[] = "{$stats['delayed_orders']} orders delayed >6h"; }
        elseif ($stats['delayed_orders'] > 3) { $score -= 1; $issues[] = "{$stats['delayed_orders']} delayed orders"; }

        $score  = max(1, min(10, $score));
        $status = match(true) {
            $score >= 8  => 'healthy',
            $score >= 5  => 'degraded',
            $score >= 3  => 'unstable',
            default      => 'critical',
        };

        return ['score' => $score, 'status' => $status, 'issues' => $issues];
    }

    private function notifyAdminUnhealthyProvider(ApiProvider $provider, array $health): void
    {
        Log::critical("SUPPLIER CRITICAL: {$provider->name} — Score: {$health['score']}/10 — " . implode(', ', $health['issues']));
        // Email/Slack notification can be added here via Laravel Notifications
    }
}
