<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        // ── Existing fields (unchanged) ───────────────────────────────────
        'name', 'description', 'category_id', 'api_provider_id',
        'api_service_id', 'rate', 'min', 'max', 'status', 'type',
        'tier', 'min_time', 'max_time',
        // ── NEW: pricing ──────────────────────────────────────────────────
        'supplier_rate', 'custom_margin',
        // ── NEW: quality scoring ──────────────────────────────────────────
        'quality_score', 'quality_status', 'quality_issues',
        'success_rate', 'cancel_rate', 'avg_start_time',
        // ── NEW: delivery time display ────────────────────────────────────
        'estimated_start', 'estimated_completion', 'delivery_badge',
        // ── NEW: flags ────────────────────────────────────────────────────
        'has_refill', 'is_premium', 'is_hidden',
        // ── NEW: AI content ───────────────────────────────────────────────
        'ai_tags', 'ai_description',
        // ── NEW: analytics ────────────────────────────────────────────────
        'views_count', 'orders_count', 'last_synced_at',
    ];

    protected $casts = [
        // Existing
        'rate'             => 'float',
        'min'              => 'integer',
        'max'              => 'integer',
        'min_time'         => 'integer',
        'max_time'         => 'integer',
        // New
        'supplier_rate'    => 'float',
        'custom_margin'    => 'float',
        'quality_score'    => 'integer',
        'success_rate'     => 'float',
        'cancel_rate'      => 'float',
        'avg_start_time'   => 'integer',
        'has_refill'       => 'boolean',
        'is_premium'       => 'boolean',
        'is_hidden'        => 'boolean',
        'quality_issues'   => 'array',
        'ai_tags'          => 'array',
        'views_count'      => 'integer',
        'orders_count'     => 'integer',
        'last_synced_at'   => 'datetime',
    ];

    // ── Relations (unchanged) ─────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function apiProvider(): BelongsTo
    {
        return $this->belongsTo(ApiProvider::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // ── Existing scopes (preserved exactly) ──────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        $query->where('status', 'active');

        // Only filter is_hidden if the column exists (guards against running
        // before the ai_upgrade_schema migration has been applied).
        if (Schema::hasColumn('services', 'is_hidden')) {
            $query->where('is_hidden', false);
        }

        return $query;
    }

    public function scopeForPlatform(Builder $query, ?string $platform): Builder
    {
        if (blank($platform)) return $query;
        return $query->whereHas('category', fn (Builder $q) => $q->where('platform', $platform));
    }

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        if (blank($type)) return $query;
        return $query->whereHas('category', fn (Builder $q) => $q->where('type', $type));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) return $query;
        // Extended: multi-word fuzzy matching
        $words = array_filter(explode(' ', $term), fn ($w) => strlen($w) > 2);
        return $query->where(function ($q) use ($term, $words) {
            $q->where('name', 'like', '%' . $term . '%');
            foreach ($words as $word) {
                $q->orWhere('name', 'like', '%' . $word . '%');
            }
        });
    }

    public function scopeSorted(Builder $query, string $sort = 'price'): Builder
    {
        return match ($sort) {
            'name'       => $query->orderBy('name'),
            'quality'    => $query->orderByDesc('quality_score'),
            'speed'      => $query->orderBy('avg_start_time'),
            'popularity' => $query->orderByDesc('orders_count'),
            'price_high' => $query->orderByDesc('rate'),
            default      => $query->orderBy('rate'), // cheapest first
        };
    }

    // ── NEW scopes ────────────────────────────────────────────────────────

    public function scopeByQuality(Builder $query, string $filter): Builder
    {
        return match($filter) {
            'premium'      => $query->where('is_premium', true),
            'refill'       => $query->where('has_refill', true),
            'instant'      => $query->where('delivery_badge', 'instant'),
            'fast'         => $query->whereIn('delivery_badge', ['instant', 'fast']),
            'high_quality' => $query->where('quality_score', '>=', 8),
            'best_seller'  => $query->orderByDesc('orders_count'),
            default        => $query,
        };
    }

    public function scopeLowQuality(Builder $query): Builder
    {
        return $query->where('quality_score', '<=', 3);
    }

    // ── NEW accessors ─────────────────────────────────────────────────────

    public function getDeliveryLabelAttribute(): string
    {
        return match($this->delivery_badge) {
            'instant' => '⚡ Instant',
            'fast'    => '🚀 Fast',
            'slow'    => '🐢 Slow',
            default   => '⏱ Standard',
        };
    }

    public function getQualityColorAttribute(): string
    {
        return match(true) {
            ($this->quality_score ?? 0) >= 8 => 'success',
            ($this->quality_score ?? 0) >= 6 => 'info',
            ($this->quality_score ?? 0) >= 4 => 'warning',
            default                           => 'danger',
        };
    }

    public function getAllTagsAttribute(): array
    {
        $tags = $this->ai_tags ?? [];
        if ($this->is_premium)                   $tags[] = 'Premium';
        if ($this->has_refill)                   $tags[] = 'Refill';
        if ($this->delivery_badge === 'instant') $tags[] = 'Instant';
        if ($this->delivery_badge === 'fast')    $tags[] = 'Fast';
        if (($this->orders_count ?? 0) > 500)   $tags[] = 'Best Seller';
        if (($this->quality_score ?? 0) >= 9)   $tags[] = 'Top Rated';
        return array_unique($tags);
    }
}
