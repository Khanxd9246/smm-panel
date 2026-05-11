@extends('layouts.app')

@section('title', 'Auto Pricing Engine')

@section('content')
<div class="container-fluid py-4">

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="fw-bold mb-0">💰 Auto Pricing Engine</h2>
      <small class="text-muted">Configure profit margins at global, category, and provider levels</small>
    </div>
    <a href="{{ route('admin.ai.services.index') }}" class="btn btn-outline-secondary btn-sm">← Services</a>
  </div>

  {{-- Global Margin --}}
  <div class="card shadow-sm mb-4">
    <div class="card-header fw-bold">🌍 Global Profit Margin</div>
    <div class="card-body">
      <p class="text-muted small mb-3">
        Applied to all services that don't have a category or service-level override.<br>
        Formula: <code>final_price = supplier_rate × (1 + margin/100)</code>
      </p>
      <div class="row align-items-end g-3">
        <div class="col-md-3">
          <label class="form-label fw-semibold">Global Margin %</label>
          <div class="input-group">
            <input type="number" id="global-margin" class="form-control"
              value="{{ $global }}" min="0" max="500" step="0.01">
            <span class="input-group-text">%</span>
          </div>
          <small class="text-muted">Current: <strong>{{ $global }}%</strong></small>
        </div>
        <div class="col-md-3">
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="recalculate-toggle" checked>
            <label class="form-check-label" for="recalculate-toggle">Recalculate all services now</label>
          </div>
          <button class="btn btn-primary" onclick="updateGlobalMargin()">💾 Save &amp; Apply</button>
        </div>
        <div class="col-md-6">
          <div class="alert alert-info mb-0 py-2">
            <strong>Example:</strong> Supplier cost $0.20 + {{ $global }}% margin =
            <strong>${{ number_format(0.20 * (1 + $global/100), 4) }}</strong>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Category Margins --}}
  <div class="card shadow-sm mb-4">
    <div class="card-header fw-bold">📂 Category Margins</div>
    <div class="card-body p-0">
      <table class="table mb-0">
        <thead class="table-light">
          <tr><th>Category</th><th>Services</th><th>Margin %</th><th></th></tr>
        </thead>
        <tbody>
          @foreach($categories as $cat)
          <tr>
            <td>{{ $cat->name }}</td>
            <td><span class="badge bg-secondary">{{ $cat->services_count }}</span></td>
            <td style="width:200px;">
              <div class="input-group input-group-sm">
                <input type="number" class="form-control category-margin"
                  data-id="{{ $cat->id }}" value="{{ $cat->profit_margin ?? '' }}"
                  placeholder="Global ({{ $global }}%)" min="0" max="500" step="0.01">
                <span class="input-group-text">%</span>
              </div>
            </td>
            <td>
              <button class="btn btn-xs btn-outline-primary" onclick="saveCategoryMargin({{ $cat->id }})">Save</button>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- Provider Margins --}}
  <div class="card shadow-sm">
    <div class="card-header fw-bold">🔌 Provider Margins</div>
    <div class="card-body p-0">
      <table class="table mb-0">
        <thead class="table-light">
          <tr><th>Provider</th><th>Services</th><th>Health</th><th>Margin %</th><th></th></tr>
        </thead>
        <tbody>
          @foreach($providers as $provider)
          <tr>
            <td>{{ $provider->name }}</td>
            <td><span class="badge bg-secondary">{{ $provider->services_count }}</span></td>
            <td>
              <span class="badge bg-{{ $provider->health_color }}">
                {{ ucfirst($provider->health_status ?? 'unknown') }}
              </span>
            </td>
            <td style="width:200px;">
              <div class="input-group input-group-sm">
                <input type="number" class="form-control provider-margin"
                  data-id="{{ $provider->id }}" value="{{ $provider->profit_margin ?? '' }}"
                  placeholder="Global ({{ $global }}%)" min="0" max="500" step="0.01">
                <span class="input-group-text">%</span>
              </div>
            </td>
            <td>
              <button class="btn btn-xs btn-outline-primary" onclick="saveProviderMargin({{ $provider->id }})">Save</button>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
const CSRF = '{{ csrf_token() }}';

async function updateGlobalMargin() {
  const margin = parseFloat(document.getElementById('global-margin').value);
  const recalculate = document.getElementById('recalculate-toggle').checked;
  if (isNaN(margin) || margin < 0) return showToast('Enter a valid margin', 'warning');

  const data = await apiFetch('{{ route("admin.ai.pricing.global") }}', 'POST', { margin, recalculate });
  showToast(data.message ?? 'Saved!', data.success ? 'success' : 'danger');
}

async function saveCategoryMargin(id) {
  const input = document.querySelector(`.category-margin[data-id="${id}"]`);
  const margin = input.value ? parseFloat(input.value) : null;

  await fetch(`/admin/categories/${id}`, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify({ profit_margin: margin }),
  });
  showToast('Category margin saved', 'success');
}

async function saveProviderMargin(id) {
  const input = document.querySelector(`.provider-margin[data-id="${id}"]`);
  const margin = input.value ? parseFloat(input.value) : null;

  await fetch(`/admin/providers/${id}`, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify({ profit_margin: margin }),
  });
  showToast('Provider margin saved', 'success');
}

async function apiFetch(url, method = 'GET', body = null) {
  const opts = { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } };
  if (body) opts.body = JSON.stringify(body);
  try { return await (await fetch(url, opts)).json(); } catch { return {}; }
}

function showToast(msg, type = 'success') {
  const t = document.createElement('div');
  t.className = `alert alert-${type} position-fixed bottom-0 end-0 m-3 shadow`;
  t.style.zIndex = 9999;
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 4000);
}
</script>
@endpush
