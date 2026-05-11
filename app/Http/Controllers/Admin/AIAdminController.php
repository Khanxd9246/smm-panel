<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Category;
use App\Models\ApiProvider;
use App\Models\BalanceTransaction;
use App\Services\AIService;
use App\Services\ServiceQualityService;
use App\Services\PricingService;
use App\Services\SupplierHealthService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AIAdminController extends Controller
{
    public function __construct(
        private AIService             $ai,
        private ServiceQualityService $qualityService,
        private PricingService        $pricing,
        private SupplierHealthService $supplierHealth,
        private WalletService         $wallet,
    ) {
        $this->middleware(['auth', 'admin']);
    }

    // ── Service Search (AJAX) ─────────────────────────────────────────────

    /**
     * Fuzzy service search — returns JSON results for instant search.
     * GET /admin/ai/services/search?q=cheap+insta+followers
     */
    public function searchServices(Request $request)
    {
        $term = $request->get('q', '');

        if (strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $results = Service::with(['category:id,name', 'apiProvider:id,name'])
            ->search($term)
            ->select(['id', 'name', 'rate', 'status', 'quality_score', 'delivery_badge', 'category_id', 'api_provider_id'])
            ->limit(15)
            ->get()
            ->map(fn ($s) => [
                'id'            => $s->id,
                'name'          => $s->name,
                'rate'          => $s->rate,
                'status'        => $s->status,
                'quality_score' => $s->quality_score,
                'delivery_badge'=> $s->delivery_badge,
                'category'      => $s->category?->name,
                'provider'      => $s->apiProvider?->name,
            ]);

        return response()->json(['results' => $results]);
    }

    /**
     * Lookup service by supplier service ID — AJAX.
     * GET /admin/ai/services/lookup?supplier_id=1023&provider_id=2
     */
    public function lookupBySupplierServiceId(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|integer',
            'provider_id' => 'nullable|integer',
        ]);

        $query = Service::with(['category', 'apiProvider'])
            ->where('api_service_id', $request->supplier_id);

        if ($request->provider_id) {
            $query->where('api_provider_id', $request->provider_id);
        }

        $service = $query->first();

        if (!$service) {
            return response()->json(['found' => false, 'message' => 'No service found with that supplier ID']);
        }

        $stats = $this->qualityService->getServiceStats($service->id);

        return response()->json([
            'found'   => true,
            'service' => [
                'id'                   => $service->id,
                'name'                 => $service->name,
                'rate'                 => $service->rate,
                'supplier_rate'        => $service->supplier_rate,
                'status'               => $service->status,
                'quality_score'        => $service->quality_score,
                'quality_status'       => $service->quality_status,
                'delivery_badge'       => $service->delivery_badge,
                'estimated_start'      => $service->estimated_start,
                'estimated_completion' => $service->estimated_completion,
                'has_refill'           => $service->has_refill,
                'category'             => $service->category?->name,
                'provider'             => $service->apiProvider?->name,
                'success_rate'         => $stats['success_rate'],
                'cancel_rate'          => $stats['cancel_rate'],
                'total_orders'         => $stats['total_orders'],
                'edit_url'             => route('admin.services.edit', $service->id),
            ],
        ]);
    }

    // ── Service Management Page ───────────────────────────────────────────

    /**
     * AI-enhanced services index page.
     * GET /admin/ai/services
     */
    public function servicesIndex(Request $request)
    {
        $query = Service::with(['category:id,name', 'apiProvider:id,name,status']);

        // Filters
        if ($request->filled('search'))   $query->search($request->search);
        if ($request->filled('category')) $query->where('category_id', $request->category);
        if ($request->filled('provider')) $query->where('api_provider_id', $request->provider);
        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('quality'))  $query->where('quality_status', $request->quality);
        if ($request->filled('badge'))    $query->where('delivery_badge', $request->badge);
        if ($request->filled('filter'))   $query->byQuality($request->filter);

        if ($request->filled('sort')) {
            $query->sorted($request->sort);
        } else {
            $query->orderByDesc('quality_score');
        }

        $services   = $query->paginate(30)->withQueryString();
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $providers  = ApiProvider::orderBy('name')->get(['id', 'name', 'health_status']);

        // Dashboard stats
        $stats = Cache::remember('admin_ai_service_stats', 300, function () {
            return [
                'total'       => Service::count(),
                'active'      => Service::where('status', 'active')->count(),
                'low_quality' => Service::where('quality_score', '<=', 3)->count(),
                'no_orders'   => Service::doesntHave('orders')->count(),
                'hidden'      => Service::where('is_hidden', true)->count(),
                'premium'     => Service::where('is_premium', true)->count(),
            ];
        });

        return view('admin.services.ai-index', compact('services', 'categories', 'providers', 'stats'));
    }

    // ── Bulk Actions ──────────────────────────────────────────────────────

    /**
     * Handle bulk service actions.
     * POST /admin/ai/services/bulk
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action'      => 'required|in:enable,disable,delete,hide,unhide,mark_premium,remove_premium,move_category,update_margin,remove_low_quality',
            'service_ids' => 'required|array|min:1|max:500',
            'service_ids.*' => 'integer|exists:services,id',
            // Conditional
            'category_id' => 'required_if:action,move_category|integer|exists:categories,id',
            'margin'      => 'required_if:action,update_margin|numeric|min:0|max:500',
        ]);

        $ids    = $request->service_ids;
        $action = $request->action;
        $count  = 0;

        DB::transaction(function () use ($ids, $action, $request, &$count) {
            switch ($action) {
                case 'enable':
                    $count = Service::whereIn('id', $ids)->update(['status' => 'active']);
                    break;

                case 'disable':
                    $count = Service::whereIn('id', $ids)->update(['status' => 'inactive']);
                    break;

                case 'delete':
                    $count = Service::whereIn('id', $ids)->delete();
                    break;

                case 'hide':
                    $count = Service::whereIn('id', $ids)->update(['is_hidden' => true]);
                    break;

                case 'unhide':
                    $count = Service::whereIn('id', $ids)->update(['is_hidden' => false]);
                    break;

                case 'mark_premium':
                    $count = Service::whereIn('id', $ids)->update(['is_premium' => true]);
                    break;

                case 'remove_premium':
                    $count = Service::whereIn('id', $ids)->update(['is_premium' => false]);
                    break;

                case 'move_category':
                    $count = Service::whereIn('id', $ids)->update(['category_id' => $request->category_id]);
                    break;

                case 'update_margin':
                    $pricing = app(PricingService::class);
                    $count   = $pricing->bulkUpdateMargin($ids, (float) $request->margin);
                    break;

                case 'remove_low_quality':
                    $count = Service::whereIn('id', $ids)
                        ->where('quality_score', '<=', 4)
                        ->update(['is_hidden' => true, 'status' => 'inactive']);
                    break;
            }
        });

        Cache::flush(); // Clear service caches after bulk changes

        return response()->json([
            'success' => true,
            'message' => "Bulk action '{$action}' applied to {$count} services.",
            'count'   => $count,
        ]);
    }

    // ── Quality Management ────────────────────────────────────────────────

    /**
     * Run quality scoring for all services.
     * POST /admin/ai/quality/score-all
     */
    public function scoreAllServices()
    {
        $count = $this->qualityService->scoreAllServices();

        return response()->json([
            'success' => true,
            'message' => "Quality scored {$count} services.",
            'count'   => $count,
        ]);
    }

    /**
     * Score a single service via AJAX.
     * POST /admin/ai/services/{id}/score
     */
    public function scoreService(Service $service)
    {
        $result = $this->qualityService->scoreService($service);

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    /**
     * Get low-quality services.
     * GET /admin/ai/quality/low
     */
    public function lowQualityServices()
    {
        $services = Service::with(['category:id,name', 'apiProvider:id,name'])
            ->lowQuality()
            ->orderBy('quality_score')
            ->paginate(30);

        return view('admin.services.low-quality', compact('services'));
    }

    // ── Duplicate Detection ───────────────────────────────────────────────

    /**
     * Show duplicate services.
     * GET /admin/ai/duplicates
     */
    public function duplicates()
    {
        $groups = Cache::remember('duplicate_service_groups', 1800, function () {
            return $this->qualityService->findDuplicates();
        });

        return view('admin.duplicates.index', compact('groups'));
    }

    /**
     * Auto-resolve duplicates (keep best, hide rest).
     * POST /admin/ai/duplicates/resolve
     */
    public function resolveDuplicates(Request $request)
    {
        $request->validate([
            'group'       => 'required|array',
            'keep_id'     => 'required|integer|exists:services,id',
            'hide_ids'    => 'required|array',
            'hide_ids.*'  => 'integer|exists:services,id',
        ]);

        Service::whereIn('id', $request->hide_ids)
            ->where('id', '!=', $request->keep_id)
            ->update(['is_hidden' => true, 'status' => 'inactive']);

        Cache::forget('duplicate_service_groups');

        return response()->json(['success' => true, 'message' => 'Duplicates resolved.']);
    }

    // ── Auto Pricing ──────────────────────────────────────────────────────

    /**
     * Pricing settings page.
     * GET /admin/ai/pricing
     */
    public function pricingIndex()
    {
        $global     = $this->pricing->getGlobalMargin();
        $categories = Category::withCount('services')->get();
        $providers  = ApiProvider::withCount('services')->get();

        return view('admin.pricing.index', compact('global', 'categories', 'providers'));
    }

    /**
     * Update global margin and optionally recalculate all services.
     * POST /admin/ai/pricing/global
     */
    public function updateGlobalMargin(Request $request)
    {
        $request->validate([
            'margin'      => 'required|numeric|min:0|max:500',
            'recalculate' => 'boolean',
        ]);

        \App\Models\Setting::setValue('global_profit_margin', $request->margin);

        $updated = 0;
        if ($request->boolean('recalculate')) {
            $updated = $this->pricing->recalculateAll();
        }

        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => "Global margin set to {$request->margin}%. Updated {$updated} services.",
        ]);
    }

    // ── Supplier Health ───────────────────────────────────────────────────

    /**
     * Supplier health dashboard.
     * GET /admin/ai/suppliers/health
     */
    public function supplierHealth()
    {
        $providers = ApiProvider::withCount('services')
            ->with(['services' => fn ($q) => $q->limit(3)])
            ->orderByDesc('health_score')
            ->get();

        $healthSummary = [
            'healthy'  => $providers->where('health_status', 'healthy')->count(),
            'degraded' => $providers->where('health_status', 'degraded')->count(),
            'unstable' => $providers->where('health_status', 'unstable')->count(),
            'critical' => $providers->where('health_status', 'critical')->count(),
            'unknown'  => $providers->whereNull('health_status')->count(),
        ];

        return view('admin.suppliers.health', compact('providers', 'healthSummary'));
    }

    /**
     * Run health check for all providers — AJAX.
     * POST /admin/ai/suppliers/health-check
     */
    public function runHealthCheck()
    {
        $results = $this->supplierHealth->checkAll();

        return response()->json([
            'success' => true,
            'results' => $results,
            'message' => 'Health check completed for ' . count($results) . ' providers.',
        ]);
    }

    // ── AI Service Analysis ───────────────────────────────────────────────

    /**
     * Run AI analysis on a service.
     * POST /admin/ai/services/{id}/analyze
     */
    public function analyzeService(Service $service)
    {
        $stats = $this->qualityService->getServiceStats($service->id);

        $analysis = $this->ai->analyzeService(array_merge([
            'name'          => $service->name,
            'rate'          => $service->rate,
            'supplier_rate' => $service->supplier_rate,
            'has_refill'    => $service->has_refill,
            'delivery_badge'=> $service->delivery_badge,
        ], $stats));

        // Optionally update description from AI
        if (!empty($analysis['generate_description'])) {
            $desc = $this->ai->generateDescription($service->name, [
                'delivery_time' => $service->estimated_completion,
                'refill'        => $service->has_refill,
                'quality'       => $analysis['quality_score'] . '/10',
            ]);
            $service->update(['ai_description' => $desc]);
        }

        return response()->json(['success' => true, 'analysis' => $analysis]);
    }

    /**
     * Generate AI-powered title for a service.
     * POST /admin/ai/services/{id}/generate-title
     */
    public function generateTitle(Service $service)
    {
        $newTitle = $this->ai->generateTitle($service->name, $service->category?->name ?? '');

        return response()->json([
            'success'    => true,
            'old_title'  => $service->name,
            'new_title'  => $newTitle,
        ]);
    }

    /**
     * Apply AI-generated title to a service.
     * POST /admin/ai/services/{id}/apply-title
     */
    public function applyTitle(Request $request, Service $service)
    {
        $request->validate(['title' => 'required|string|max:200']);
        $service->update(['name' => $request->title]);

        return response()->json(['success' => true]);
    }

    // ── Wallet Management ─────────────────────────────────────────────────

    /**
     * Admin wallet management for a user.
     * POST /admin/users/{user}/wallet
     */
    public function walletAction(Request $request, \App\Models\User $user)
    {
        $request->validate([
            'action' => 'required|in:credit,debit,refund,freeze',
            'amount' => 'required_unless:action,freeze|numeric|min:0.01|max:100000',
            'reason' => 'required|string|min:5|max:500',
        ]);

        $action = $request->action;
        $amount = (float) ($request->amount ?? 0);
        $reason = $request->reason;

        $tx = match($action) {
            'credit' => $this->wallet->credit($user, $amount, $reason, auth()->id()),
            'debit'  => $this->wallet->debit($user, $amount, $reason, auth()->id()),
            'refund' => $this->wallet->refund($user, $amount, $reason, auth()->id()),
            'freeze' => $this->wallet->freeze($user, $reason, auth()->id()),
        };

        return response()->json([
            'success'         => true,
            'message'         => "Wallet action '{$action}' applied.",
            'new_balance'     => $user->fresh()->funds,
            'transaction_id'  => $tx->id,
        ]);
    }

    /**
     * Get wallet transaction history for a user.
     * GET /admin/users/{user}/wallet/history
     */
    public function walletHistory(\App\Models\User $user)
    {
        $transactions = $this->wallet->getHistory($user, 30);

        return response()->json([
            'transactions' => $transactions->items(),
            'total'        => $transactions->total(),
        ]);
    }
}
