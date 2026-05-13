<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApiProvider;
use App\Models\Category;
use App\Models\Order;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ProviderSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function __construct(
        private readonly ProviderSyncService $syncService,
    ) {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $stats = Cache::remember('admin_dashboard_stats', 300, function () {
            return DB::selectOne("
                SELECT
                    COUNT(*) FILTER (WHERE 1=1)                             AS total_orders,
                    COUNT(*) FILTER (WHERE status IN ('pending','in progress')) AS pending_orders,
                    COUNT(*) FILTER (WHERE status = 'completed')            AS completed_orders,
                    (SELECT COUNT(*) FROM users WHERE status = 'active')    AS active_users,
                    (SELECT SUM(amount) FROM transactions
                        WHERE type = 'deposit' AND status = 'completed')    AS total_revenue,
                    (SELECT COUNT(*) FROM transactions
                        WHERE status = 'pending' AND type = 'deposit')      AS pending_deposits,
                    (SELECT COUNT(*) FROM tickets WHERE status != 'closed') AS open_tickets
                FROM orders
            ");
        });

        $recent_orders = Order::with(['user:id,name,email', 'service:id,name'])
            ->latest()
            ->take(10)
            ->get();

        $recent_users = User::latest()->take(6)->get(['id', 'name', 'email', 'funds', 'created_at', 'status']);

        $providers = Cache::remember('admin_providers_list', 600, fn () =>
            ApiProvider::withCount('services')->get()
        );

        // Pending deposits — shown prominently so admin never misses them
        $pending_deposits = Transaction::where('status', 'pending')
            ->where('type', 'deposit')
            ->where('gateway', 'manual')
            ->with('user:id,name,email')
            ->latest()
            ->take(5)
            ->get();

        $total_orders         = (int)   ($stats->total_orders         ?? 0);
        $pending_orders       = (int)   ($stats->pending_orders       ?? 0);
        $completed_orders     = (int)   ($stats->completed_orders     ?? 0);
        $active_users         = (int)   ($stats->active_users         ?? 0);
        $total_revenue        = (float) ($stats->total_revenue        ?? 0);
        $pending_deposits_count = (int) ($stats->pending_deposits     ?? 0);
        $open_tickets         = (int)   ($stats->open_tickets         ?? 0);

        return view('admin.dashboard', compact(
            'total_orders',
            'pending_orders',
            'completed_orders',
            'active_users',
            'total_revenue',
            'pending_deposits_count',
            'open_tickets',
            'recent_orders',
            'recent_users',
            'providers',
            'pending_deposits',
        ));
    }

    // ── Users ─────────────────────────────────────────────────────────────────

    public function usersIndex(Request $request)
    {
        $query = User::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $users = $query->withCount(['orders', 'transactions'])
            ->orderBy('created_at', 'desc')
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function usersAddFunds(Request $request, User $user)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:10000',
            'reason' => 'required|string|min:5|max:255',
        ]);

        try {
            DB::transaction(function () use ($user, $validated) {
                $lockedUser    = User::lockForUpdate()->findOrFail($user->id);
                $balanceBefore = $lockedUser->funds;

                $lockedUser->increment('funds', $validated['amount']);

                Transaction::create([
                    'user_id'     => $lockedUser->id,
                    'amount'      => $validated['amount'],
                    'type'        => 'deposit',
                    'description' => 'Admin credit: ' . $validated['reason'],
                    'status'      => 'completed',
                    'reference'   => 'admin_' . Auth::id() . '_' . time(),
                    'gateway'     => 'admin',
                ]);

                $this->auditLog('add_funds', 'User', $lockedUser->id, [
                    'balance_before' => $balanceBefore,
                    'amount_added'   => $validated['amount'],
                    'balance_after'  => $balanceBefore + $validated['amount'],
                    'reason'         => $validated['reason'],
                ]);
            });

            Cache::forget('admin_dashboard_stats');
            return back()->with('success', "Added \${$validated['amount']} to {$user->name}'s account.");

        } catch (\Throwable $e) {
            Log::error('Admin add funds failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to add funds. Please try again.']);
        }
    }

    public function usersBan(Request $request, User $user)
    {
        $validated = $request->validate(['reason' => 'required|string|min:5|max:255']);

        if ($user->is_admin) {
            return back()->withErrors(['error' => 'Cannot ban admin accounts through this interface.']);
        }

        $user->update(['status' => 'banned']);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $this->auditLog('ban_user', 'User', $user->id, ['reason' => $validated['reason']]);
        Cache::forget('admin_dashboard_stats');

        return back()->with('success', "User {$user->name} has been banned.");
    }

    public function usersUnban(Request $request, User $user)
    {
        $user->update(['status' => 'active']);
        $this->auditLog('unban_user', 'User', $user->id, []);
        return back()->with('success', "User {$user->name} has been unbanned.");
    }

    // ── Orders ────────────────────────────────────────────────────────────────

    public function ordersIndex(Request $request)
    {
        $query = Order::with(['user:id,name,email', 'service:id,name']);

        if ($status = $request->get('status')) $query->where('status', $status);
        if ($userId = $request->get('user_id')) $query->where('user_id', $userId);
        if ($search = $request->get('search')) {
            $query->where(fn ($q) => $q->where('id', $search)->orWhere('link', 'ilike', "%{$search}%"));
        }

        $orders = $query->latest()->paginate(30)->withQueryString();
        return view('admin.orders.index', compact('orders'));
    }

    public function ordersUpdateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in progress,completed,partial,cancelled,refunded,error',
        ]);

        $oldStatus = $order->status;
        $order->update(['status' => $validated['status']]);
        $this->auditLog('update_order_status', 'Order', $order->id, ['from' => $oldStatus, 'to' => $validated['status']]);
        Cache::forget('admin_dashboard_stats');

        return back()->with('success', "Order #{$order->id} status updated.");
    }

    // ── Transactions ──────────────────────────────────────────────────────────

    public function transactionsIndex(Request $request)
    {
        $query = Transaction::with('user:id,name,email');

        if ($status = $request->get('status')) $query->where('status', $status);
        if ($type   = $request->get('type'))   $query->where('type', $type);

        $transactions = $query->latest()->paginate(30)->withQueryString();
        return view('admin.transactions.index', compact('transactions'));
    }

    public function transactionsApprove(Request $request, Transaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending transactions can be approved.']);
        }

        try {
            DB::transaction(function () use ($transaction) {
                $user = User::lockForUpdate()->findOrFail($transaction->user_id);
                $transaction->update(['status' => 'completed']);
                $user->increment('funds', $transaction->amount);

                $this->auditLog('approve_transaction', 'Transaction', $transaction->id, [
                    'user_id'   => $user->id,
                    'amount'    => $transaction->amount,
                    'reference' => $transaction->reference,
                ]);

                DB::table('payment_logs')
                    ->where('transaction_id', $transaction->id)
                    ->update(['status' => 'completed', 'updated_at' => now()]);
            });

            Cache::forget('admin_dashboard_stats');
            return back()->with('success', 'Transaction approved and funds credited.');

        } catch (\Throwable $e) {
            Log::error('Transaction approval failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Approval failed. Please try again.']);
        }
    }

    public function transactionsReject(Request $request, Transaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending transactions can be rejected.']);
        }

        $validated = $request->validate(['reason' => 'required|string|min:5|max:255']);
        $transaction->update(['status' => 'failed']);
        $this->auditLog('reject_transaction', 'Transaction', $transaction->id, ['reason' => $validated['reason']]);

        DB::table('payment_logs')
            ->where('transaction_id', $transaction->id)
            ->update(['status' => 'failed', 'error_message' => $validated['reason']]);

        return back()->with('success', 'Transaction rejected.');
    }

    // ── Providers ─────────────────────────────────────────────────────────────

    public function providersIndex()
    {
        $providers = ApiProvider::withCount('services')->get();
        return view('admin.providers.index', compact('providers'));
    }

    public function providersCreate() { return view('admin.providers.create'); }

    public function providersStore(Request $request)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:100|unique:api_providers',
            'url'                 => 'required|url|max:255',
            'api_key'             => 'required|string|max:255',
            'percentage_increase' => 'required|numeric|min:0|max:10000',
        ]);

        try {
            $provider = ApiProvider::create($validated + ['status' => 'active']);
            $this->auditLog('create_provider', 'ApiProvider', $provider->id, ['name' => $provider->name]);
            Cache::forget('admin_providers_list');
            return redirect()->route('admin.providers.index')->with('success', 'Provider added. Click Sync to import services.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Failed to create provider.']);
        }
    }

    public function providersEdit(ApiProvider $provider) { return view('admin.providers.edit', compact('provider')); }

    public function providersUpdate(Request $request, ApiProvider $provider)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:100|unique:api_providers,name,' . $provider->id,
            'url'                 => 'required|url|max:255',
            'api_key'             => 'nullable|string|max:255',
            'percentage_increase' => 'required|numeric|min:0|max:10000',
            'status'              => 'required|in:active,inactive',
        ]);

        if (empty($validated['api_key'])) unset($validated['api_key']);
        $provider->update($validated);
        $this->auditLog('update_provider', 'ApiProvider', $provider->id, $validated);
        Cache::forget('admin_providers_list');

        return redirect()->route('admin.providers.index')->with('success', 'Provider updated.');
    }

    public function syncProvider(Request $request, ApiProvider $provider)
    {
        try {
            $count = $this->syncService->syncProvider($provider);
            $this->auditLog('sync_provider', 'ApiProvider', $provider->id, ['services_synced' => $count]);
            Cache::forget('admin_providers_list');
            return back()->with('success', "Synced {$count} services from {$provider->name}.");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => "Sync failed: {$e->getMessage()}"]);
        }
    }

    public function syncAll()
    {
        try {
            $count = $this->syncService->syncAll();
            $this->auditLog('sync_all_providers', 'ApiProvider', 0, ['total_synced' => $count]);
            Cache::forget('admin_providers_list');
            return back()->with('success', "Synced {$count} total services.");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    public function syncServices()
    {
        try {
            $count = $this->syncService->syncAll();
            Cache::forget('admin_providers_list');
            return response()->json(['message' => "Synced {$count} services successfully."]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Sync failed: ' . $e->getMessage()], 500);
        }
    }

    public function syncOrders()
    {
        try {
            $updated = 0;
            $orders  = \App\Models\Order::whereIn('status', ['pending', 'in progress'])
                ->whereNotNull('api_order_id')->get();

            foreach ($orders as $order) {
                try {
                    if ($order->service && $order->service->apiProvider) {
                        $api    = new \App\Services\ProviderApiService($order->service->apiProvider);
                        $status = $api->getOrderStatus($order->api_order_id);
                        if ($status && isset($status['status'])) {
                            $order->update(['status' => strtolower($status['status'])]);
                            $updated++;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("syncOrders: order #{$order->id} failed: " . $e->getMessage());
                }
            }

            Cache::forget('admin_dashboard_stats');
            return response()->json(['message' => "Updated {$updated} orders."]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Order sync failed: ' . $e->getMessage()], 500);
        }
    }

    // ── Services ──────────────────────────────────────────────────────────────

    public function servicesIndex(Request $request)
    {
        $query = Service::with(['apiProvider:id,name', 'category:id,name']);

        if ($tier       = $request->get('tier'))        $query->where('tier', $tier);
        if ($status     = $request->get('status'))      $query->where('status', $status);
        if ($categoryId = $request->get('category_id')) $query->where('category_id', $categoryId);
        if ($search     = $request->get('q'))           $query->search($search);

        $sortBy  = in_array($request->get('sort_by'), ['name', 'rate', 'min_time', 'created_at', 'sort_order'], true)
                   ? $request->get('sort_by') : 'name';
        $sortDir = $request->get('sort_direction', 'asc');

        $services   = $query->orderBy($sortBy, $sortDir)->paginate(50)->withQueryString();
        $categories = \App\Models\Category::orderBy('name')->get(['id', 'name']);

        return view('admin.services.index', compact('services', 'categories'));
    }

    public function servicesToggle(Service $service)
    {
        $newStatus = $service->status === 'active' ? 'inactive' : 'active';
        $service->update(['status' => $newStatus]);
        $this->auditLog('toggle_service', 'Service', $service->id, ['status' => $newStatus]);
        return back()->with('success', 'Service status updated.');
    }

    // ── Phase 3: Admin service management ────────────────────────────────────

    /**
     * GET admin/services/manage
     * Full management table: all services with visibility toggles, price overrides, delivery labels.
     */
    public function servicesManage(Request $request)
    {
        $query = Service::with(['apiProvider:id,name', 'category:id,name']);

        if ($cat    = $request->get('category_id')) $query->where('category_id', $cat);
        if ($vis    = $request->get('visible'))     $query->where('admin_visible', $vis === '1');
        if ($search = $request->get('q'))           $query->search($search);
        if ($speed  = $request->get('speed'))       $query->where('delivery_speed', $speed);

        $sortBy  = in_array($request->get('sort_by'), ['name','rate','admin_price','sort_order','orders_count'], true)
                   ? $request->get('sort_by') : 'sort_order';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $services   = $query->orderBy($sortBy, $sortDir)->paginate(50)->withQueryString();
        $categories = \App\Models\Category::orderBy('name')->get(['id','name']);
        $stats = [
            'total'   => Service::count(),
            'visible' => Service::where('admin_visible', true)->count(),
            'hidden'  => Service::where('admin_visible', false)->count(),
            'custom_price' => Service::whereNotNull('admin_price')->where('admin_price', '>', 0)->count(),
        ];

        return view('admin.services.manage', compact('services', 'categories', 'stats'));
    }

    /**
     * POST admin/services/{service}/visibility
     * Toggle a single service's admin_visible flag (AJAX-friendly).
     */
    public function servicesVisibility(Request $request, Service $service)
    {
        $visible = $request->boolean('visible');
        $service->update(['admin_visible' => $visible]);
        $this->auditLog('service_visibility', 'Service', $service->id, ['admin_visible' => $visible]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'admin_visible' => $visible]);
        }
        return back()->with('success', "Service " . ($visible ? 'shown to' : 'hidden from') . " users.");
    }

    /**
     * POST admin/services/bulk-visibility
     * Toggle visibility for multiple services at once.
     */
    public function servicesBulkVisibility(Request $request)
    {
        $request->validate([
            'ids'     => 'required|array',
            'ids.*'   => 'integer|exists:services,id',
            'visible' => 'required|boolean',
        ]);

        $count = Service::whereIn('id', $request->ids)
                         ->update(['admin_visible' => $request->boolean('visible')]);

        $this->auditLog('bulk_visibility', 'Service', null, [
            'count'   => $count,
            'visible' => $request->boolean('visible'),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'updated' => $count]);
        }
        return back()->with('success', "{$count} service(s) updated.");
    }

    /**
     * POST admin/services/{service}/admin-update
     * Save admin overrides: custom name, price, description, delivery label, speed, sort order.
     */
    public function servicesAdminUpdate(Request $request, Service $service)
    {
        $validated = $request->validate([
            'admin_name'             => 'nullable|string|max:255',
            'admin_price'            => 'nullable|numeric|min:0|max:99999',
            'admin_description'      => 'nullable|string|max:2000',
            'delivery_time_label'    => 'nullable|string|max:80',
            'delivery_speed'         => 'nullable|in:instant,fast,standard,slow',
            'estimated_start_min'    => 'nullable|integer|min:0|max:99999',
            'estimated_complete_min' => 'nullable|integer|min:0|max:999999',
            'sort_order'             => 'nullable|integer|min:0|max:99999',
            'admin_visible'          => 'nullable|boolean',
        ]);

        // Treat empty strings as null
        foreach (['admin_name', 'admin_price', 'admin_description', 'delivery_time_label'] as $k) {
            if (isset($validated[$k]) && trim($validated[$k]) === '') {
                $validated[$k] = null;
            }
        }

        $service->update($validated);

        // Bust the category services cache so the new data is served immediately
        \Illuminate\Support\Facades\Cache::forget("services_cat_{$service->category_id}_admin");

        $this->auditLog('admin_update_service', 'Service', $service->id, $validated);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'service' => $service->fresh()]);
        }
        return back()->with('success', "Service "{$service->display_name}" updated.");
    }

    // ── Tickets ───────────────────────────────────────────────────────────────

    public function ticketsIndex(Request $request)
    {
        $query = Ticket::with(['user:id,name,email', 'messages.user:id,name']);
        if ($status = $request->get('status')) $query->where('status', $status);
        $tickets = $query->latest()->paginate(20)->withQueryString();
        return view('admin.tickets.index', compact('tickets'));
    }

    public function ticketsReply(Request $request, Ticket $ticket)
    {
        $validated = $request->validate(['message' => 'required|string|max:5000']);
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => Auth::id(),
            'message'   => $validated['message'],
            'is_admin'  => true,
        ]);
        $ticket->update(['status' => 'pending']);
        return back()->with('success', 'Reply sent.');
    }

    public function ticketsClose(Ticket $ticket)
    {
        $ticket->update(['status' => 'closed', 'closed_at' => now()]);
        $this->auditLog('close_ticket', 'Ticket', $ticket->id, []);
        return back()->with('success', 'Ticket closed.');
    }

    // ── Logs ──────────────────────────────────────────────────────────────────

    public function activityLogs()
    {
        $logs = ActivityLog::with('user:id,name,email')->latest()->paginate(50)->withQueryString();
        return view('admin.logs.activity', compact('logs'));
    }

    public function paymentLogs(Request $request)
    {
        $query = DB::table('payment_logs')
            ->leftJoin('users', 'payment_logs.user_id', '=', 'users.id')
            ->select('payment_logs.*', 'users.name as user_name', 'users.email as user_email');

        if ($gateway = $request->get('gateway')) $query->where('payment_logs.gateway', $gateway);
        if ($status  = $request->get('status'))  $query->where('payment_logs.status', $status);

        $logs = $query->orderByDesc('payment_logs.created_at')->paginate(50);
        return view('admin.logs.payments', compact('logs'));
    }

    public function providerLogs(Request $request)
    {
        $query = DB::table('provider_logs')
            ->join('api_providers', 'provider_logs.api_provider_id', '=', 'api_providers.id')
            ->select('provider_logs.*', 'api_providers.name as provider_name');

        if ($providerId = $request->get('provider_id')) $query->where('provider_logs.api_provider_id', $providerId);

        $logs      = $query->orderByDesc('provider_logs.created_at')->paginate(50);
        $providers = ApiProvider::all(['id', 'name']);
        return view('admin.logs.providers', compact('logs', 'providers'));
    }

    public function settings()
    {
        $whatsappNumber  = \App\Models\SiteSetting::get('whatsapp_number', '');
        $whatsappMessage = \App\Models\SiteSetting::get('whatsapp_message', 'Hi, I submitted a fund request. TXN ID: ');
        return view('admin.settings', compact('whatsappNumber', 'whatsappMessage'));
    }

    public function settingsSave(Request $request)
    {
        $validated = $request->validate([
            'whatsapp_number'  => 'nullable|string|max:20',
            'whatsapp_message' => 'nullable|string|max:255',
        ]);
        \App\Models\SiteSetting::set('whatsapp_number',  $validated['whatsapp_number']  ?? '');
        \App\Models\SiteSetting::set('whatsapp_message', $validated['whatsapp_message'] ?? '');
        return back()->with('success', 'Settings saved successfully.');
    }

    // ── Appearance ────────────────────────────────────────────────────────────

    public function appearance()
    {
        return view('admin.appearance', [
            'darkPrimary'  => \App\Models\SiteSetting::get('appearance_dark_primary',  ''),
            'darkAccent'   => \App\Models\SiteSetting::get('appearance_dark_accent',   ''),
            'darkBase'     => \App\Models\SiteSetting::get('appearance_dark_base',     ''),
            'darkCard'     => \App\Models\SiteSetting::get('appearance_dark_card',     ''),
            'lightPrimary' => \App\Models\SiteSetting::get('appearance_light_primary', ''),
            'lightAccent'  => \App\Models\SiteSetting::get('appearance_light_accent',  ''),
            'lightBase'    => \App\Models\SiteSetting::get('appearance_light_base',    ''),
            'lightCard'    => \App\Models\SiteSetting::get('appearance_light_card',    ''),
            'borderRadius' => \App\Models\SiteSetting::get('appearance_border_radius', ''),
            'fontSize'     => \App\Models\SiteSetting::get('appearance_font_size',     ''),
            'currentFont'  => \App\Models\SiteSetting::get('appearance_font',          ''),
            'customCSS'    => \App\Models\SiteSetting::get('appearance_custom_css',    ''),
        ]);
    }

    public function appearanceSave(Request $request)
    {
        $hex = 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/';
        $validated = $request->validate([
            'appearance_dark_primary'  => $hex,
            'appearance_dark_accent'   => $hex,
            'appearance_dark_base'     => $hex,
            'appearance_dark_card'     => $hex,
            'appearance_light_primary' => $hex,
            'appearance_light_accent'  => $hex,
            'appearance_light_base'    => $hex,
            'appearance_light_card'    => $hex,
            'appearance_border_radius' => 'nullable|string|max:10',
            'appearance_font_size'     => 'nullable|string|max:10',
            'appearance_font'          => 'nullable|string|max:80',
            'appearance_custom_css'    => 'nullable|string|max:8000',
        ]);

        foreach ($validated as $key => $value) {
            \App\Models\SiteSetting::set($key, $value ?? '');
        }

        \Illuminate\Support\Facades\Cache::flush(); // clear all cached settings

        return back()->with('appearance_saved', true)->with('success', 'Appearance saved successfully.');
    }

    public function appearanceReset()
    {
        $keys = [
            'appearance_dark_primary','appearance_dark_accent','appearance_dark_base','appearance_dark_card',
            'appearance_light_primary','appearance_light_accent','appearance_light_base','appearance_light_card',
            'appearance_border_radius','appearance_font_size','appearance_font','appearance_custom_css',
        ];
        foreach ($keys as $k) {
            \App\Models\SiteSetting::set($k, '');
        }
        \Illuminate\Support\Facades\Cache::flush();

        return redirect()->route('admin.appearance')->with('success', 'Appearance reset to defaults.');
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    private function auditLog(string $action, string $targetType, int $targetId, array $data): void
    {
        try {
            DB::table('admin_action_logs')->insert([
                'admin_id'    => Auth::id(),
                'action'      => $action,
                'target_type' => $targetType,
                'target_id'   => $targetId,
                'after'       => json_encode($data),
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin audit log write failed: ' . $e->getMessage());
        }
    }
}
