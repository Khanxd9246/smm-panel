@extends('layouts.app')
@section('title','My Orders')
@section('page-title','My Orders')

@section('css')
<style>
.status-pill{padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.04em;border:1px solid var(--c-border);color:var(--c-muted);background:transparent;cursor:pointer;transition:all .15s;white-space:nowrap}
.status-pill:hover{border-color:var(--c-primary);color:var(--c-primary-l)}
.status-pill.on{background:rgba(79,142,247,.12);border-color:rgba(79,142,247,.4);color:var(--c-primary-l)}
.tr-row{border-bottom:1px solid rgba(255,255,255,.04);transition:background .12s;cursor:pointer}
.tr-row:hover{background:rgba(255,255,255,.025)}
.tr-row:last-child{border-bottom:none}
@keyframes pulseRing{0%,100%{opacity:.7;transform:scale(1)}50%{opacity:.3;transform:scale(1.5)}}
.pulse-dot{width:6px;height:6px;border-radius:50%;display:inline-block;flex-shrink:0}
.processing{background:var(--c-primary);animation:pulseRing 1.6s ease infinite}
.completed{background:var(--c-accent)}
.pending-dot{background:var(--c-warn)}
.cancelled-dot{background:var(--c-danger)}
.partial-dot{background:var(--c-purple)}
</style>
@endsection

@section('content')

{{-- Header + stats strip --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:12px">
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    @php $statuses=['all'=>'All','pending'=>'Pending','in progress'=>'Running','completed'=>'Done','cancelled'=>'Cancelled']; @endphp
    @foreach($statuses as $val=>$label)
    <button class="status-pill {{ $val==='all'?'on':'' }}" data-s="{{ $val }}" onclick="filterStatus('{{ $val }}',this)">
      {{ $label }}
      @if($val!=='all')
      <span style="background:rgba(255,255,255,.07);border-radius:10px;padding:0 5px;font-size:10px;margin-left:2px" class="cnt-{{ str_replace(' ','-',$val) }}">
        {{ $orders->where('status',$val)->count() }}
      </span>
      @else
      <span style="background:rgba(255,255,255,.07);border-radius:10px;padding:0 5px;font-size:10px;margin-left:2px">{{ $orders->total() }}</span>
      @endif
    </button>
    @endforeach
  </div>
  <div style="display:flex;align-items:center;gap:10px">
    <div style="position:relative">
      <span class="material-symbols-outlined" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:17px;color:var(--c-muted);pointer-events:none">search</span>
      <input type="text" id="o-search" class="inp" style="padding-left:36px;width:220px" placeholder="Search orders..." oninput="searchOrders()">
    </div>
    <a href="{{ route('orders.create') }}" class="btn-primary">
      <span class="material-symbols-outlined" style="font-size:16px;font-variation-settings:'FILL' 1">add</span> New
    </a>
  </div>
</div>

{{-- Table card --}}
<div class="card fade-up" style="overflow:hidden">
  <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:13px" id="orders-table">
      <thead>
        <tr style="border-bottom:1px solid var(--c-border)">
          @foreach(['#ID','Service','Link','Qty','Remains','Cost','Status','Date'] as $h)
          <th style="padding:12px 14px;text-align:{{ in_array($h,['Qty','Remains','Cost','Date']) ? 'right' : ($h==='Status'?'center':'left') }};font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted);white-space:nowrap">{{ $h }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody id="orders-body">
        @forelse($orders as $order)
        @php
          $s=strtolower($order->status??'pending');
          [$chip,$dot]=match(true){
            $s==='completed'                         =>['chip-green','completed'],
            in_array($s,['in progress','processing'])=>['chip-blue','processing'],
            in_array($s,['cancelled','refunded'])    =>['chip-red','cancelled-dot'],
            $s==='partial'                           =>['chip-yellow','partial-dot'],
            default                                  =>['chip-gray','pending-dot'],
          };
        @endphp
        <tr class="tr-row order-row"
            data-status="{{ $s }}"
            data-q="{{ strtolower($order->service->name??'') }} #{{ $order->id }}"
            onclick="window.location='{{ route('orders.show',$order->id) }}'">
          <td style="padding:14px;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--c-muted)">#{{ $order->id }}</td>
          <td style="padding:14px;max-width:180px">
            <p style="font-weight:600;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px">{{ $order->service->name ?? 'N/A' }}</p>
          </td>
          <td style="padding:14px;max-width:120px">
            <a href="{{ $order->link }}" target="_blank" rel="noopener" onclick="event.stopPropagation()"
               style="font-size:11.5px;color:var(--c-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:120px;display:block;text-decoration:none"
               title="{{ $order->link }}">{{ Str::limit($order->link,30) }}</a>
          </td>
          <td style="padding:14px;text-align:right;color:var(--c-text);font-weight:600">{{ number_format($order->quantity) }}</td>
          <td style="padding:14px;text-align:right;color:var(--c-muted)">{{ number_format($order->remains??0) }}</td>
          <td style="padding:14px;text-align:right;font-weight:700;color:var(--c-primary-l)">${{ number_format($order->total,4) }}</td>
          <td style="padding:14px;text-align:center">
            <span class="chip {{ $chip }}" style="white-space:nowrap">
              <span class="pulse-dot {{ $dot }}"></span>
              {{ ucfirst($order->status??'pending') }}
            </span>
          </td>
          <td style="padding:14px;text-align:right;font-size:11.5px;color:var(--c-muted);white-space:nowrap">{{ $order->created_at->format('d M Y') }}</td>
        </tr>
        @empty
        <tr>
          <td colspan="8" style="padding:64px;text-align:center;color:var(--c-muted)">
            <span class="material-symbols-outlined" style="font-size:48px;opacity:.15;display:block;margin-bottom:12px">receipt_long</span>
            <p style="margin-bottom:8px">No orders yet</p>
            <a href="{{ route('orders.create') }}" style="color:var(--c-primary);font-weight:600;text-decoration:none;font-size:13px">Place your first order →</a>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($orders->hasPages())
  <div style="padding:14px 18px;border-top:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <p style="font-size:12px;color:var(--c-muted)">Showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ $orders->total() }}</p>
    <div style="display:flex;gap:6px">
      @if($orders->onFirstPage())
      <span class="btn-ghost" style="opacity:.35;cursor:not-allowed;padding:6px 14px;font-size:12px">← Prev</span>
      @else
      <a href="{{ $orders->previousPageUrl() }}" class="btn-ghost" style="padding:6px 14px;font-size:12px">← Prev</a>
      @endif
      @if($orders->hasMorePages())
      <a href="{{ $orders->nextPageUrl() }}" class="btn-ghost" style="padding:6px 14px;font-size:12px">Next →</a>
      @else
      <span class="btn-ghost" style="opacity:.35;cursor:not-allowed;padding:6px 14px;font-size:12px">Next →</span>
      @endif
    </div>
  </div>
  @endif
</div>

@endsection

@section('scripts')
<script>
function filterStatus(s,el){
  document.querySelectorAll('.status-pill').forEach(b=>b.classList.remove('on'));
  el.classList.add('on');
  document.querySelectorAll('.order-row').forEach(r=>{
    r.style.display=(s==='all'||r.dataset.status===s)?'':'none';
  });
}
function searchOrders(){
  const q=document.getElementById('o-search').value.toLowerCase();
  document.querySelectorAll('.order-row').forEach(r=>{
    r.style.display=r.dataset.q.includes(q)?'':'none';
  });
}
</script>
@endsection
