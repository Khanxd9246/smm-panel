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
        // ── Phase 3: Admin service controls ──────────────────────────────
        'admin_visible', 'admin_price', 'admin_name', 'admin_description',
        'delivery_time_label', 'delivery_speed',
        'estimated_start_min', 'estimated_complete_min', 'sort_order',
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
        // Phase 3
        'admin_visible'           => 'boolean',
        'admin_price'             => 'float',
        'estimated_start_min'     => 'integer',
        'estimated_complete_min'  => 'integer',
        'sort_order'              => 'integer',
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

    // ── Phase 3: admin-visible scope (used in all user-facing queries) ────

    public function scopeAdminVisible(Builder $query): Builder
    {
        if (Schema::hasColumn('services', 'admin_visible')) {
            $query->where('admin_visible', true);
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

        $term = trim($term);

        // Numeric-only: exact match on our DB id OR provider api_service_id
        if (ctype_digit($term)) {
            return $query->where(function ($q) use ($term) {
                $q->where('id', (int) $term)
                  ->orWhere('api_service_id', $term);
            });
        }

        // Text search: name + api_service_id partial
        $words = array_filter(explode(' ', $term), fn ($w) => strlen($w) > 2);
        return $query->where(function ($q) use ($term, $words) {
            $q->where('name', 'like', '%' . $term . '%')
              ->orWhere('api_service_id', 'like', '%' . $term . '%');
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

    // ── Accessors ─────────────────────────────────────────────────────────

    /** Price shown to users — admin override wins */
    public function getDisplayPriceAttribute(): float
    {
        return ($this->admin_price > 0) ? (float) $this->admin_price : (float) $this->rate;
    }

    /** Name shown to users — admin override wins */
    public function getDisplayNameAttribute(): string
    {
        return ($this->admin_name && trim($this->admin_name) !== '')
            ? $this->admin_name
            : $this->name;
    }

    /** Description shown to users — admin override wins */
    public function getDisplayDescriptionAttribute(): ?string
    {
        return ($this->admin_description && trim($this->admin_description) !== '')
            ? $this->admin_description
            : $this->description;
    }

    /** Human-readable delivery label for badge */
    public function getDeliveryLabelAttribute(): string
    {
        if ($this->delivery_time_label && trim($this->delivery_time_label) !== '') {
            return $this->delivery_time_label;
        }
        return match($this->delivery_speed ?? $this->delivery_badge ?? 'standard') {
            'instant' => '⚡ Instant',
            'fast'    => '🚀 Fast (1–6 hrs)',
            'slow'    => '🐢 Slow (7+ days)',
            default   => '⏱ Standard (1–3 days)',
        };
    }

    /** CSS chip class for delivery speed */
    public function getDeliveryColorAttribute(): string
    {
        return match($this->delivery_speed ?? $this->delivery_badge ?? 'standard') {
            'instant' => 'chip-blue',
            'fast'    => 'chip-green',
            'slow'    => 'chip-yellow',
            default   => 'chip-gray',
        };
    }

    /** Estimated start text */
    public function getEstimatedStartLabelAttribute(): string
    {
        $min = $this->estimated_start_min ?? $this->avg_start_time ?? null;
        if (!$min) return 'Usually within a few minutes';
        if ($min < 60) return "Starts in ~{$min} min";
        $h = round($min / 60, 1);
        return "Starts in ~{$h} hr";
    }

    /** Estimated completion text */
    public function getEstimatedCompleteLabelAttribute(): string
    {
        $min = $this->estimated_complete_min ?? null;
        if (!$min) {
            if ($this->max_time) {
                return $this->max_time < 24
                    ? "Up to {$this->max_time} hr"
                    : 'Up to ' . round($this->max_time / 24) . ' days';
            }
            return 'Varies';
        }
        if ($min < 60)   return "~{$min} min";
        if ($min < 1440) return '~' . round($min / 60) . ' hr';
        return '~' . round($min / 1440) . ' days';
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
        if ($this->is_premium)  $tags[] = 'Premium';
        if ($this->has_refill)  $tags[] = 'Refill';
        $speed = $this->delivery_speed ?? $this->delivery_badge ?? '';
        if ($speed === 'instant') $tags[] = 'Instant';
        if ($speed === 'fast')    $tags[] = 'Fast';
        if (($this->orders_count ?? 0) > 500) $tags[] = 'Best Seller';
        if (($this->quality_score ?? 0) >= 9) $tags[] = 'Top Rated';
        return array_unique($tags);
    }
}
