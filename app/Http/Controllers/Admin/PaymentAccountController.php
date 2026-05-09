<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FundRequest;
use App\Models\PaymentAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    // ── Payment Accounts CRUD ─────────────────────────────────────────────────

    public function index()
    {
        $accounts = PaymentAccount::withCount('fundRequests')->latest()->get();
        return view('admin.payment-accounts.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:100',
            'type'           => 'required|in:easypaisa,jazzcash,bank,crypto',
            'account_number' => 'required|string|max:255',
            'account_title'  => 'nullable|string|max:100',
            'bank_name'      => 'nullable|string|max:100',
        ]);

        PaymentAccount::create($validated + ['is_active' => true]);
        return back()->with('success', 'Payment account added successfully.');
    }

    public function toggle(PaymentAccount $account)
    {
        $account->update(['is_active' => !$account->is_active]);
        return back()->with('success', 'Account status updated.');
    }

    public function destroy(PaymentAccount $account)
    {
        $account->delete();
        return back()->with('success', 'Account deleted.');
    }

    // ── Fund Requests ─────────────────────────────────────────────────────────

    public function fundRequests(Request $request)
    {
        $query = FundRequest::with(['user:id,name,email', 'paymentAccount']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $requests = $query->latest()->paginate(30)->withQueryString();
        return view('admin.payment-accounts.fund-requests', compact('requests'));
    }

    public function approve(Request $request, FundRequest $fundRequest)
    {
        if ($fundRequest->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending requests can be approved.']);
        }

        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($fundRequest, $validated) {
                $user = User::lockForUpdate()->findOrFail($fundRequest->user_id);

                $fundRequest->update([
                    'status'      => 'approved',
                    'admin_note'  => $validated['admin_note'] ?? 'Verified',
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                ]);

                $user->increment('funds', $fundRequest->usd_amount);

                Transaction::create([
                    'user_id'     => $user->id,
                    'amount'      => $fundRequest->usd_amount,
                    'type'        => 'deposit',
                    'description' => 'Manual deposit approved — TXN: ' . $fundRequest->transaction_id,
                    'status'      => 'completed',
                    'reference'   => 'fund_req_' . $fundRequest->id,
                    'gateway'     => $fundRequest->paymentAccount->type,
                ]);
            });

            return back()->with('success', 'Approved — $' . number_format($fundRequest->usd_amount, 2) . ' credited to user.');

        } catch (\Throwable $e) {
            Log::error('Fund request approval failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Approval failed. Please try again.']);
        }
    }

    public function reject(Request $request, FundRequest $fundRequest)
    {
        if ($fundRequest->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending requests can be rejected.']);
        }

        $validated = $request->validate([
            'admin_note' => 'required|string|min:5|max:255',
        ]);

        $fundRequest->update([
            'status'      => 'rejected',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Request rejected.');
    }
}
