<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FundAccount extends Model
{
    protected $fillable = [
        'name',
        'iban',
        'account_number',
        'notes',
        'status',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
