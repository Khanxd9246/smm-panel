@extends('layouts.app')

@section('title', 'AI Dashboard')

@push('styles')
<style>
.dash-card { border: none; border-radius: 16px; transition: transform .15s, box-shadow .15s; }
.dash-card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,.12)!important; }
.metric-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
.ai-recommendation { border-left: 4px solid #6366f1; background: rgba(99,102,241,.06); border-radius: 8px; padding: 12px 16px; margin-bottom: 10px; }
.supplier-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
.activity-line { padding: 8px 0; border-bottom: 1px solid rgba(0,0,0,.05); }
.activity-line:last-child { border-bottom: none; }
.chart-container { position: relative; height: 200px; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

  {{-- Header --}}
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="fw-bold mb-0">🤖 AI SMM Dashboard</h2>
      <small class="text-muted">{{ now()->format('l, d F Y · H:i') }} · Auto-refreshes every 5 minutes</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('admin.ai.services.index') }}" class="btn btn-primary btn-sm">🎛 Service Manager</a>
      <a href="{{ route('admin.ai.suppliers.health') }}" class="btn btn-outline-warning btn-sm">⚡ Supplier Health</a>
      <a href="{{ route('admin.ai.quality.low') }}" class="btn btn-outline-danger btn-sm">⚠ Low Quality</a>
    </div>
  </div>

  {{-- Top Metrics Row --}}
  <div class="row g-3 mb-4">
    @php
      $metrics = [
        ['label'=>'Total Orders',        'value'=>number_format($total_orders),       'icon'=>'📦', 'color'=>'primary',  'sub'=>'All time'],
        ['label'=>'Pending Orders',       'value'=>number_format($pending_orders),      'icon'=>'⏳', 'color'=>'warning',  'sub'=>'Need attention'],
        ['label'=>'Completed Today',      'value'=>number_format($completed_orders),    'icon'=>'✅', 'color'=>'success',  'sub'=>'Today'],
        ['label'=>'Active Users',         'value'=>number_format($active_users),        'icon'=>'👥', 'color'=>'info',     'sub'=>'Registered'],
        ['label'=>'Revenue',              'value'=>'$'.number_format($total_revenue,2), 'icon'=>'💰', 'color'=>'success',  'sub'=>'Total deposits'],
        ['label'=>'Open Tickets',         'value'=>number_format($open_tickets),        'icon'=>'🎫', 'color'=>'danger',   'sub'=>'Awaiting reply'],
      ];
    @endphp
    @foreach($metrics as $m)
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card dash-card shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="metric-icon bg-{{ $m['color'] }} bg-opacity-15">{{ $m['icon'] }}</div>
          <div>
            <div class="fs-5 fw-bold text-{{ $m['color'] }}">{{ $m['value'] }}</div>
            <div class="small fw-semibold">{{ $m['label'] }}</div>
            <div class="text-muted" style="font-size:.7rem;">{{ $m['sub'] }}</div>
          </div>
        </div>
      </div>
    </div>
    @endforeach
  </div>

  <div class="row g-3 mb-4">

    {{-- AI Supplier Health Widget --}}
    <div class="col-md-4">
      <div class="card dash-card shadow-sm h-100">
        <div class="card-header fw-bold d-flex justify-content-between align-items-center">
          ⚡ Supplier Health
          <a href="{{ route('admin.ai.suppliers.health') }}" class="btn btn-xs btn-outline-secondary">View All</a>
        </div>
        <div class="card-body">
          @foreach($providers as $p)
          <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
              <span class="supplier-dot bg-{{ $p->health_color }}"></span>
              <span class="small fw-semibold">{{ $p->name }}</span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="small text-muted">{{ $p->health_score ?? '?' }}/10</span>
              <span class="badge bg-{{ $p->health_color }} badge-sm">{{ ucfirst($p->health_status ?? 'unknown') }}</span>
            </div>
          </div>
          @endforeach

          @if($providers->isEmpty())
          <div class="text-muted text-center py-3 small">No providers configured</div>
          @endif
        </div>
      </div>
    </div>

    {{-- AI Recommendations --}}
    <div class="col-md-4">
      <div class="card dash-card shadow-sm h-100">
        <div class="card-header fw-bold">🤖 AI Recommendations</div>
        <div class="card-body">
          @php
            $lowQualityCount = \App\Models\Service::where('quality_score', '<=', 3)->count();
            $noOrderCount    = \App\Models\Service::doesntHave('orders')->count();
            $hiddenCount     = \App\Models\Service::where('is_hidden', true)->count();
            $criticalProviders = \App\Models\ApiProvider::where('health_status', 'critical')->count();
          @endphp

          @if($lowQualityCount > 0)
          <div class="ai-recommendation">
            <div class="small fw-bold text-danger">⚠ {{ $lowQualityCount }} low quality services</div>
            <div class="small text-muted">These services may be damaging user trust.</div>
            <a href="{{ route('admin.ai.quality.low') }}" class="btn btn-xs btn-outline-danger mt-1">Review Now →</a>
          </div>
          @endif

          @if($criticalProviders > 0)
          <div class="ai-recommendation">
            <div class="small fw-bold text-danger">🔴 {{ $criticalProviders }} critical supplier(s)</div>
            <div class="small text-muted">API providers are offline or failing orders.</div>
            <a href="{{ route('admin.ai.suppliers.health') }}" class="btn btn-xs btn-outline-warning mt-1">Check Health →</a>
          </div>
          @endif

          @if($noOrderCount > 0)
          <div class="ai-recommendation">
            <div class="small fw-bold text-warning">📭 {{ $noOrderCount }} services with no orders</div>
            <div class="small text-muted">Consider hiding or promoting these services.</div>
            <a href="{{ route('admin.ai.services.index') }}?filter=no_orders" class="btn btn-xs btn-outline-secondary mt-1">View →</a>
          </div>
          @endif

          @if($lowQualityCount === 0 && $criticalProviders === 0 && $noOrderCount === 0)
          <div class="text-success text-center py-3">
            <div class="fs-2">✅</div>
            <div class="small">Everything looks healthy!</div>
          </div>
          @endif
        </div>
      </div>
    </div>

    {{-- Quick Actions --}}
    <div class="col-md-4">
      <div class="card dash-card shadow-sm h-100">
        <div class="card-header fw-bold">⚡ Quick Actions</div>
        <div class="card-body d-flex flex-column gap-2">
          <button class="btn btn-outline-primary btn-sm text-start" onclick="runScoring()">
            🎯 Score All Services
          </button>
          <button class="btn btn-outline-warning btn-sm text-start" onclick="runHealthCheck()">
            ⚡ Run Supplier Health Check
          </button>
          <a href="{{ route('admin.ai.duplicates.index') }}" class="btn btn-outline-secondary btn-sm text-start">
            🔍 Scan Duplicates
          </a>
          <a href="{{ route('admin.ai.pricing.index') }}" class="btn btn-outline-success btn-sm text-start">
            💰 Configure Pricing
          </a>
          <a href="{{ route('admin.providers.index') }}" class="btn btn-outline-info btn-sm text-start">
            🔌 Manage Providers
          </a>
          <a href="{{ route('admin.users.index') }}" class="btn btn-outline-dark btn-sm text-start">
            👥 Manage Users
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- Recent Orders + Users --}}
  <div class="row g-3">
    <div class="col-md-7">
      <div class="card dash-card shadow-sm">
        <div class="card-header fw-bold d-flex justify-content-between">
          📦 Recent Orders
          <a href="{{ route('admin.orders.index') }}" class="btn btn-xs btn-outline-secondary">All Orders</a>
        </div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>#</th><th>User</th><th>Service</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
              @foreach($recent_orders as $order)
              <tr>
                <td><small>{{ $order->id }}</small></td>
                <td><small>{{ $order->user?->name }}</small></td>
                <td><small style="max-width:150px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $order->service?->name }}</small></td>
                <td><small>${{ number_format($order->total, 2) }}</small></td>
                <td>
                  <span class="badge bg-{{ match($order->status) {
                    'completed' => 'success', 'pending' => 'warning',
                    'cancelled' => 'danger', 'in progress' => 'info', default => 'secondary'
                  } }} badge-sm">{{ $order->status }}</span>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="card dash-card shadow-sm">
        <div class="card-header fw-bold d-flex justify-content-between">
          👥 New Users
          <a href="{{ route('admin.users.index') }}" class="btn btn-xs btn-outline-secondary">All Users</a>
        </div>
        <div class="card-body p-0">
          @foreach($recent_users as $user)
          <div class="activity-line px-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="small fw-semibold">{{ $user->name }}</div>
                <div class="text-muted" style="font-size:.72rem;">{{ $user->email }}</div>
              </div>
              <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
const CSRF = '{{ csrf_token() }}';

async function runScoring() {
  showToast('Running quality scoring...', 'info');
  const data = await apiFetch('{{ route("admin.ai.quality.score-all") }}', 'POST');
  showToast(data.message ?? 'Done', data.success ? 'success' : 'danger');
}

async function runHealthCheck() {
  showToast('Running health checks...', 'info');
  const data = await apiFetch('{{ route("admin.ai.suppliers.health-check") }}', 'POST');
  showToast(data.message ?? 'Done', data.success ? 'success' : 'danger');
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
