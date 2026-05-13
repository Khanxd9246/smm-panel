@extends('layouts.app')
@section('title','Order #'.$order->id)
@section('page-title','Order #'.$order->id)

@section('css')
<style>
@keyframes pulseRing{0%,100%{opacity:.7;transform:scale(1)}50%{opacity:.3;transform:scale(1.6)}}
@keyframes spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}
.pulse-dot{width:8px;height:8px;border-radius:50%;display:inline-block;flex-shrink:0}
.processing{background:var(--c-primary);animation:pulseRing 1.5s ease infinite}
.completed{background:var(--c-accent)}
.pending-d{background:var(--c-warn)}
.cancelled-d{background:var(--c-danger)}
.detail-row{display:flex;justify-content:space-between;align-items:center;padding:13px 0;border-bottom:1px solid rgba(255,255,255,.05)}
.detail-row:last-child{border-bottom:none}
[data-theme="light"] .detail-row{border-bottom-color:var(--c-border)}

/* Live status badge */
.live-badge{
  display:inline-flex;align-items:center;gap:5px;font-size:10.5px;font-weight:700;
  padding:3px 10px;border-radius:20px;letter-spacing:.04em;
}
.live-badge .spin-icon{animation:spin 1s linear infinite;font-size:13px}

/* Delivery timeline */
.timeline{display:flex;flex-direction:column;gap:0;position:relative}
.timeline::before{content:'';position:absolute;left:14px;top:24px;bottom:24px;width:2px;background:var(--c-border)}
.tl-step{display:flex;align-items:flex-start;gap:14px;position:relative;padding-bottom:16px}
.tl-step:last-child{padding-bottom:0}
.tl-dot{
  width:28px;height:28px;border-radius:50%;flex-shrink:0;z-index:1;
  display:flex;align-items:center;justify-content:center;font-size:12px;
  border:2px solid var(--c-border);background:var(--c-card);transition:all .3s;
}
.tl-dot.done{background:var(--c-accent);border-color:var(--c-accent);color:#fff}
.tl-dot.active{background:var(--c-primary);border-color:var(--c-primary);color:#fff;animation:pulseRing 1.5s ease infinite}
.tl-dot.wait{background:var(--c-card);border-color:var(--c-border);color:var(--c-muted)}
.tl-content{padding-top:4px;flex:1}
.tl-title{font-size:13px;font-weight:600;color:var(--c-text);margin-bottom:2px}
.tl-sub{font-size:11.5px;color:var(--c-muted)}

/* Refresh indicator */
#refresh-indicator{font-size:11px;color:var(--c-muted);display:flex;align-items:center;gap:5px;opacity:0;transition:opacity .3s}
#refresh-indicator.show{opacity:1}

/* Provider status card */
.provider-card{
  background:var(--c-input-bg);border:1px solid var(--c-border);
  border-radius:10px;padding:12px 16px;display:flex;align-items:center;
  justify-content:space-between;flex-wrap:wrap;gap:10px;
}
.prov-field{display:flex;flex-direction:column;gap:2px}
.prov-label{font-size:10px;font-weight:700;color:var(--c-muted);text-transform:uppercase;letter-spacing:.06em}
.prov-val{font-size:13px;font-weight:600;color:var(--c-text)}
</style>
@endsection

@section('content')
@php
  $s = strtolower($order->status);
  [$chip,$dot,$barClr] = match(true) {
    $s === 'completed'                          => ['chip-green', 'completed', 'var(--c-accent)'],
    in_array($s, ['in progress','processing'])  => ['chip-blue',  'processing','var(--c-primary)'],
    in_array($s, ['cancelled','refunded'])       => ['chip-red',   'cancelled-d','var(--c-danger)'],
    $s === 'partial'                             => ['chip-yellow','pending-d', 'var(--c-warn)'],
    default                                      => ['chip-gray',  'pending-d', 'var(--c-muted)'],
  };
  $progress = match(true) {
    $s === 'completed'                         => 100,
    $s === 'partial'                           => round((($order->quantity - $order->remains) / max($order->quantity,1)) * 100),
    in_array($s,['in progress','processing'])  => max(8, round((($order->quantity - $order->remains) / max($order->quantity,1)) * 100)),
    default                                    => 0,
  };
  $isActive   = in_array($s, ['pending','in progress','processing']);
  $isTerminal = in_array($s, ['completed','cancelled','refunded']);
  $svc        = $order->service;
@endphp

<div style="max-width:640px;margin:0 auto">

  {{-- ── Status hero card ─────────────────────────────────────────────── --}}
  <div class="card fade-up" style="padding:26px;margin-bottom:16px;position:relative;overflow:hidden">
    <div style="position:absolute;top:-60px;right:-60px;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,{{ $barClr }}20,transparent 65%)"></div>
    <div style="position:relative;z-index:1">

      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
        <div>
          <p style="font-size:12px;color:var(--c-muted);margin-bottom:3px">Placed {{ $order->created_at->format('d M Y, H:i') }}</p>
          <h2 style="font-size:22px;font-weight:800;color:var(--c-text)">Order #{{ $order->id }}</h2>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px">
          <span class="chip {{ $chip }}" id="statusChip" style="font-size:12px;padding:6px 14px;display:flex;align-items:center;gap:6px">
            <span class="pulse-dot {{ $dot }}" id="statusDot"></span>
            <span id="statusText">{{ ucfirst($order->status) }}</span>
          </span>
          @if($isActive)
          <div id="refresh-indicator">
            <span class="material-symbols-outlined spin-icon" style="font-size:13px">sync</span>
            <span id="refresh-msg">Checking…</span>
          </div>
          @endif
        </div>
      </div>

      {{-- Progress bar --}}
      <div style="margin-bottom:6px;display:flex;justify-content:space-between;font-size:12px;color:var(--c-muted)">
        <span>Delivery progress</span>
        <span style="font-weight:700;color:var(--c-text)" id="progressPct">{{ $progress }}%</span>
      </div>
      <div style="height:7px;border-radius:6px;background:rgba(128,128,128,.12);overflow:hidden;margin-bottom:10px">
        <div id="prog-bar" style="height:100%;border-radius:6px;width:0%;transition:width 1.3s cubic-bezier(.4,0,.2,1);background:{{ $barClr }}"></div>
      </div>
      <div id="remains-text" style="font-size:11.5px;color:var(--c-muted)">
        @if($order->remains > 0 && $s !== 'completed')
        {{ number_format($order->remains) }} remaining of {{ number_format($order->quantity) }}
        @endif
      </div>
    </div>
  </div>

  {{-- ── Delivery timeline ─────────────────────────────────────────────── --}}
  @if($svc)
  <div class="card fade-up fade-up-d1" style="padding:22px;margin-bottom:16px">
    <div style="font-size:13px;font-weight:700;color:var(--c-text);margin-bottom:16px;display:flex;align-items:center;gap:8px">
      <span class="material-symbols-outlined" style="font-size:16px;color:var(--c-primary)">schedule</span>
      Delivery Timeline
      {{-- Delivery badge --}}
      <span class="chip {{ $svc->delivery_color }}" style="font-size:10px;padding:2px 10px;margin-left:auto">
        {{ $svc->delivery_label }}
      </span>
    </div>

    <div class="timeline">
      @php
        $steps = [
          ['Order Placed',           'Order received and queued for processing',                          true,                                          false],
          ['Processing Started',     $svc->estimated_start_label,                                        in_array($s,['in progress','processing','completed','partial']), in_array($s,['in progress','processing'])],
          ['Delivery In Progress',   'Started: ' . ($order->start_count ? number_format($order->start_count) . ' initial count' : $svc->estimated_complete_label), in_array($s,['in progress','completed','partial']), $s==='in progress'],
          ['Completed',              $s === 'completed' ? 'Delivered ' . $order->updated_at->diffForHumans() : $svc->estimated_complete_label,           $s === 'completed',                                    false],
        ];
        if(in_array($s,['cancelled','refunded'])){
          $steps[] = [ucfirst($s), $s==='refunded'?'Funds returned to wallet':'Order was cancelled', true, false];
        }
      @endphp
      @foreach($steps as [$title,$sub,$done,$active])
      <div class="tl-step">
        <div class="tl-dot {{ $done ? ($active ? 'active' : 'done') : 'wait' }}">
          @if($done && !$active) ✓ @elseif($active) … @else {{ $loop->iteration }} @endif
        </div>
        <div class="tl-content">
          <div class="tl-title" style="color:{{ $done ? 'var(--c-text)' : 'var(--c-muted)' }}">{{ $title }}</div>
          <div class="tl-sub">{{ $sub }}</div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- ── Provider live status ─────────────────────────────────────────── --}}
  @if($order->api_order_id)
  <div class="card fade-up fade-up-d2" style="padding:20px;margin-bottom:16px">
    <div style="font-size:13px;font-weight:700;color:var(--c-text);margin-bottom:12px;display:flex;align-items:center;gap:8px">
      <span class="material-symbols-outlined" style="font-size:16px;color:var(--c-primary)">satellite_alt</span>
      Provider Status
      @if($isActive)
      <span class="live-badge" style="background:rgba(79,142,247,.1);color:var(--c-primary);margin-left:auto">
        <span class="material-symbols-outlined spin-icon">sync</span> LIVE
      </span>
      @endif
    </div>
    <div class="provider-card">
      <div class="prov-field">
        <span class="prov-label">Provider Order ID</span>
        <span class="prov-val">#{{ $order->api_order_id }}</span>
      </div>
      <div class="prov-field">
        <span class="prov-label">Status</span>
        <span class="prov-val" id="provStatus">{{ ucfirst($order->status) }}</span>
      </div>
      <div class="prov-field">
        <span class="prov-label">Delivered</span>
        <span class="prov-val" id="provDelivered">
          @if($order->start_count && $order->remains !== null)
            {{ number_format($order->quantity - $order->remains) }} / {{ number_format($order->quantity) }}
          @else
            {{ number_format($order->quantity) }} qty
          @endif
        </span>
      </div>
      <div class="prov-field">
        <span class="prov-label">Remains</span>
        <span class="prov-val" id="provRemains">{{ number_format($order->remains ?? 0) }}</span>
      </div>
      @if(!$isTerminal)
      <div class="prov-field">
        <span class="prov-label">Last Checked</span>
        <span class="prov-val" id="lastChecked" style="font-size:11px">Just now</span>
      </div>
      @endif
    </div>
    @if(!$isActive)
    <p style="font-size:11px;color:var(--c-muted);margin-top:8px">This order is {{ $order->status }} — no further polling needed.</p>
    @endif
  </div>
  @endif

  {{-- ── Order details ─────────────────────────────────────────────────── --}}
  <div class="card fade-up fade-up-d2" style="padding:22px;margin-bottom:16px">
    <div style="font-size:13px;font-weight:700;color:var(--c-text);margin-bottom:14px">Order Details</div>
    @php
      $details = [
        ['Service',    $svc ? $svc->display_name : 'N/A'],
        ['Link',       $order->link],
        ['Quantity',   number_format($order->quantity)],
        ['Cost (USD)', '$'.number_format($order->total,4)],
        ['Cost (PKR)', '₨'.number_format($order->total * session('usd_pkr_rate',280),0)],
        ['Placed',     $order->created_at->format('d M Y H:i')],
        ['Updated',    $order->updated_at->diffForHumans()],
      ];
      if($svc) {
        $details[] = ['Est. Start',    $svc->estimated_start_label];
        $details[] = ['Est. Complete', $svc->estimated_complete_label];
      }
    @endphp
    @foreach($details as [$lbl,$val])
    <div class="detail-row">
      <span style="font-size:12.5px;color:var(--c-muted)">{{ $lbl }}</span>
      <span style="font-size:13px;font-weight:600;color:var(--c-text);max-width:65%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-align:right" title="{{ $val }}">{{ $val }}</span>
    </div>
    @endforeach
  </div>

  {{-- ── Actions ───────────────────────────────────────────────────────── --}}
  <div style="display:flex;gap:10px" class="fade-up fade-up-d3">
    <a href="{{ route('orders.index') }}" class="btn-ghost" style="flex:1;justify-content:center">
      <span class="material-symbols-outlined" style="font-size:17px">arrow_back</span> All Orders
    </a>
    <a href="{{ route('orders.create') }}" class="btn-primary" style="flex:1;justify-content:center">
      <span class="material-symbols-outlined" style="font-size:17px;font-variation-settings:'FILL' 1">add_circle</span> New Order
    </a>
  </div>

