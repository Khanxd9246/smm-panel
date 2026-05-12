@extends('layouts.app')
@section('title','Order #'.$order->id)
@section('page-title','Order #'.$order->id)

@section('css')
<style>
@keyframes pulseRing{0%,100%{opacity:.7;transform:scale(1)}50%{opacity:.3;transform:scale(1.5)}}
.pulse-dot{width:8px;height:8px;border-radius:50%;display:inline-block}
.processing{background:var(--c-primary);animation:pulseRing 1.6s ease infinite}
.completed{background:var(--c-accent)}
.pending-d{background:var(--c-warn)}
.cancelled-d{background:var(--c-danger)}
.detail-row{display:flex;justify-content:space-between;align-items:center;padding:13px 0;border-bottom:1px solid rgba(255,255,255,.05)}
.detail-row:last-child{border-bottom:none}
</style>
@endsection

@section('content')
@php
  $s=strtolower($order->status);
  [$chip,$dot,$barClr]=match(true){
    $s==='completed'                         =>['chip-green','completed','var(--c-accent)'],
    in_array($s,['in progress','processing'])=>['chip-blue','processing','var(--c-primary)'],
    in_array($s,['cancelled','refunded'])    =>['chip-red','cancelled-d','var(--c-danger)'],
    $s==='partial'                           =>['chip-yellow','pending-d','var(--c-warn)'],
    default                                  =>['chip-gray','pending-d','var(--c-muted)'],
  };
  $progress=match(true){
    $s==='completed'=>100,
    $s==='partial'=>round((($order->quantity-$order->remains)/max($order->quantity,1))*100),
    in_array($s,['in progress','processing'])=>max(8,round((($order->quantity-$order->remains)/max($order->quantity,1))*100)),
    default=>0,
  };
@endphp

<div style="max-width:620px;margin:0 auto">

  {{-- Status hero --}}
  <div class="card fade-up" style="padding:26px;margin-bottom:16px;position:relative;overflow:hidden">
    <div style="position:absolute;top:-60px;right:-60px;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,rgba(79,142,247,.1),transparent 65%)"></div>
    <div style="position:relative;z-index:1">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
        <div>
          <p style="font-size:12px;color:var(--c-muted);margin-bottom:3px">Placed {{ $order->created_at->format('d M Y, H:i') }}</p>
          <h2 style="font-size:22px;font-weight:800;color:var(--c-text)">Order #{{ $order->id }}</h2>
        </div>
        <span class="chip {{ $chip }}" style="font-size:12px;padding:6px 14px">
          <span class="pulse-dot {{ $dot }}"></span>
          {{ ucfirst($order->status) }}
        </span>
      </div>

      {{-- Progress --}}
      <div style="margin-bottom:6px;display:flex;justify-content:space-between;font-size:12px;color:var(--c-muted)">
        <span>Delivery progress</span>
        <span style="font-weight:700;color:var(--c-text)">{{ $progress }}%</span>
      </div>
      <div style="height:7px;border-radius:6px;background:rgba(255,255,255,.06);overflow:hidden;margin-bottom:8px">
        <div id="prog-bar" style="height:100%;border-radius:6px;width:0%;transition:width 1.3s cubic-bezier(.4,0,.2,1);background:{{ $barClr }}"></div>
      </div>
      @if($order->remains > 0 && $s !== 'completed')
      <p style="font-size:11.5px;color:var(--c-muted)">{{ number_format($order->remains) }} remaining of {{ number_format($order->quantity) }}</p>
      @endif
    </div>
  </div>

  {{-- Detail grid --}}
  <div class="card fade-up" style="padding:22px;margin-bottom:16px">
    @foreach([
      ['Service',     $order->service->name ?? 'N/A'],
      ['Link',        $order->link],
      ['Quantity',    number_format($order->quantity)],
      ['Remains',     number_format($order->remains ?? 0)],
      ['Cost (USD)',  '$'.number_format($order->total,4)],
      ['Cost (PKR)',  '₨'.number_format($order->total * session('usd_pkr_rate',280),0)],
      ['Provider ID', $order->api_order_id ?? 'N/A'],
      ['Placed',      $order->created_at->format('d M Y H:i')],
    ] as [$lbl,$val])
    <div class="detail-row">
      <span style="font-size:12.5px;color:var(--c-muted)">{{ $lbl }}</span>
      <span style="font-size:13px;font-weight:600;color:var(--c-text);max-width:60%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-align:right" title="{{ $val }}">{{ $val }}</span>
    </div>
    @endforeach
  </div>

  {{-- Actions --}}
  <div style="display:flex;gap:10px" class="fade-up">
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
window.addEventListener('load',()=>{
  setTimeout(()=>document.getElementById('prog-bar').style.width='{{ $progress }}%',200);
});
</script>
@endsection
