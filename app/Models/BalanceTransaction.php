<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceTransaction extends Model
{
    protected $fillable = [
        'user_id', 'admin_id', 'type', 'amount',
        'balance_before', 'balance_after', 'reason', 'ip_address',
    ];

    protected $casts = [
        'amount'         => 'float',
        'balance_before' => 'float',
        'balance_after'  => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function getTypeBadgeAttribute(): string
    {
        return match($this->type) {
            'credit' => 'success',
            'debit'  => 'danger',
            'refund' => 'info',
            'freeze' => 'warning',
            default  => 'secondary',
        };
    }
}
