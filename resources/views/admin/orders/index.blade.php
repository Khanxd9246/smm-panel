@extends('layouts.app')
@section('title','All Orders')
@section('page-title','All Orders')
@section('css')
<style>
.tr-row{border-bottom:1px solid rgba(255,255,255,.04);transition:background .12s}
.tr-row:hover{background:rgba(255,255,255,.025)}
.tr-row:last-child{border-bottom:none}
.filter-pill{padding:5px 12px;border-radius:20px;border:1px solid var(--c-border);font-size:11px;font-weight:700;color:var(--c-muted);background:transparent;cursor:pointer;transition:all .15s;text-decoration:none;white-space:nowrap}
.filter-pill:hover,.filter-pill.on{border-color:var(--c-primary);color:var(--c-primary-l);background:rgba(79,142,247,.08)}
@keyframes pulseRing{0%,100%{opacity:.7;transform:scale(1)}50%{opacity:.3;transform:scale(1.5)}}
@keyframes spin{to{transform:rotate(360deg)}}
.pulse-dot{width:6px;height:6px;border-radius:50%;display:inline-block;flex-shrink:0}
.pdot-processing{background:var(--c-primary);animation:pulseRing 1.6s ease infinite}
.pdot-completed{background:var(--c-accent)}
.pdot-pending{background:var(--c-warn)}
.pdot-cancelled{background:var(--c-danger)}

/* Progress bar */
.prog-wrap{width:100%;background:rgba(255,255,255,.07);border-radius:4px;height:5px;margin-top:4px;overflow:hidden;min-width:80px}
.prog-bar{height:100%;border-radius:4px;transition:width .4s ease}
.prog-bar-blue{background:var(--c-primary)}
.prog-bar-green{background:var(--c-accent)}
.prog-bar-yellow{background:var(--c-warn)}

/* Sync button states */
.sync-btn{background:none;border:none;cursor:pointer;color:var(--c-muted);display:flex;padding:5px;border-radius:6px;transition:color .15s}
.sync-btn:hover{color:var(--c-primary-l)}
.sync-btn.syncing .material-symbols-outlined{animation:spin .7s linear infinite}
.sync-btn.syncing{color:var(--c-primary-l);pointer-events:none}

/* Sync All button */
#syncAllBtn{display:flex;align-items:center;gap:5px;padding:7px 14px;font-size:12px}
#syncAllBtn .material-symbols-outlined{font-size:15px;transition:transform .3s}
#syncAllBtn.syncing .material-symbols-outlined{animation:spin .7s linear infinite}

/* Toast */
#toast{position:fixed;bottom:24px;right:24px;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;z-index:9999;opacity:0;transform:translateY(10px);transition:all .25s;pointer-events:none;max-width:320px}
#toast.show{opacity:1;transform:translateY(0)}
#toast.ok{background:#1a3a1a;border:1px solid var(--c-accent);color:var(--c-accent)}
#toast.err{background:#3a1a1a;border:1px solid var(--c-danger);color:var(--c-danger)}
</style>
@endsection

@section('content')

{{-- Toast notification --}}
<div id="toast"></div>

