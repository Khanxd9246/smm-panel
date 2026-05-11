<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ApiProvider;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PricingService — Dynamic pricing engine with hierarchical margin rules.
 *
 * Priority:
 *  1. Service-level custom margin (highest priority)
 *  2. Category-level margin
 *  3. Supplier-level margin
 *  4. Global margin (lowest priority fallback)
 */
class PricingService
{
    /**
     * Calculate the final user-facing price for a service.
     *
     * @param  float      $supplierRate   Raw cost from supplier
     * @param  int|null   $serviceId      For service-level margin lookup
     * @param  int|null   $categoryId     For category-level margin lookup
     * @param  int|null   $providerId     For provider-level margin lookup
     */
    public function calculatePrice(
        float $supplierRate,
        ?int  $serviceId  = null,
        ?int  $categoryId = null,
        ?int  $providerId = null
    ): float {
        $margin = $this->resolveMargin($serviceId, $categoryId, $providerId);
        return $this->applyMargin($supplierRate, $margin);
    }

    /**
     * Recalculate and update prices for all services in bulk.
     * Returns count of updated services.
     */
    public function recalculateAll(): int
    {
        $updated = 0;
        $global  = $this->getGlobalMargin();

        Service::with(['category', 'apiProvider'])->chunk(100, function ($services) use ($global, &$updated) {
            foreach ($services as $service) {
                if (!$service->supplier_rate) continue; // No supplier cost stored

                $margin = $this->resolveMargin(
                    $service->id,
                    $service->category_id,
                    $service->api_provider_id
                );

                $newRate = $this->applyMargin($service->supplier_rate, $margin);

                if (abs($newRate - $service->rate) > 0.0001) {
                    $service->update(['rate' => $newRate]);
                    $updated++;
                }
            }
        });

        return $updated;
    }

    /**
     * Bulk update prices for a set of service IDs with a given margin.
     */
    public function bulkUpdateMargin(array $serviceIds, float $marginPercent): int
    {
        $updated = 0;

        Service::whereIn('id', $serviceIds)
            ->whereNotNull('supplier_rate')
            ->chunk(50, function ($services) use ($marginPercent, &$updated) {
                foreach ($services as $service) {
                    $newRate = $this->applyMargin($service->supplier_rate, $marginPercent);
                    $service->update([
                        'rate'           => $newRate,
                        'custom_margin'  => $marginPercent,
                    ]);
                    $updated++;
                }
            });

        return $updated;
    }

    /**
     * Get the effective margin for a service (hierarchy resolution).
     */
    public function resolveMargin(?int $serviceId, ?int $categoryId, ?int $providerId): float
    {
        // 1. Service-level custom margin
        if ($serviceId) {
            $svc = Service::find($serviceId, ['custom_margin']);
            if ($svc && $svc->custom_margin !== null) {
                return (float) $svc->custom_margin;
            }
        }

        // 2. Category-level margin
        if ($categoryId) {
            $cat = \App\Models\Category::find($categoryId, ['profit_margin']);
            if ($cat && $cat->profit_margin !== null) {
                return (float) $cat->profit_margin;
            }
        }

        // 3. Provider-level margin
        if ($providerId) {
            $provider = ApiProvider::find($providerId, ['profit_margin']);
            if ($provider && $provider->profit_margin !== null) {
                return (float) $provider->profit_margin;
            }
        }

        // 4. Global fallback
        return $this->getGlobalMargin();
    }

    /**
     * Get global profit margin from settings (default 40%).
     */
    public function getGlobalMargin(): float
    {
        return (float) Setting::getValue('global_profit_margin', 40);
    }

    /**
     * Apply margin percentage to supplier rate.
     */
    private function applyMargin(float $supplierRate, float $marginPercent): float
    {
        return round($supplierRate * (1 + $marginPercent / 100), 6);
    }
}
