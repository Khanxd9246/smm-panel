<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Service;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\ProviderApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * OrderController
 *
 * FIXES:
 * - Renamed method to getServicesByCategory to match web.php route name
 * - Creates a Transaction (deduction) record for every order placed
 * - Validates service status and quantity
 */
class OrderController extends Controller
{
    public function __construct(protected \App\Services\OrderService $orderService)
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $orders = Order::with('service')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        $categories = Category::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'icon', 'color']);

        return view('orders.create', compact('categories'));
    }

    /**
     * FIXED: This method name now matches the route: 
     * Route::get('services-by-category', [OrderController::class, 'getServicesByCategory'])->name('services_by_category');
     */
    public function getServicesByCategory(Request $request)
    {
        $categoryId = $request->integer('category_id');

        if (!$categoryId) {
            return response()->json([]);
        }

        $services = Cache::remember(
            "services_cat_{$categoryId}_admin",
            120,
            fn () => Service::where('status', 'active')
                ->where('category_id', $categoryId)
                ->where('admin_visible', true)          // Phase 3: only admin-approved
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get([
                    'id', 'name', 'admin_name', 'rate', 'admin_price',
                    'min', 'max', 'description', 'admin_description',
                    'min_time', 'max_time',
                    'delivery_time_label', 'delivery_speed',
                    'estimated_start_min', 'estimated_complete_min',
                    'has_refill', 'is_premium', 'delivery_badge',
                ])
        );

        // Map to user-safe display fields
        $mapped = $services->map(fn ($s) => [
            'id'               => $s->id,
            'name'             => $s->display_name,
            'description'      => $s->display_description,
            'rate'             => $s->display_price,
            'min'              => $s->min,
            'max'              => $s->max,
            'delivery_label'   => $s->delivery_label,
            'delivery_color'   => $s->delivery_color,
            'est_start'        => $s->estimated_start_label,
            'est_complete'     => $s->estimated_complete_label,
            'has_refill'       => $s->has_refill,
            'is_premium'       => $s->is_premium,
        ]);

        return response()->json($mapped);
    }

    public function store(\App\Http\Requests\StoreOrderRequest $request)
    {
        $validated = $request->validated();

        if ($this->orderService->isDuplicateOrder(Auth::id(), $validated['service_id'], $validated['link'])) {
            return back()->withErrors(['error' => 'You already placed an identical order a moment ago. Please wait 60 seconds.']);
        }

        try {
            $order = $this->orderService->createOrder($validated);

            Log::info('Order placed', [
                'order_id'   => $order->id,
                'user_id'    => Auth::id(),
                'service_id' => $validated['service_id'],
                'total'      => $order->total,
                'ip'         => $request->ip(),
            ]);

            return redirect()->route('orders.show', $order->id)
                ->with('success', "Order #{$order->id} placed successfully!");

        } catch (\Exception $e) {
            Log::error('Order creation failed', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ]);
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * GET /orders/{order}/status — live provider status poll (AJAX)
     * Called every N seconds from the order detail page.
     * Only queries the provider if the order has an api_order_id and is not terminal.
     */
    public function liveStatus(Order $order)
    {
        abort_unless($order->user_id === Auth::id(), 403);

        $terminal = ['completed', 'cancelled', 'refunded', 'error'];

        // If already terminal, just return current DB state — no provider call
        if (in_array(strtolower($order->status), $terminal, true)) {
            return response()->json([
                'status'     => $order->status,
                'remains'    => $order->remains,
                'start_count'=> $order->start_count,
                'terminal'   => true,
                'source'     => 'db',
            ]);
        }

        // If no api_order_id, nothing to poll
        if (!$order->api_order_id) {
            return response()->json([
                'status'   => $order->status,
                'remains'  => $order->remains,
                'terminal' => false,
                'source'   => 'db',
            ]);
        }

        try {
            $order->load('service.apiProvider');
            $provider = $order->service->apiProvider ?? null;

            if (!$provider) {
                return response()->json([
                    'status'   => $order->status,
                    'remains'  => $order->remains,
                    'terminal' => false,
                    'source'   => 'db',
                ]);
            }

            $api  = new ProviderApiService($provider);
            $data = $api->getStatus($order->api_order_id);

            // Normalise provider status string
            $rawStatus = strtolower($data['status'] ?? $order->status);
            $statusMap = [
                'inprogress' => 'in progress',
                'processing' => 'in progress',
                'active'     => 'in progress',
                'canceled'   => 'cancelled',
            ];
            $newStatus = $statusMap[$rawStatus] ?? $rawStatus;

            $remains    = isset($data['remains'])     ? (int) $data['remains']     : $order->remains;
            $startCount = isset($data['start_count']) ? (int) $data['start_count'] : $order->start_count;

            // Persist only if something changed (avoid needless writes)
            $changed = ($newStatus !== $order->status)
                    || ($remains    !== (int) $order->remains)
                    || ($startCount !== (int) $order->start_count);

            if ($changed) {
                $order->update([
                    'status'      => $newStatus,
                    'remains'     => $remains,
                    'start_count' => $startCount,
                ]);
            }

            $isTerminal = in_array($newStatus, $terminal, true);

            return response()->json([
                'status'      => $newStatus,
                'remains'     => $remains,
                'start_count' => $startCount,
                'terminal'    => $isTerminal,
                'source'      => 'provider',
                'changed'     => $changed,
            ]);

        } catch (\Throwable $e) {
            Log::warning('Live status poll failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'status'   => $order->status,
                'remains'  => $order->remains,
                'terminal' => false,
                'source'   => 'db',
                'error'    => 'Provider unreachable',
            ]);
        }
    }

    public function show(Order $order)
    {
        abort_unless($order->user_id === Auth::id(), 403);
        $order->load('service');

        return view('orders.show', compact('order'));
    }
}