@if(session('success'))
<div class="alert alert-success fade-up" style="margin-bottom:12px;padding:12px 16px;border-radius:10px;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.25);color:var(--c-accent);font-size:13px">
  {{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="alert alert-error fade-up" style="margin-bottom:12px;padding:12px 16px;border-radius:10px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);color:var(--c-danger);font-size:13px">
  {{ $errors->first() }}
</div>
@endif

{{-- Filters --}}
<div class="card fade-up" style="padding:16px;margin-bottom:14px">
  <form action="{{ route('admin.orders.index') }}" method="GET">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px">
      @foreach([''=>'All','pending'=>'Pending','in progress'=>'Running','completed'=>'Done','cancelled'=>'Cancelled','partial'=>'Partial'] as $v=>$l)
      <a href="{{ request()->fullUrlWithQuery(['status'=>$v?:null,'page'=>null]) }}"
         class="filter-pill {{ request('status','')===$v?'on':'' }}">{{ $l }}</a>
      @endforeach
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <div style="position:relative;flex:1;min-width:180px">
        <span class="material-symbols-outlined" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);font-size:17px;color:var(--c-muted);pointer-events:none">search</span>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by user, service, ID…" class="inp" style="padding-left:36px">
      </div>
      <input type="date" name="date_from" value="{{ request('date_from') }}" class="inp" style="width:150px">
      <input type="date" name="date_to"   value="{{ request('date_to') }}"   class="inp" style="width:150px">
      <button type="submit" class="btn-primary" style="padding:10px 18px">Filter</button>
      @if(request()->hasAny(['search','status','date_from','date_to']))
      <a href="{{ route('admin.orders.index') }}" class="btn-ghost" style="padding:10px 14px">Clear</a>
      @endif
    </div>
  </form>
</div>

{{-- Table --}}
<div class="card fade-up" style="overflow:hidden">
  <div style="padding:16px 20px;border-bottom:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <p style="font-size:13px;color:var(--c-muted)">
      <span style="color:var(--c-text);font-weight:700">{{ $orders->total() }}</span> orders total
    </p>
    <button id="syncAllBtn" class="btn-ghost" onclick="syncAll()" data-url="{{ route('admin.sync.orders') }}">
      <span class="material-symbols-outlined">sync</span> Sync All
    </button>
  </div>
  <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:12.5px">
      <thead>
        <tr style="border-bottom:1px solid var(--c-border)">
          @foreach(['#ID','User','Service','Progress','Cost','Status','Date','Actions'] as $h)
          <th style="padding:11px 14px;text-align:{{ $h==='Actions'?'right':($h==='Cost'?'right':'left') }};font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted);white-space:nowrap">{{ $h }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @forelse($orders as $o)
        @php
          $s = strtolower($o->status ?? 'pending');
          [$chip, $dotCls, $barCls] = match(true) {
            $s === 'completed'                          => ['chip-green',  'pdot-completed',  'prog-bar-green'],
            in_array($s, ['in progress','processing'])  => ['chip-blue',   'pdot-processing', 'prog-bar-blue'],
            in_array($s, ['cancelled','refunded'])      => ['chip-red',    'pdot-cancelled',  'prog-bar-yellow'],
            $s === 'partial'                            => ['chip-yellow', 'pdot-pending',    'prog-bar-yellow'],
            default                                     => ['chip-gray',   'pdot-pending',    'prog-bar-blue'],
          };

          $qty       = max(1, (int)($o->quantity ?? 1));
          $remains   = (int)($o->remains ?? 0);
          $startCount = (int)($o->start_count ?? 0);
          $delivered = $qty - $remains;
          $pct       = $s === 'completed' ? 100 : ($s === 'cancelled' ? 0 : max(0, min(100, round(($delivered / $qty) * 100))));
        @endphp
        <tr class="tr-row" id="order-row-{{ $o->id }}">

          {{-- ID --}}
          <td style="padding:12px 14px;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--c-muted)">#{{ $o->id }}</td>

          {{-- User --}}
          <td style="padding:12px 14px;max-width:110px">
            <p style="font-size:12px;font-weight:600;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $o->user->name ?? 'N/A' }}</p>
          </td>

          {{-- Service --}}
          <td style="padding:12px 14px;max-width:160px">
            <p style="font-size:12px;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $o->service->name ?? 'N/A' }}</p>
          </td>

          {{-- Progress --}}
          <td style="padding:12px 14px;min-width:120px">
            <div style="display:flex;align-items:center;gap:6px;white-space:nowrap">
              <span style="font-size:12px;font-weight:600;color:var(--c-text)">{{ number_format($delivered) }}</span>
              <span style="font-size:11px;color:var(--c-muted)">/ {{ number_format($qty) }}</span>
            </div>
            <div class="prog-wrap">
              <div class="prog-bar {{ $barCls }}" style="width:{{ $pct }}%"></div>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:2px">
              <span style="font-size:10px;color:var(--c-muted)">{{ $pct }}%</span>
              @if($remains > 0 && !in_array($s, ['completed','cancelled']))
              <span style="font-size:10px;color:var(--c-muted)">{{ number_format($remains) }} left</span>
              @endif
            </div>
          </td>

          {{-- Cost --}}
          <td style="padding:12px 14px;text-align:right;font-weight:700;color:var(--c-primary-l)">${{ number_format($o->total, 4) }}</td>

          {{-- Status chip --}}
          <td style="padding:12px 14px" id="order-status-{{ $o->id }}">
            <span class="chip {{ $chip }}" style="white-space:nowrap">
              <span class="pulse-dot {{ $dotCls }}"></span>
              {{ ucfirst($o->status ?? 'pending') }}
            </span>
          </td>

          {{-- Date --}}
          <td style="padding:12px 14px;font-size:11px;color:var(--c-muted);white-space:nowrap">{{ $o->created_at->format('d M Y') }}</td>

          {{-- Actions --}}
          <td style="padding:12px 14px;text-align:right">
            <div style="display:flex;align-items:center;justify-content:flex-end;gap:5px">

              {{-- Per-order sync --}}
              <button type="button"
                title="Sync from provider"
                class="sync-btn"
                id="sync-btn-{{ $o->id }}"
                onclick="syncOrder({{ $o->id }}, this)">
                <span class="material-symbols-outlined" style="font-size:15px">sync</span>
              </button>

              {{-- Cancel --}}
              @if(!in_array($s, ['completed','cancelled','refunded']))
              <form method="POST" action="{{ route('admin.orders.cancel', $o->id) }}"
                    onsubmit="return confirm('Cancel order #{{ $o->id }}?')">
                @csrf
                <button type="submit" title="Cancel"
                        style="background:none;border:none;cursor:pointer;color:var(--c-muted);display:flex;padding:5px;border-radius:6px;transition:color .15s"
                        onmouseover="this.style.color='var(--c-danger)'"
                        onmouseout="this.style.color='var(--c-muted)'">
                  <span class="material-symbols-outlined" style="font-size:15px">cancel</span>
                </button>
              </form>
              @endif

            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8" style="padding:60px;text-align:center;color:var(--c-muted)">
            <span class="material-symbols-outlined" style="font-size:44px;opacity:.15;display:block;margin-bottom:10px">list_alt</span>
            No orders found
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($orders->hasPages())
  <div style="padding:14px 18px;border-top:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <p style="font-size:12px;color:var(--c-muted)">{{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ $orders->total() }}</p>
    <div style="display:flex;gap:6px">
      @if($orders->onFirstPage())<span class="btn-ghost" style="opacity:.35;cursor:not-allowed;padding:6px 14px;font-size:12px">← Prev</span>
      @else<a href="{{ $orders->previousPageUrl() }}" class="btn-ghost" style="padding:6px 14px;font-size:12px">← Prev</a>@endif
      @if($orders->hasMorePages())<a href="{{ $orders->nextPageUrl() }}" class="btn-ghost" style="padding:6px 14px;font-size:12px">Next →</a>
      @else<span class="btn-ghost" style="opacity:.35;cursor:not-allowed;padding:6px 14px;font-size:12px">Next →</span>@endif
    </div>
  </div>
  @endif
</div>

@endsection

@section('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';

function toast(msg, type = 'ok') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'show ' + type;
  clearTimeout(t._timer);
  t._timer = setTimeout(() => { t.className = ''; }, 3500);
}

/* ── Per-order sync ───────────────────────────────────────────── */
async function syncOrder(id, btn) {
  btn.classList.add('syncing');
  try {
    const res  = await fetch(`/admin/orders/${id}/sync`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    });
    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      toast(data.error ?? `Order #${id} sync failed.`, 'err');
      return;
    }

    // Update status chip
    if (data.status) {
      const cell = document.getElementById(`order-status-${id}`);
      if (cell) {
        const { chip, dot, label } = statusMeta(data.status);
        cell.innerHTML = `<span class="chip ${chip}" style="white-space:nowrap"><span class="pulse-dot ${dot}"></span>${label}</span>`;
      }
    }

    // Update progress bar
    if (data.remains !== undefined || data.quantity !== undefined) {
      updateProgress(id, data);
    }

    toast(data.message ?? `Order #${id} synced.`, 'ok');

    // Reload row if status changed to completed/cancelled to hide cancel button
    if (['completed', 'cancelled', 'refunded'].includes(data.status)) {
      setTimeout(() => location.reload(), 800);
    }
  } catch (e) {
    toast(`Order #${id}: network error.`, 'err');
  } finally {
    btn.classList.remove('syncing');
  }
}

