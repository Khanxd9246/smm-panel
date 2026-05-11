<?php

namespace App\Console\Commands;

use App\Services\ServiceQualityService;
use App\Services\SupplierHealthService;
use App\Services\PricingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * RunAIMaintenanceCommand — Single unified command for all AI maintenance tasks.
 *
 * Usage:
 *   php artisan smm:ai-maintenance            # Run all tasks
 *   php artisan smm:ai-maintenance --quality  # Quality scoring only
 *   php artisan smm:ai-maintenance --health   # Supplier health only
 *   php artisan smm:ai-maintenance --pricing  # Auto-pricing only
 */
class RunAIMaintenanceCommand extends Command
{
    protected $signature = 'smm:ai-maintenance
                            {--quality  : Run quality scoring only}
                            {--health   : Run supplier health checks only}
                            {--pricing  : Run auto-pricing recalculation only}';

    protected $description = 'Run AI-powered maintenance: quality scoring, supplier health, and auto-pricing';

    public function __construct(
        private ServiceQualityService $qualityService,
        private SupplierHealthService $supplierHealth,
        private PricingService        $pricing,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $runAll     = !$this->option('quality') && !$this->option('health') && !$this->option('pricing');
        $startTime  = microtime(true);

        $this->info('[SMM AI Maintenance] Starting at ' . now()->toDateTimeString());

        if ($runAll || $this->option('health')) {
            $this->runHealthChecks();
        }

        if ($runAll || $this->option('quality')) {
            $this->runQualityScoring();
        }

        if ($runAll || $this->option('pricing')) {
            $this->runAutopricing();
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->info("[SMM AI Maintenance] Done in {$elapsed}s");

        return Command::SUCCESS;
    }

    private function runHealthChecks(): void
    {
        $this->info('  → Running supplier health checks...');

        try {
            $results = $this->supplierHealth->checkAll();
            $critical = collect($results)->where('status', 'critical')->count();

            $this->info("  ✓ Checked " . count($results) . " suppliers. Critical: {$critical}");

            if ($critical > 0) {
                $this->warn("  ⚠ {$critical} suppliers in critical state — check admin dashboard!");
                Log::warning("AI Maintenance: {$critical} critical suppliers detected.");
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Health check failed: " . $e->getMessage());
            Log::error('AI Maintenance health check failed', ['error' => $e->getMessage()]);
        }
    }

    private function runQualityScoring(): void
    {
        $this->info('  → Running quality scoring...');

        try {
            $count = $this->qualityService->scoreAllServices(50);
            $this->info("  ✓ Scored {$count} services");
        } catch (\Exception $e) {
            $this->error("  ✗ Quality scoring failed: " . $e->getMessage());
            Log::error('AI Maintenance quality scoring failed', ['error' => $e->getMessage()]);
        }
    }

    private function runAutopricing(): void
    {
        $this->info('  → Running auto-pricing recalculation...');

        try {
            $updated = $this->pricing->recalculateAll();
            $this->info("  ✓ Updated prices for {$updated} services");
        } catch (\Exception $e) {
            $this->error("  ✗ Pricing recalculation failed: " . $e->getMessage());
            Log::error('AI Maintenance pricing failed', ['error' => $e->getMessage()]);
        }
    }
}
