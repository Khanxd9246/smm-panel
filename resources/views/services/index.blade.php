@extends('layouts.app')

@section('title', 'Services')

@push('styles')
<style>
.service-card { border: none; border-radius: 14px; transition: all .2s; }
.service-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,.12)!important; }
.delivery-badge { font-size:.68rem; font-weight:700; padding:2px 8px; border-radius:12px; }
.tag-chip { display:inline-block; font-size:.64rem; padding:1px 7px; border-radius:10px; margin:1px; background:#f1f5f9; color:#475569; }
.quality-bar { height: 5px; border-radius: 3px; background: #e2e8f0; overflow: hidden; }
.quality-fill { height: 100%; border-radius: 3px; }
.filter-pill { border-radius: 20px; font-size:.8rem; padding:4px 14px; }
.filter-pill.active { background:#6366f1; color:white; border-color:#6366f1; }
</style>
@endpush

@section('content')
<div class="container py-4">

  <div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="fw-bold mb-0">📦 Our Services</h2>
    <small class="text-muted">{{ $services->total() }} services available</small>
  </div>

  {{-- ── Filters ──────────────────────────────────────────────── --}}
  <div class="card shadow-sm mb-4 border-0">
    <div class="card-body p-3">
      {{-- Quick filter pills --}}
      <div class="d-flex flex-wrap gap-2 mb-3">
        @php
          $filters = [
            ''          => '🌍 All',
            'instant'   => '⚡ Instant',
            'fast'      => '🚀 Fast',
            'refill'    => '🔄 Refill',
            'premium'   => '⭐ Premium',
            'high_quality' => '🏆 High Quality',
            'best_seller'  => '🔥 Best Seller',
          ];
        @endphp
        @foreach($filters as $val => $label)
          <a href="{{ request()->fullUrlWithQuery(['filter' => $val, 'page' => null]) }}"
            class="btn btn-outline-secondary filter-pill {{ request('filter', '') === $val ? 'active' : '' }}">
            {{ $label }}
          </a>
        @endforeach
      </div>

      {{-- Search + Sort Row --}}
      <form method="GET" class="row g-2">
        @if(request('filter')) <input type="hidden" name="filter" value="{{ request('filter') }}"> @endif
        <div class="col-md-4">
          <input type="text" name="search" class="form-control form-control-sm"
            placeholder="🔍 Search services..." value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
          <select name="category" class="form-select form-select-sm">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select name="sort" class="form-select form-select-sm">
            <option value="price" @selected(request('sort') === 'price')>💰 Cheapest First</option>
            <option value="quality" @selected(request('sort') === 'quality')>🏆 Highest Quality</option>
            <option value="speed" @selected(request('sort') === 'speed')>⚡ Fastest</option>
            <option value="popularity" @selected(request('sort') === 'popularity')>🔥 Popular</option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
        </div>
        @if(request()->hasAny(['search','category','sort','filter']))
        <div class="col-md-2">
          <a href="{{ route('services.index') }}" class="btn btn-outline-secondary btn-sm w-100">Clear</a>
        </div>
        @endif
      </form>
    </div>
  </div>

  {{-- ── Service Cards ─────────────────────────────────────────── --}}
  @if($services->isEmpty())
    <div class="text-center py-5 text-muted">
      <div class="fs-1">🔍</div>
      <h5>No services found</h5>
      <a href="{{ route('services.index') }}" class="btn btn-outline-primary btn-sm mt-2">Clear Filters</a>
    </div>
  @else
  <div class="row g-3">
    @foreach($services as $service)
    <div class="col-md-6 col-xl-4">
      <div class="card service-card shadow-sm h-100">
        <div class="card-body d-flex flex-column">

          {{-- Header --}}
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="flex-grow-1 pe-2">
              <h6 class="mb-1 fw-bold" style="line-height:1.3;">
                {{ $service->name }}
                @if($service->is_premium)
                  <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem;">⭐ Premium</span>
                @endif
              </h6>
              <small class="text-muted">{{ $service->category?->name }}</small>
            </div>
            <div class="text-end flex-shrink-0">
              <div class="fs-5 fw-bold text-success">${{ number_format($service->rate, 4) }}</div>
              <small class="text-muted">per 1000</small>
            </div>
          </div>

          {{-- Delivery + Quality Badges --}}
          <div class="d-flex align-items-center gap-2 mb-3">
            @if($service->delivery_badge)
              <span class="delivery-badge bg-{{ match($service->delivery_badge) {
                'instant' => 'success', 'fast' => 'info', 'slow' => 'warning', default => 'secondary'
              } }} text-white">
                {{ $service->delivery_label }}
              </span>
            @endif
            @if($service->has_refill)
              <span class="delivery-badge bg-primary text-white">🔄 Refill</span>
            @endif
            @if(($service->quality_score ?? 0) >= 8)
              <span class="delivery-badge bg-warning text-dark">🏆 Top Quality</span>
            @endif
          </div>

          {{-- Delivery Times --}}
          @if($service->estimated_start || $service->estimated_completion)
          <div class="bg-light rounded p-2 mb-3 small">
            @if($service->estimated_start)
              <div class="d-flex justify-content-between">
                <span class="text-muted">⏱ Start time:</span>
                <strong>{{ $service->estimated_start }}</strong>
              </div>
            @endif
            @if($service->estimated_completion)
              <div class="d-flex justify-content-between mt-1">
                <span class="text-muted">✅ Completion:</span>
                <strong>{{ $service->estimated_completion }}</strong>
              </div>
            @endif
          </div>
          @endif

          {{-- Quality Bar --}}
          @if($service->quality_score)
          <div class="mb-2">
            <div class="d-flex justify-content-between mb-1">
              <small class="text-muted">Quality</small>
              <small class="fw-bold text-{{ $service->quality_color }}">{{ $service->quality_score }}/10</small>
            </div>
            <div class="quality-bar">
              <div class="quality-fill bg-{{ $service->quality_color }}"
                style="width: {{ $service->quality_score * 10 }}%"></div>
            </div>
          </div>
          @endif

          {{-- Stats Row --}}
          @if($service->success_rate > 0)
          <div class="d-flex gap-3 mb-3 small">
            <div><span class="text-success fw-bold">{{ $service->success_rate }}%</span> <span class="text-muted">success</span></div>
            @if($service->orders_count > 0)
              <div><span class="fw-bold">{{ number_format($service->orders_count) }}</span> <span class="text-muted">orders</span></div>
            @endif
          </div>
          @endif

          {{-- Tags --}}
          @if($service->all_tags)
          <div class="mb-3">
            @foreach(array_slice($service->all_tags, 0, 5) as $tag)
              <span class="tag-chip">{{ $tag }}</span>
            @endforeach
          </div>
          @endif

          {{-- Min/Max + CTA --}}
          <div class="mt-auto">
            <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
              <span>Min: {{ number_format($service->min) }}</span>
              <span>Max: {{ number_format($service->max) }}</span>
            </div>
            <a href="{{ route('orders.create', ['service_id' => $service->id]) }}"
              class="btn btn-primary btn-sm w-100">
              Order Now →
            </a>
          </div>

        </div>
      </div>
    </div>
    @endforeach
  </div>

  {{-- Pagination --}}
  @if($services->hasPages())
  <div class="d-flex justify-content-center mt-4">
    {{ $services->links() }}
  </div>
  @endif
  @endif

</div>
@endsection