/* ── Sync All ─────────────────────────────────────────────────── */
async function syncAll() {
  const btn = document.getElementById('syncAllBtn');
  btn.classList.add('syncing');
  btn.disabled = true;

  try {
    const res  = await fetch(btn.dataset.url, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    });
    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      toast(data.message ?? 'Sync All failed.', 'err');
      return;
    }

    toast(data.message ?? 'All orders synced.', 'ok');
    // Reload to reflect all changes
    setTimeout(() => location.reload(), 1200);
  } catch (e) {
    toast('Network error during Sync All.', 'err');
  } finally {
    btn.classList.remove('syncing');
    btn.disabled = false;
  }
}

/* ── Helpers ──────────────────────────────────────────────────── */
function statusMeta(status) {
  const s = (status ?? '').toLowerCase();
  if (s === 'completed')                                    return { chip: 'chip-green',  dot: 'pdot-completed',  label: 'Completed'   };
  if (['in progress','processing','active'].includes(s))    return { chip: 'chip-blue',   dot: 'pdot-processing', label: 'In progress' };
  if (['cancelled','canceled'].includes(s))                 return { chip: 'chip-red',    dot: 'pdot-cancelled',  label: 'Cancelled'   };
  if (s === 'partial')                                      return { chip: 'chip-yellow', dot: 'pdot-pending',    label: 'Partial'     };
  return { chip: 'chip-gray', dot: 'pdot-pending', label: 'Pending' };
}

