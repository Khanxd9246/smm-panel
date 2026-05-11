<?php

namespace App\Services;

use App\Models\User;
use App\Models\BalanceTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Exceptions\InsufficientFundsException;

/**
 * WalletService — Atomic, audited balance operations.
 *
 * ALL balance mutations MUST go through this service.
 * Never update user.funds directly in controllers.
 */
class WalletService
{
    /**
     * Add funds to a user's balance.
     */
    public function credit(User $user, float $amount, string $reason, ?int $adminId = null): BalanceTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Credit amount must be positive. Got: {$amount}");
        }

        return DB::transaction(function () use ($user, $amount, $reason, $adminId) {
            $user->lockForUpdate()->find($user->id); // Pessimistic lock

            $before = $user->funds;
            $user->increment('funds', $amount);
            $after = $user->fresh()->funds;

            return BalanceTransaction::create([
                'user_id'       => $user->id,
                'admin_id'      => $adminId ?? Auth::id(),
                'type'          => 'credit',
                'amount'        => $amount,
                'balance_before'=> $before,
                'balance_after' => $after,
                'reason'        => $reason,
                'ip_address'    => request()->ip(),
            ]);
        });
    }

    /**
     * Deduct funds from a user's balance.
     * Throws InsufficientFundsException if balance would go negative.
     */
    public function debit(User $user, float $amount, string $reason, ?int $adminId = null): BalanceTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Debit amount must be positive. Got: {$amount}");
        }

        return DB::transaction(function () use ($user, $amount, $reason, $adminId) {
            $user = User::lockForUpdate()->findOrFail($user->id);

            if ($user->funds < $amount) {
                throw new InsufficientFundsException(
                    "Insufficient funds. Balance: {$user->funds}, Required: {$amount}"
                );
            }

            $before = $user->funds;
            $user->decrement('funds', $amount);
            $after = $user->fresh()->funds;

            return BalanceTransaction::create([
                'user_id'       => $user->id,
                'admin_id'      => $adminId ?? Auth::id(),
                'type'          => 'debit',
                'amount'        => $amount,
                'balance_before'=> $before,
                'balance_after' => $after,
                'reason'        => $reason,
                'ip_address'    => request()->ip(),
            ]);
        });
    }

    /**
     * Refund a specific amount to a user.
     */
    public function refund(User $user, float $amount, string $reason, ?int $adminId = null): BalanceTransaction
    {
        $tx = $this->credit($user, $amount, "REFUND: {$reason}", $adminId);
        $tx->update(['type' => 'refund']);
        return $tx;
    }

    /**
     * Freeze a user's account (admin action).
     * Sets balance to 0 and logs the frozen amount.
     */
    public function freeze(User $user, string $reason, int $adminId): BalanceTransaction
    {
        return DB::transaction(function () use ($user, $reason, $adminId) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            $frozenAmount = $user->funds;

            $user->update(['funds' => 0, 'status' => 'banned']);

            return BalanceTransaction::create([
                'user_id'       => $user->id,
                'admin_id'      => $adminId,
                'type'          => 'freeze',
                'amount'        => $frozenAmount,
                'balance_before'=> $frozenAmount,
                'balance_after' => 0,
                'reason'        => $reason,
                'ip_address'    => request()->ip(),
            ]);
        });
    }

    /**
     * Get transaction history for a user.
     */
    public function getHistory(User $user, int $perPage = 20)
    {
        return BalanceTransaction::where('user_id', $user->id)
            ->with('admin:id,name')
            ->latest()
            ->paginate($perPage);
    }
}
