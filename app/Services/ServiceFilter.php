<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * ServiceFilter
 * ─────────────────────────────────────────────────────────────────────────────
 * Centralises all service-listing query logic so both ServiceController and
 * future API endpoints share identical behaviour without duplicate code.
 *
 * Supported query params:
 *   platform   – e.g. instagram | tiktok | youtube
 *   type       – e.g. followers | likes | views
 *   search / q – name search string
 *   sort       – price (default) | price_high | quality | speed | popularity | name
 *   filter     – instant | fast | refill | premium | high_quality | best_seller
 *   category   – category id
 *   max_rate   – maximum rate filter (e.g. 0.5, 1, 2)
 *   page       – pagination (handled by Laravel)
 * ─────────────────────────────────────────────────────────────────────────────
 */
class ServiceFilter
{
    private const PER_PAGE    = 24;
    private const SORT_VALUES = ['price', 'price_high', 'name', 'quality', 'speed', 'popularity'];

    public function paginate(Request $request): LengthAwarePaginator
    {
        $platform = $request->input('platform');
        $type     = $request->input('type');
        $search   = $request->input('search') ?? $request->input('q');
        $filter   = $request->input('filter');
        $category = $request->input('category');
        $maxRate  = $request->input('max_rate');

        $sort = in_array($request->input('sort'), self::SORT_VALUES, true)
                ? $request->input('sort')
                : 'price';

        $query = Service::with('category')
            ->active()
            ->forPlatform($platform)
            ->ofType($type)
            ->search($search)
            ->sorted($sort);

        // Category filter
        if ($category) {
            $query->where('category_id', $category);
        }

        // Quality/badge filter
        if ($filter) {
            $query->byQuality($filter);
        }

        // Max rate filter
        if ($maxRate && is_numeric($maxRate)) {
            $query->where('rate', '<=', (float) $maxRate);
        }

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }
}
