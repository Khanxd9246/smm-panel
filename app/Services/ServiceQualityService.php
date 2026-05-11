<?php

namespace App\Services;

use App\Models\Service;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ServiceQualityService — Automatic quality scoring for services.
 *
 * Scores services 1–10 based on real order statistics:
 * - cancel rate, refill availability, delivery speed, success rate, supplier health
 */
class ServiceQualityService
{
    public function __construct(private AIService $ai) {}

    /**
     * Calculate and persist quality score for a single service.
     */
    public function scoreService(Service $service): array
    {
        $stats = $this->getServiceStats($service->id);
        $score = $this->computeScore($stats, $service);
        $tags  = $this->ai->generateTags($service->name, array_merge($stats, ['rate' => $service->rate]));

        $qualityData = [
            'quality_score'   => $score['score'],
            'quality_status'  => $score['status'],
            'cancel_rate'     => $stats['cancel_rate'],
            'success_rate'    => $stats['success_rate'],
            'avg_start_time'  => $stats['avg_start_minutes'],
            'quality_issues'  => json_encode($score['issues']),
            'ai_tags'         => json_encode($tags),
        ];

        $service->update($qualityData);

        Cache::forget("service_quality_{$service->id}");

        return $qualityData;
    }

    /**
     * Score all services in bulk (used by scheduler).
     */
    public function scoreAllServices(int $chunkSize = 50): int
    {
        $count = 0;

        Service::with(['apiProvider'])->chunk($chunkSize, function ($services) use (&$count) {
            foreach ($services as $service) {
                try {
                    $this->scoreService($service);
                    $count++;
                } catch (\Exception $e) {
                    Log::error("Quality scoring failed for service {$service->id}: " . $e->getMessage());
                }
            }
        });

        return $count;
    }

    /**
     * Get real-time stats for a service from order history.
     */
    public function getServiceStats(int $serviceId): array
    {
        return Cache::remember("service_stats_{$serviceId}", 1800, function () use ($serviceId) {
            $stats = DB::selectOne("
                SELECT
                    COUNT(*) AS total_orders,
                    COUNT(*) FILTER (WHERE status = 'completed')   AS completed_orders,
                    COUNT(*) FILTER (WHERE status = 'cancelled')   AS cancelled_orders,
                    COUNT(*) FILTER (WHERE status = 'partial')     AS partial_orders,
                    COALESCE(AVG(
                        CASE WHEN status IN ('completed','partial')
                        THEN EXTRACT(EPOCH FROM (updated_at - created_at))/60
                        END
                    ), 0) AS avg_completion_minutes
                FROM orders
                WHERE service_id = ?
                AND created_at >= NOW() - INTERVAL '30 days'
            ", [$serviceId]);

            $total     = (int) ($stats->total_orders ?? 0);
            $completed = (int) ($stats->completed_orders ?? 0);
            $cancelled = (int) ($stats->cancelled_orders ?? 0);

            return [
                'total_orders'        => $total,
                'completed_orders'    => $completed,
                'cancelled_orders'    => $cancelled,
                'success_rate'        => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
                'cancel_rate'         => $total > 0 ? round(($cancelled / $total) * 100, 2) : 0,
                'avg_start_minutes'   => 0, // Populated from provider sync
                'avg_completion_minutes' => round((float)($stats->avg_completion_minutes ?? 0), 1),
                'has_refill'          => false, // From service record
                'is_stable'           => true,
            ];
        });
    }

    /**
     * Compute quality score (1-10) from stats + service metadata.
     */
    private function computeScore(array $stats, Service $service): array
    {
        $score  = 10;
        $issues = [];

        // Penalize high cancel rate
        if ($stats['cancel_rate'] >= 20) { $score -= 3; $issues[] = "High cancel rate ({$stats['cancel_rate']}%)"; }
        elseif ($stats['cancel_rate'] >= 10) { $score -= 2; $issues[] = "Elevated cancel rate ({$stats['cancel_rate']}%)"; }
        elseif ($stats['cancel_rate'] >= 5)  { $score -= 1; $issues[] = "Moderate cancel rate ({$stats['cancel_rate']}%)"; }

        // Penalize low success rate
        if ($stats['success_rate'] > 0 && $stats['success_rate'] < 70)  { $score -= 3; $issues[] = "Poor success rate ({$stats['success_rate']}%)"; }
        elseif ($stats['success_rate'] > 0 && $stats['success_rate'] < 85) { $score -= 2; $issues[] = "Below-average success rate ({$stats['success_rate']}%)"; }

        // Penalize slow delivery
        $avgCompletion = $stats['avg_completion_minutes'];
        if ($avgCompletion > 1440) { $score -= 2; $issues[] = 'Very slow delivery (>24h)'; }
        elseif ($avgCompletion > 360) { $score -= 1; $issues[] = 'Slow delivery (>6h)'; }

        // Penalize no refill (if tracked)
        if (isset($service->has_refill) && !$service->has_refill) {
            $score -= 1;
            $issues[] = 'No refill available';
        }

        // Dead/disabled provider
        if ($service->apiProvider && $service->apiProvider->status !== 'active') {
            $score -= 3;
            $issues[] = 'Supplier is inactive or offline';
        }

        // Penalize inactive service with no orders
        if ($stats['total_orders'] === 0) {
            $issues[] = 'No orders in last 30 days';
        }

        $score = max(1, min(10, $score));

        $status = match(true) {
            $score >= 8  => 'excellent',
            $score >= 6  => 'good',
            $score >= 4  => 'fair',
            default      => 'poor',
        };

        return ['score' => $score, 'status' => $status, 'issues' => $issues];
    }

    /**
     * Find all duplicate services (same category, similar names).
     */
    public function findDuplicates(): array
    {
        $services = Service::with(['category', 'apiProvider'])->get();
        $groups   = [];
        $seen     = [];

        foreach ($services as $service) {
            if (isset($seen[$service->id])) continue;

            $similar = $services->filter(function ($other) use ($service) {
                if ($other->id === $service->id) return false;
                if ($other->category_id !== $service->category_id) return false;

                // Similar-name detection via normalized comparison
                $a = $this->normalizeName($service->name);
                $b = $this->normalizeName($other->name);

                similar_text($a, $b, $pct);
                return $pct >= 75;
            });

            if ($similar->isNotEmpty()) {
                $group = $similar->prepend($service)->values();
                foreach ($group as $s) { $seen[$s->id] = true; }
                $groups[] = $group->toArray();
            }
        }

        return $groups;
    }

    private function normalizeName(string $name): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', $name));
    }
}