</div>
@endsection

@section('scripts')
<script>
/* ── Animate progress bar on load ── */
window.addEventListener('load', function() {
  setTimeout(function() {
    document.getElementById('prog-bar').style.width = '{{ $progress }}%';
  }, 200);
});

/* ── Live status polling ── */
@if($isActive && $order->api_order_id)
(function() {
  var ORDER_ID    = {{ $order->id }};
  var POLL_SECS   = 15;          // poll every 15 seconds
  var POLL_URL    = '{{ route("orders.live_status", $order->id) }}';
  var CSRF        = document.querySelector('meta[name="csrf-token"]').content;
  var terminal    = false;
  var timer       = null;
  var lastChecked = Date.now();

  var statusChip  = document.getElementById('statusChip');
  var statusDot   = document.getElementById('statusDot');
  var statusText  = document.getElementById('statusText');
  var progBar     = document.getElementById('prog-bar');
  var progPct     = document.getElementById('progressPct');
  var remainsTxt  = document.getElementById('remains-text');
  var provStatus  = document.getElementById('provStatus');
  var provRemains = document.getElementById('provRemains');
  var provDelivered = document.getElementById('provDelivered');
  var lastChk     = document.getElementById('lastChecked');
  var refreshInd  = document.getElementById('refresh-indicator');
  var refreshMsg  = document.getElementById('refresh-msg');

  function chipClass(s) {
    if (s === 'completed') return 'chip-green';
    if (s === 'in progress' || s === 'processing') return 'chip-blue';
    if (s === 'cancelled' || s === 'refunded') return 'chip-red';
    if (s === 'partial') return 'chip-yellow';
    return 'chip-gray';
  }
  function dotClass(s) {
    if (s === 'completed') return 'completed';
    if (s === 'in progress' || s === 'processing') return 'processing';
    if (s === 'cancelled' || s === 'refunded') return 'cancelled-d';
    return 'pending-d';
  }
  function calcProgress(qty, remains, s) {
    if (s === 'completed') return 100;
    if (remains != null && qty) {
      var pct = Math.round(((qty - remains) / qty) * 100);
      return s === 'in progress' ? Math.max(8, pct) : pct;
    }
    return 0;
  }

  function poll() {
    if (terminal) return;
    if (refreshInd) { refreshInd.classList.add('show'); refreshMsg.textContent = 'Checking…'; }

    fetch(POLL_URL, {
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      lastChecked = Date.now();
      if (lastChk) lastChk.textContent = 'Just now';

      var s        = (data.status || '').toLowerCase();
      var remains  = data.remains  != null ? data.remains  : {{ $order->remains ?? 0 }};
      var qty      = {{ $order->quantity }};
      var pct      = calcProgress(qty, remains, s);

      // Update status chip
      if (statusChip) {
        statusChip.className = 'chip ' + chipClass(s);
        statusChip.style.cssText = 'font-size:12px;padding:6px 14px;display:flex;align-items:center;gap:6px';
      }
      if (statusDot) statusDot.className = 'pulse-dot ' + dotClass(s);
      if (statusText) statusText.textContent = s.charAt(0).toUpperCase() + s.slice(1);

      // Progress bar
      if (progBar) progBar.style.width = pct + '%';
      if (progPct) progPct.textContent = pct + '%';
      if (remainsTxt && s !== 'completed') {
        remainsTxt.textContent = remains > 0 ? (remains.toLocaleString() + ' remaining of ' + qty.toLocaleString()) : '';
      }

      // Provider card
      if (provStatus) provStatus.textContent = s.charAt(0).toUpperCase() + s.slice(1);
      if (provRemains) provRemains.textContent = remains.toLocaleString();
      if (provDelivered) {
        var delivered = qty - remains;
        provDelivered.textContent = (delivered > 0 ? delivered.toLocaleString() + ' / ' : '') + qty.toLocaleString();
      }

      // Source indicator
      if (refreshMsg) refreshMsg.textContent = data.source === 'provider' ? '✓ Live from provider' : '✓ Cached';
      setTimeout(function() { if (refreshInd) refreshInd.classList.remove('show'); }, 2000);

      if (data.terminal) {
        terminal = true;
        clearInterval(timer);
        if (data.status === 'completed') {
          showToast('Order #{{ $order->id }} completed!', 'success');
        }
      }
    })
    .catch(function(err) {
      if (refreshMsg) refreshMsg.textContent = 'Offline';
      setTimeout(function() { if (refreshInd) refreshInd.classList.remove('show'); }, 2000);
    });
  }

  // Initial poll after 2s, then every POLL_SECS
  setTimeout(poll, 2000);
  timer = setInterval(poll, POLL_SECS * 1000);

  // Update "last checked" display every 10s
  setInterval(function() {
    if (!lastChk || terminal) return;
    var ago = Math.round((Date.now() - lastChecked) / 1000);
    lastChk.textContent = ago < 5 ? 'Just now' : ago + 's ago';
  }, 10000);
})();
@endif
</script>
@endsection
