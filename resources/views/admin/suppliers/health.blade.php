@extends('layouts.app')

@section('title', 'Supplier Health Monitor')

@push('styles')
<style>
.health-card { border-radius: 14px; border: none; transition: transform .15s; }
.health-card:hover { transform: translateY(-2px); }
.health-ring { width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 800; color: white; flex-shrink: 0; }
.status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.stat-pill { background: rgba(0,0,0,.06); border-radius: 20px; padding: 4px 12px; font-size: .78rem; }
.provider-issues li { font-size: .8rem; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

  {{-- Header --}}
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="fw-bold mb-0">⚡ Supplier Health Monitor</h2>
      <small class="text-muted">Real-time API provider uptime, reliability &amp; order performance</small>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary btn-sm" onclick="runHealthCheck()" id="hc-btn">
        <span class="spinner-border spinner-border-sm d-none" id="hc-spinner"></span>
        🔄 Run Health Check
      </button>
      <a href="{{ route('admin.ai.services.index') }}" class="btn btn-outline-secondary btn-sm">← Services</a>
    </div>
  </div>

  {{-- Summary Widgets --}}
  <div class="row g-3 mb-4">
    @foreach([
      ['label'=>'Healthy','key'=>'healthy','color'=>'success','icon'=>'✅'],
      ['label'=>'Degraded','key'=>'degraded','color'=>'warning','icon'=>'⚠️'],
      ['label'=>'Unstable','key'=>'unstable','color'=>'orange','icon'=>'🔥'],
      ['label'=>'Critical','key'=>'critical','color'=>'danger','icon'=>'❌'],
      ['label'=>'Unknown','key'=>'unknown','color'=>'secondary','icon'=>'❓'],
    ] as $s)
    <div class="col-6 col-md">
      <div class="card health-card shadow-sm text-center p-3">
        <div class="fs-2">{{ $s['icon'] }}</div>
        <div class="fs-3 fw-bold text-{{ $s['color'] }}">{{ $healthSummary[$s['key']] }}</div>
        <small class="text-muted">{{ $s['label'] }}</small>
      </div>
    </div>
    @endforeach
  </div>

  {{-- Provider Cards --}}
  <div class="row g-3" id="provider-grid">
    @forelse($providers as $provider)
    @php
      $color = match($provider->health_status ?? 'unknown') {
        'healthy'  => 'success',
        'degraded' => 'warning',
        'unstable' => 'orange',
        'critical' => 'danger',
        default    => 'secondary',
      };
      $score = $provider->health_score ?? 5;
    @endphp
    <div class="col-md-6 col-xl-4">
      <div class="card health-card shadow-sm h-100" id="provider-card-{{ $provider->id }}">
        <div class="card-body">
          <div class="d-flex align-items-start gap-3 mb-3">
            <div class="health-ring bg-{{ $color }}" style="{{ $color === 'orange' ? 'background:#fd7e14!important;' : '' }}">
              {{ $score }}
            </div>
            <div class="flex-grow-1">
              <div class="fw-bold">{{ $provider->name }}</div>
              <div class="d-flex align-items-center gap-2 mt-1">
                <span class="status-dot bg-{{ $color }}" style="{{ $color === 'orange' ? 'background:#fd7e14!important;' : '' }}"></span>
                <small class="text-{{ $color }}">{{ ucfirst($provider->health_status ?? 'Unknown') }}</small>
                @if($provider->api_response_ms)
                  <small class="text-muted">· {{ $provider->api_response_ms }}ms</small>
                @endif
              </div>
            </div>
            <div class="text-end">
              <div class="badge bg-secondary">{{ $provider->services_count }} services</div>
              @if($provider->status !== 'active')
                <div class="badge bg-danger mt-1">Disabled</div>
              @endif
            </div>
          </div>

          {{-- Quick stats --}}
          <div class="d-flex flex-wrap gap-2 mb-3">
            @php $stats = \Illuminate\Support\Facades\Cache::get("provider_stats_{$provider->id}") ?? []; @endphp
            @if(!empty($stats))
              <span class="stat-pill">✅ {{ $stats['success_rate'] ?? '?' }}% success</span>
              <span class="stat-pill">❌ {{ $stats['cancel_rate'] ?? '?' }}% cancel</span>
              <span class="stat-pill">⏱ {{ $stats['delayed_orders'] ?? '0' }} delayed</span>
            @else
              <span class="stat-pill text-muted">No stats yet — run health check</span>
            @endif
          </div>

          {{-- Last checked --}}
          @if($provider->last_checked_at)
          <small class="text-muted">Last checked: {{ $provider->last_checked_at->diffForHumans() }}</small>
          @else
          <small class="text-muted">Never checked</small>
          @endif

          <div class="d-flex gap-2 mt-3">
            <button class="btn btn-outline-primary btn-sm flex-grow-1"
              onclick="checkSingleProvider({{ $provider->id }}, '{{ addslashes($provider->name) }}')">
              🔍 Check Now
            </button>
            @if($provider->status !== 'active')
              <form method="POST" action="{{ route('admin.providers.update', $provider->id) }}" class="d-inline">
                @csrf @method('PUT')
                <input type="hidden" name="status" value="active">
                <button class="btn btn-outline-success btn-sm">Enable</button>
              </form>
            @else
              <form method="POST" action="{{ route('admin.providers.update', $provider->id) }}" class="d-inline">
                @csrf @method('PUT')
                <input type="hidden" name="status" value="inactive">
                <button class="btn btn-outline-danger btn-sm">Disable</button>
              </form>
            @endif
          </div>
        </div>
      </div>
    </div>
    @empty
    <div class="col-12 text-center py-5 text-muted">
      <div class="fs-1">⚡</div>
      No providers configured.
      <a href="{{ route('admin.providers.create') }}">Add your first provider</a>
    </div>
    @endforelse
  </div>

</div>
@endsection

@push('scripts')
<script>
const CSRF = '{{ csrf_token() }}';

async function runHealthCheck() {
  const btn = document.getElementById('hc-btn');
  const spinner = document.getElementById('hc-spinner');
  btn.disabled = true;
  spinner.classList.remove('d-none');

  const data = await apiFetch('{{ route("admin.ai.suppliers.health-check") }}', 'POST');
  btn.disabled = false;
  spinner.classList.add('d-none');

  showToast(data.message ?? 'Health check complete', data.success ? 'success' : 'danger');
  if (data.success) setTimeout(() => location.reload(), 1500);
}

async function checkSingleProvider(id, name) {
  showToast(`Checking ${name}...`, 'info');
  // Hit full health check endpoint — server checks all; reload reflects result
  const data = await apiFetch('{{ route("admin.ai.suppliers.health-check") }}', 'POST');
  showToast(`${name} checked!`, 'success');
  setTimeout(() => location.reload(), 1000);
}

async function apiFetch(url, method = 'GET', body = null) {
  const opts = { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } };
  if (body) opts.body = JSON.stringify(body);
  try { return await (await fetch(url, opts)).json(); }
  catch(e) { return {}; }
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
