<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FundRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentAccountController extends Controller
{
    public function approve(Request $request, FundRequest $fundRequest)
    {
        if ($fundRequest->status !== 'pending') {
            return back()->withErrors(['error' => 'This request is already processed.']);
        }

        try {
            DB::transaction(function () use ($fundRequest) {
                // 1. Lock and update user balance
                $user = User::lockForUpdate()->findOrFail($fundRequest->user_id);
                $user->increment('funds', $fundRequest->usd_amount);

                // 2. Mark request as approved
                $fundRequest->update([
                    'status' => 'approved',
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                ]);

                // 3. Create a transaction log
                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $fundRequest->usd_amount,
                    'type' => 'deposit',
                    'description' => 'Deposit approved (TID: ' . $fundRequest->transaction_id . ')',
                    'status' => 'completed',
                    'gateway' => 'manual'
                ]);
            });

            return back()->with('success', 'Balance credited to user successfully.');
        } catch (\Exception $e) {
            Log::error('Deposit Approval Failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'System error during approval.']);
        }
    }

    public function reject(Request $request, FundRequest $fundRequest)
    {
        $fundRequest->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);
        return back()->with('success', 'Request rejected.');
    }
}
