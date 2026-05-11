<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiProvider extends Model
{
    protected $fillable = [
        // Existing
        'name', 'url', 'api_key', 'status', 'percentage_increase',
        // New
        'profit_margin', 'health_score', 'health_status',
        'last_checked_at', 'api_response_ms',
    ];

    protected $casts = [
        'api_key'             => 'encrypted',
        'percentage_increase' => 'float',
        // New
        'profit_margin'       => 'float',
        'health_score'        => 'integer',
        'api_response_ms'     => 'integer',
        'last_checked_at'     => 'datetime',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function getHealthColorAttribute(): string
    {
        return match($this->health_status ?? 'unknown') {
            'healthy'  => 'success',
            'degraded' => 'warning',
            'unstable' => 'orange',
            'critical' => 'danger',
            default    => 'secondary',
        };
    }
}
