@extends('layouts.app')

@section('title', 'AI Service Management')

@push('styles')
<style>
/* ── AI Admin Services Page ──────────────────────────────────── */
.quality-badge { font-size: .7rem; font-weight: 700; padding: 3px 8px; border-radius: 20px; }
.delivery-badge { font-size: .7rem; padding: 2px 7px; border-radius: 12px; }
.tag-chip { display:inline-block; font-size:.65rem; padding:1px 6px; border-radius:10px; margin:1px; background:#e9ecef; color:#495057; }
.tag-chip.premium { background:#fff3cd; color:#856404; }
.tag-chip.instant { background:#d1ecf1; color:#0c5460; }
.tag-chip.refill  { background:#d4edda; color:#155724; }
.tag-chip.fast    { background:#cce5ff; color:#004085; }
.service-row:hover { background: rgba(99,102,241,.04); }
.search-highlight { background: #fef08a; border-radius: 2px; }
.bulk-bar { position: sticky; top: 0; z-index: 100; background: #1e293b; color: #f8fafc; padding: 10px 20px; border-radius: 8px; margin-bottom: 12px; display: none; }
.bulk-bar.active { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.stat-card { border: none; border-radius: 12px; padding: 18px 20px; }
#search-results-dropdown { position: absolute; z-index: 200; width: 100%; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,.12); max-height: 400px; overflow-y: auto; }
#search-results-dropdown .result-item { padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f3f4f6; }
#search-results-dropdown .result-item:hover { background: #f0f4ff; }
.score-ring { width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: .8rem; color: white; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

  {{-- ── Header ──────────────────────────────────────────────── --}}
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0 fw-bold">🤖 AI Service Manager</h2>
      <small class="text-muted">Smart service management with quality scoring, bulk actions &amp; AI analysis</small>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-primary btn-sm" id="btn-score-all" onclick="scoreAllServices()">
        <span class="spinner-border spinner-border-sm d-none" id="score-spinner"></span>
        🎯 Score All Services
      </button>
      <button class="btn btn-outline-warning btn-sm" onclick="runHealthCheck()">⚡ Health Check</button>
      <a href="{{ route('admin.ai.duplicates.index') }}" class="btn btn-outline-secondary btn-sm">🔍 Duplicates</a>
      <a href="{{ route('admin.ai.pricing.index') }}" class="btn btn-outline-success btn-sm">💰 Pricing</a>
    </div>
  </div>

  {{-- ── Stat Cards ───────────────────────────────────────────── --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
      <div class="stat-card bg-primary bg-opacity-10 text-center">
        <div class="fs-4 fw-bold text-primary">{{ number_format($stats['total']) }}</div>
        <small class="text-muted">Total</small>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="stat-card bg-success bg-opacity-10 text-center">
        <div class="fs-4 fw-bold text-success">{{ number_format($stats['active']) }}</div>
        <small class="text-muted">Active</small>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="stat-card bg-danger bg-opacity-10 text-center">
        <div class="fs-4 fw-bold text-danger">{{ number_format($stats['low_quality']) }}</div>
        <small class="text-muted">Low Quality</small>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="stat-card bg-warning bg-opacity-10 text-center">
        <div class="fs-4 fw-bold text-warning">{{ number_format($stats['hidden']) }}</div>
        <small class="text-muted">Hidden</small>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="stat-card bg-info bg-opacity-10 text-center">
        <div class="fs-4 fw-bold text-info">{{ number_format($stats['premium']) }}</div>
        <small class="text-muted">Premium</small>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="stat-card bg-secondary bg-opacity-10 text-center">
        <div class="fs-4 fw-bold text-secondary">{{ number_format($stats['no_orders']) }}</div>
        <small class="text-muted">No Orders</small>
      </div>
    </div>
  </div>

  {{-- ── Search + Lookup Bar ──────────────────────────────────── --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body p-3">
      <div class="row g-2">
        {{-- Smart Search --}}
        <div class="col-md-5">
          <div class="position-relative">
            <input type="text" id="smart-search" class="form-control form-control-sm"
              placeholder="🔍 Smart search: paste name, keyword, or typo..." autocomplete="off">
            <div id="search-results-dropdown" style="display:none;"></div>
          </div>
        </div>
        {{-- Supplier ID Lookup --}}
        <div class="col-md-3">
          <div class="input-group input-group-sm">
            <input type="number" id="supplier-id-input" class="form-control"
              placeholder="Supplier Service ID...">
            <button class="btn btn-outline-info" onclick="lookupSupplierServiceId()">Lookup</button>
          </div>
        </div>
        {{-- Provider filter for lookup --}}
        <div class="col-md-2">
          <select id="lookup-provider" class="form-select form-select-sm">
            <option value="">Any Provider</option>
            @foreach($providers as $p)
              <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
          </select>
        </div>
        {{-- Table filters --}}
        <div class="col-md-2 text-end">
          <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filter-row">
            ⚙ Filters
          </button>
        </div>
      </div>

      {{-- Filter Row --}}
      <div class="collapse mt-2" id="filter-row">
        <form method="GET" action="{{ route('admin.ai.services.index') }}" class="row g-2">
          <div class="col-md-2">
            <select name="category" class="form-select form-select-sm">
              <option value="">All Categories</option>
              @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <select name="provider" class="form-select form-select-sm">
              <option value="">All Providers</option>
              @foreach($providers as $p)
                <option value="{{ $p->id }}" @selected(request('provider') == $p->id)>{{ $p->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
              <option value="">All Status</option>
              <option value="active" @selected(request('status') === 'active')>Active</option>
              <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="quality" class="form-select form-select-sm">
              <option value="">All Quality</option>
              <option value="excellent" @selected(request('quality') === 'excellent')>Excellent</option>
              <option value="good" @selected(request('quality') === 'good')>Good</option>
              <option value="fair" @selected(request('quality') === 'fair')>Fair</option>
              <option value="poor" @selected(request('quality') === 'poor')>Poor</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="sort" class="form-select form-select-sm">
              <option value="quality" @selected(request('sort') === 'quality')>Highest Quality</option>
              <option value="price" @selected(request('sort') === 'price')>Cheapest First</option>
              <option value="price_high" @selected(request('sort') === 'price_high')>Most Expensive</option>
              <option value="popularity" @selected(request('sort') === 'popularity')>Most Popular</option>
              <option value="speed" @selected(request('sort') === 'speed')>Fastest</option>
            </select>
          </div>
          <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Apply</button>
            <a href="{{ route('admin.ai.services.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- ── Bulk Action Bar ──────────────────────────────────────── --}}
  <div class="bulk-bar" id="bulk-bar">
    <span class="fw-bold" id="bulk-count">0 selected</span>
    <select id="bulk-action" class="form-select form-select-sm" style="width:auto;">
      <option value="">Select Action...</option>
      <option value="enable">✅ Enable</option>
      <option value="disable">🚫 Disable</option>
      <option value="hide">👁‍🗨 Hide</option>
      <option value="unhide">👁 Unhide</option>
      <option value="mark_premium">⭐ Mark Premium</option>
      <option value="remove_premium">Remove Premium</option>
      <option value="remove_low_quality">🗑 Remove Low Quality</option>
      <option value="update_margin">💰 Update Margin %</option>
      <option value="move_category">📂 Move Category</option>
      <option value="delete">❌ Delete</option>
    </select>
    <div id="bulk-margin-input" style="display:none;">
      <input type="number" id="bulk-margin-val" class="form-control form-control-sm" placeholder="Margin %" style="width:120px;">
    </div>
    <div id="bulk-category-input" style="display:none;">
      <select id="bulk-category-val" class="form-select form-select-sm" style="width:180px;">
        @foreach($categories as $cat)
          <option value="{{ $cat->id }}">{{ $cat->name }}</option>
        @endforeach
      </select>
    </div>
    <button class="btn btn-warning btn-sm" onclick="executeBulkAction()">Apply to Selected</button>
    <button class="btn btn-outline-light btn-sm" onclick="clearSelection()">✕ Clear</button>
  </div>

  {{-- ── Services Table ───────────────────────────────────────── --}}
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-dark">
            <tr>
              <th width="40">
                <input type="checkbox" id="select-all" class="form-check-input">
              </th>
              <th>Service</th>
              <th>Category</th>
              <th>Provider</th>
              <th>Price</th>
              <th>Quality</th>
              <th>Delivery</th>
              <th>Tags</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($services as $service)
            <tr class="service-row" data-id="{{ $service->id }}">
              <td>
                <input type="checkbox" class="form-check-input service-checkbox" value="{{ $service->id }}">
              </td>
              <td>
                <div class="fw-semibold" style="max-width:280px;">
                  {{ $service->name }}
                  @if($service->is_premium)
                    <span class="badge bg-warning text-dark ms-1">⭐</span>
                  @endif
                  @if($service->is_hidden)
                    <span class="badge bg-secondary ms-1">Hidden</span>
                  @endif
                </div>
                <small class="text-muted">ID: {{ $service->id }} | API: {{ $service->api_service_id }}</small>
              </td>
              <td><small>{{ $service->category?->name ?? '—' }}</small></td>
              <td>
                <small>{{ $service->apiProvider?->name ?? '—' }}</small>
              </td>
              <td>
                <div class="fw-bold text-success">${{ number_format($service->rate, 4) }}</div>
                @if($service->supplier_rate)
                  <small class="text-muted">Cost: ${{ number_format($service->supplier_rate, 4) }}</small>
                @endif
              </td>
              <td>
                @php $qs = $service->quality_score ?? 5; @endphp
                <div class="d-flex align-items-center gap-1">
                  <div class="score-ring bg-{{ $service->quality_color }}">{{ $qs }}</div>
                  <small class="text-{{ $service->quality_color }}">{{ ucfirst($service->quality_status ?? 'unknown') }}</small>
                </div>
                @if($service->success_rate > 0)
                  <small class="text-muted d-block">{{ $service->success_rate }}% success</small>
                @endif
              </td>
              <td>
                @if($service->delivery_badge)
                  <span class="delivery-badge bg-{{ match($service->delivery_badge) {
                    'instant' => 'success', 'fast' => 'info', 'slow' => 'warning', default => 'secondary'
                  } }} text-white">
                    {{ $service->delivery_label }}
                  </span>
                @endif
                @if($service->estimated_start)
                  <small class="text-muted d-block">Start: {{ $service->estimated_start }}</small>
                @endif
              </td>
              <td style="max-width: 150px;">
                @foreach(array_slice($service->all_tags ?? [], 0, 4) as $tag)
                  <span class="tag-chip {{ strtolower($tag) }}">{{ $tag }}</span>
                @endforeach
              </td>
              <td>
                <span class="badge bg-{{ $service->status === 'active' ? 'success' : 'secondary' }}">
                  {{ ucfirst($service->status) }}
                </span>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <button class="btn btn-xs btn-outline-primary" onclick="analyzeService({{ $service->id }})" title="AI Analysis">🤖</button>
                  <button class="btn btn-xs btn-outline-info" onclick="generateTitle({{ $service->id }}, '{{ addslashes($service->name) }}')" title="AI Title">✏</button>
                  <a href="{{ route('admin.services.edit', $service->id) }}" class="btn btn-xs btn-outline-secondary" title="Edit">⚙</a>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="10" class="text-center py-5 text-muted">
                <div class="fs-1">🤖</div>
                <div>No services found. Adjust your filters.</div>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($services->hasPages())
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-muted">Showing {{ $services->firstItem() }}–{{ $services->lastItem() }} of {{ $services->total() }}</small>
      {{ $services->links() }}
    </div>
    @endif
  </div>

</div>

{{-- ── Modals ───────────────────────────────────────────────── --}}

{{-- Supplier Lookup Modal --}}
<div class="modal fade" id="lookupModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">🔍 Supplier Service Lookup</h5></div>
      <div class="modal-body" id="lookup-modal-body">Loading...</div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <a id="lookup-edit-btn" href="#" class="btn btn-primary btn-sm" style="display:none;">Open Service</a>
      </div>
    </div>
  </div>
</div>

{{-- AI Analysis Modal --}}
<div class="modal fade" id="analysisModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">🤖 AI Service Analysis</h5></div>
      <div class="modal-body" id="analysis-modal-body">Running AI analysis...</div>
      <div class="modal-footer"><button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

{{-- AI Title Modal --}}
<div class="modal fade" id="titleModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">✏ AI Title Generator</h5></div>
      <div class="modal-body" id="title-modal-body">Generating title...</div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary btn-sm" id="apply-title-btn">Apply Title</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = '{{ csrf_token() }}';
let currentServiceId = null;
let newGeneratedTitle = null;

// ── Select All / Bulk ─────────────────────────────────────────
document.getElementById('select-all').addEventListener('change', function() {
  document.querySelectorAll('.service-checkbox').forEach(cb => cb.checked = this.checked);
  updateBulkBar();
});
document.querySelectorAll('.service-checkbox').forEach(cb => {
  cb.addEventListener('change', updateBulkBar);
});
function updateBulkBar() {
  const checked = document.querySelectorAll('.service-checkbox:checked');
  const bar = document.getElementById('bulk-bar');
  document.getElementById('bulk-count').textContent = `${checked.length} selected`;
  bar.classList.toggle('active', checked.length > 0);
}
function clearSelection() {
  document.querySelectorAll('.service-checkbox').forEach(cb => cb.checked = false);
  document.getElementById('select-all').checked = false;
  updateBulkBar();
}
document.getElementById('bulk-action').addEventListener('change', function() {
  document.getElementById('bulk-margin-input').style.display = this.value === 'update_margin' ? 'block' : 'none';
  document.getElementById('bulk-category-input').style.display = this.value === 'move_category' ? 'block' : 'none';
});

// ── Execute Bulk Action ───────────────────────────────────────
async function executeBulkAction() {
  const action = document.getElementById('bulk-action').value;
  if (!action) return showToast('Select an action', 'warning');

  const ids = [...document.querySelectorAll('.service-checkbox:checked')].map(cb => parseInt(cb.value));
  if (!ids.length) return showToast('No services selected', 'warning');

  const dangerous = ['delete', 'remove_low_quality'];
  if (dangerous.includes(action) && !confirm(`Are you sure you want to "${action}" ${ids.length} services?`)) return;

  const payload = { action, service_ids: ids, _token: CSRF };
  if (action === 'update_margin') payload.margin = document.getElementById('bulk-margin-val').value;
  if (action === 'move_category') payload.category_id = document.getElementById('bulk-category-val').value;

  const res = await apiFetch('{{ route("admin.ai.services.bulk") }}', 'POST', payload);
  if (res.success) {
    showToast(res.message, 'success');
    setTimeout(() => location.reload(), 1200);
  }
}

// ── Smart Fuzzy Search ────────────────────────────────────────
let searchTimeout;
document.getElementById('smart-search').addEventListener('input', function() {
  clearTimeout(searchTimeout);
  const q = this.value.trim();
  const dropdown = document.getElementById('search-results-dropdown');
  if (q.length < 2) { dropdown.style.display = 'none'; return; }

  searchTimeout = setTimeout(async () => {
    const data = await apiFetch(`{{ route("admin.ai.services.search") }}?q=${encodeURIComponent(q)}`);
    renderSearchResults(data.results || []);
  }, 300);
});

function renderSearchResults(results) {
  const dropdown = document.getElementById('search-results-dropdown');
  if (!results.length) { dropdown.style.display = 'none'; return; }

  dropdown.innerHTML = results.map(r => `
    <div class="result-item" onclick="window.location='{{ url("admin/services") }}/${r.id}/edit'">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="fw-semibold small">${r.name}</div>
          <small class="text-muted">${r.category ?? ''} · ${r.provider ?? ''}</small>
        </div>
        <div class="text-end">
          <div class="text-success fw-bold small">$${parseFloat(r.rate).toFixed(4)}</div>
          <span class="badge bg-${r.quality_score >= 7 ? 'success' : r.quality_score >= 4 ? 'warning' : 'danger'} badge-sm">${r.quality_score ?? '?'}/10</span>
        </div>
      </div>
    </div>
  `).join('');
  dropdown.style.display = 'block';
}
document.addEventListener('click', e => {
  if (!e.target.closest('#smart-search') && !e.target.closest('#search-results-dropdown')) {
    document.getElementById('search-results-dropdown').style.display = 'none';
  }
});

// ── Supplier ID Lookup ────────────────────────────────────────
async function lookupSupplierServiceId() {
  const supplierId = document.getElementById('supplier-id-input').value;
  const providerId = document.getElementById('lookup-provider').value;
  if (!supplierId) return showToast('Enter a supplier service ID', 'warning');

  new bootstrap.Modal(document.getElementById('lookupModal')).show();
  document.getElementById('lookup-modal-body').innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div><div class="mt-2">Looking up...</div></div>';

  const url = `{{ route("admin.ai.services.lookup") }}?supplier_id=${supplierId}${providerId ? '&provider_id=' + providerId : ''}`;
  const data = await apiFetch(url);

  if (!data.found) {
    document.getElementById('lookup-modal-body').innerHTML = `<div class="alert alert-warning">No service found with Supplier ID: <strong>${supplierId}</strong></div>`;
    document.getElementById('lookup-edit-btn').style.display = 'none';
    return;
  }

  const s = data.service;
  document.getElementById('lookup-modal-body').innerHTML = `
    <div class="row g-3">
      <div class="col-12">
        <h6 class="fw-bold">${s.name}</h6>
        <span class="badge bg-${s.status === 'active' ? 'success' : 'secondary'}">${s.status}</span>
        ${s.delivery_badge ? `<span class="badge bg-info ms-1">${s.delivery_badge}</span>` : ''}
      </div>
      <div class="col-6"><small class="text-muted d-block">User Price</small><strong class="text-success">$${parseFloat(s.rate).toFixed(6)}</strong></div>
      <div class="col-6"><small class="text-muted d-block">Supplier Cost</small><strong>${s.supplier_rate ? '$' + parseFloat(s.supplier_rate).toFixed(6) : '—'}</strong></div>
      <div class="col-6"><small class="text-muted d-block">Quality Score</small><strong>${s.quality_score ?? '?'}/10</strong> <span class="text-muted">(${s.quality_status ?? 'unknown'})</span></div>
      <div class="col-6"><small class="text-muted d-block">Refill</small><strong>${s.has_refill ? '✅ Yes' : '❌ No'}</strong></div>
      <div class="col-6"><small class="text-muted d-block">Est. Start</small>${s.estimated_start ?? '—'}</div>
      <div class="col-6"><small class="text-muted d-block">Est. Completion</small>${s.estimated_completion ?? '—'}</div>
      <div class="col-6"><small class="text-muted d-block">Success Rate</small>${s.success_rate}%</div>
      <div class="col-6"><small class="text-muted d-block">Cancel Rate</small>${s.cancel_rate}%</div>
      <div class="col-6"><small class="text-muted d-block">Provider</small>${s.provider ?? '—'}</div>
      <div class="col-6"><small class="text-muted d-block">Category</small>${s.category ?? '—'}</div>
    </div>
  `;
  document.getElementById('lookup-edit-btn').href = s.edit_url;
  document.getElementById('lookup-edit-btn').style.display = 'inline-block';
}

// ── AI Analysis ───────────────────────────────────────────────
async function analyzeService(id) {
  new bootstrap.Modal(document.getElementById('analysisModal')).show();
  document.getElementById('analysis-modal-body').innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div><p class="mt-2">Running AI analysis...</p></div>';

  const data = await apiFetch(`/admin/ai/services/${id}/analyze`, 'POST');
  const a = data.analysis ?? {};

  const color = a.quality_score >= 8 ? 'success' : a.quality_score >= 5 ? 'warning' : 'danger';
  document.getElementById('analysis-modal-body').innerHTML = `
    <div class="d-flex align-items-center gap-3 mb-3">
      <div class="score-ring bg-${color}" style="width:50px;height:50px;font-size:1.2rem;">${a.quality_score ?? '?'}</div>
      <div><strong>Quality Score: ${a.quality_score ?? '?'}/10</strong><br><small class="text-muted">${a.recommendation ?? ''}</small></div>
    </div>
    ${a.issues?.length ? '<h6 class="text-danger">⚠ Issues</h6><ul class="mb-3">' + a.issues.map(i => `<li>${i}</li>`).join('') + '</ul>' : ''}
    ${a.strengths?.length ? '<h6 class="text-success">✅ Strengths</h6><ul class="mb-3">' + a.strengths.map(s => `<li>${s}</li>`).join('') + '</ul>' : ''}
    ${a.suggested_tags?.length ? '<h6>🏷 Suggested Tags</h6>' + a.suggested_tags.map(t => `<span class="tag-chip">${t}</span>`).join('') : ''}
  `;
}

// ── AI Title Generator ────────────────────────────────────────
async function generateTitle(id, currentName) {
  currentServiceId = id;
  new bootstrap.Modal(document.getElementById('titleModal')).show();
  document.getElementById('title-modal-body').innerHTML = '<div class="text-center p-4"><div class="spinner-border"></div></div>';

  const data = await apiFetch(`/admin/ai/services/${id}/generate-title`, 'POST');
  newGeneratedTitle = data.new_title;

  document.getElementById('title-modal-body').innerHTML = `
    <div class="mb-3"><small class="text-muted">Original</small><div class="p-2 bg-light rounded">${data.old_title}</div></div>
    <div><small class="text-success fw-bold">AI Suggestion</small>
    <div class="p-2 bg-success bg-opacity-10 rounded fw-semibold">${data.new_title}</div></div>
  `;
}
document.getElementById('apply-title-btn').addEventListener('click', async () => {
  if (!currentServiceId || !newGeneratedTitle) return;
  const data = await apiFetch(`/admin/ai/services/${currentServiceId}/apply-title`, 'POST', { title: newGeneratedTitle });
  if (data.success) { showToast('Title updated!', 'success'); bootstrap.Modal.getInstance(document.getElementById('titleModal')).hide(); setTimeout(() => location.reload(), 800); }
});

// ── Score All Services ────────────────────────────────────────
async function scoreAllServices() {
  if (!confirm('Run quality scoring for all services? This may take a moment.')) return;
  document.getElementById('score-spinner').classList.remove('d-none');
  const data = await apiFetch('{{ route("admin.ai.quality.score-all") }}', 'POST');
  document.getElementById('score-spinner').classList.add('d-none');
  showToast(data.message, data.success ? 'success' : 'danger');
}

// ── Health Check ──────────────────────────────────────────────
async function runHealthCheck() {
  showToast('Running supplier health checks...', 'info');
  const data = await apiFetch('{{ route("admin.ai.suppliers.health-check") }}', 'POST');
  showToast(data.message, data.success ? 'success' : 'danger');
}

// ── Helpers ───────────────────────────────────────────────────
async function apiFetch(url, method = 'GET', body = null) {
  const opts = { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } };
  if (body) opts.body = JSON.stringify(body);
  try {
    const r = await fetch(url, opts);
    return await r.json();
  } catch(e) {
    showToast('Request failed: ' + e.message, 'danger');
    return {};
  }
}
function showToast(msg, type = 'success') {
  const t = document.createElement('div');
  t.className = `alert alert-${type} position-fixed bottom-0 end-0 m-3 shadow`;
  t.style.zIndex = 9999;
  t.innerHTML = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 4000);
}
</script>
@endpush