function updateProgress(id, data) {
  const row = document.getElementById(`order-row-${id}`);
  if (!row) return;

  const qty      = parseInt(data.quantity ?? row.dataset.qty ?? 0) || 1;
  const remains  = parseInt(data.remains ?? 0);
  const status   = (data.status ?? '').toLowerCase();
  const delivered = qty - remains;
  const pct = status === 'completed' ? 100 :
              (status === 'cancelled' ? 0 :
              Math.max(0, Math.min(100, Math.round((delivered / qty) * 100))));

  const barCls = status === 'completed' ? 'prog-bar-green' :
                 (['cancelled','refunded'].includes(status) ? 'prog-bar-yellow' : 'prog-bar-blue');

  const progWrap = row.querySelector('.prog-wrap');
  if (progWrap) {
    const bar = progWrap.querySelector('.prog-bar');
    if (bar) {
      bar.className = `prog-bar ${barCls}`;
      bar.style.width = pct + '%';
    }
  }
}
/* ── Auto-sync ────────────────────────────────────────────────── */
let autoSyncTimer = null;
const AUTO_SYNC_INTERVAL = 30000; // 30 seconds

function setAutoSync(enabled) {
  clearInterval(autoSyncTimer);
  localStorage.setItem('autoSync', enabled ? '1' : '0');
  const toggle    = document.getElementById('autoSyncToggle');
  const indicator = document.getElementById('autoSyncStatus');
  const track     = document.getElementById('autoSyncTrack');
  const thumb     = document.getElementById('autoSyncThumb');
  if (track && thumb) {
    track.style.background = enabled ? 'var(--c-accent)' : '#1e2235';
    thumb.style.left       = enabled ? '22px' : '2px';
    thumb.style.background = enabled ? '#fff' : 'var(--c-muted)';
  }
  if (enabled) {
    toggle.checked = true;
    indicator.textContent = 'Auto-sync ON (every 30s)';
    indicator.style.color = 'var(--c-accent)';
    autoSyncTimer = setInterval(autoSyncTick, AUTO_SYNC_INTERVAL);
  } else {
    toggle.checked = false;
    indicator.textContent = 'Auto-sync OFF';
    indicator.style.color = 'var(--c-muted)';
  }
}

async function autoSyncTick() {
  const indicator = document.getElementById('autoSyncStatus');
  const prev = indicator.textContent;
  indicator.textContent = 'Syncing…';
  indicator.style.color = 'var(--c-primary-l)';
  try {
    const btn = document.getElementById('syncAllBtn');
    const res  = await fetch(btn.dataset.url, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    });
    const data = await res.json().catch(() => ({}));
    if (res.ok && data.updated > 0) {
      toast(data.message, 'ok');
      setTimeout(() => location.reload(), 800);
    } else {
      indicator.textContent = 'Auto-sync ON (every 30s)';
      indicator.style.color = 'var(--c-accent)';
    }
  } catch (e) {
    indicator.textContent = 'Auto-sync ON (every 30s)';
    indicator.style.color = 'var(--c-accent)';
  }
}

// Restore auto-sync preference on page load
document.addEventListener('DOMContentLoaded', () => {
  const saved = localStorage.getItem('autoSync');
  setAutoSync(saved === null ? true : saved === '1'); // default ON
});
</script>
@endsection
