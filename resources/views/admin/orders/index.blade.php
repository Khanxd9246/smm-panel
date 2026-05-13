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
.pulse-dot{width:6px;height:6px;border-radius:50%;display:inline-block;flex-shrink:0}
.pdot-processing{background:var(--c-primary);animation:pulseRing 1.6s ease infinite}
.pdot-completed{background:var(--c-accent)}
.pdot-pending{background:var(--c-warn)}
.pdot-cancelled{background:var(--c-danger)}
</style>
@endsection

@section('content')

{{-- Filters & search --}}
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
    <form method="POST" action="{{ route('admin.orders.sync_all') }}" onsubmit="return confirm('Sync all pending/running orders with providers?')">
      @csrf
      <button type="submit" class="btn-ghost" style="padding:7px 14px;font-size:12px">
        <span class="material-symbols-outlined" style="font-size:15px">sync</span> Sync All
      </button>
    </form>
  </div>
  <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:12.5px">
      <thead>
        <tr style="border-bottom:1px solid var(--c-border)">
          @foreach(['#ID','User','Service','Qty','Remains','Cost','Status','Date','Actions'] as $h)
          <th style="padding:11px 14px;text-align:{{ $h==='Actions'?'right':($h==='Cost'||$h==='Qty'||$h==='Remains'?'right':'left') }};font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted);white-space:nowrap">{{ $h }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @forelse($orders as $o)
        @php
          $s=strtolower($o->status??'pending');
          [$chip,$dotCls]=match(true){
            $s==='completed'                         =>['chip-green','pdot-completed'],
            in_array($s,['in progress','processing'])=>['chip-blue','pdot-processing'],
            in_array($s,['cancelled','refunded'])    =>['chip-red','pdot-cancelled'],
            $s==='partial'                           =>['chip-yellow','pdot-pending'],
            default                                  =>['chip-gray','pdot-pending'],
          };
        @endphp
        <tr class="tr-row">
          <td style="padding:12px 14px;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--c-muted)">#{{ $o->id }}</td>
          <td style="padding:12px 14px;max-width:110px">
            <p style="font-size:12px;font-weight:600;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $o->user->name??'N/A' }}</p>
          </td>
          <td style="padding:12px 14px;max-width:160px">
            <p style="font-size:12px;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $o->service->name??'N/A' }}</p>
          </td>
          <td style="padding:12px 14px;text-align:right;color:var(--c-text);font-weight:600">{{ number_format($o->quantity) }}</td>
          <td style="padding:12px 14px;text-align:right;color:var(--c-muted)">{{ number_format($o->remains??0) }}</td>
          <td style="padding:12px 14px;text-align:right;font-weight:700;color:var(--c-primary-l)">${{ number_format($o->total,4) }}</td>
          <td style="padding:12px 14px">
            <span class="chip {{ $chip }}" style="white-space:nowrap">
              <span class="pulse-dot {{ $dotCls }}"></span>
              {{ ucfirst($o->status??'pending') }}
            </span>
          </td>
          <td style="padding:12px 14px;font-size:11px;color:var(--c-muted);white-space:nowrap">{{ $o->created_at->format('d M Y') }}</td>
          <td style="padding:12px 14px;text-align:right">
            <div style="display:flex;align-items:center;justify-content:flex-end;gap:5px">
              <form method="POST" action="{{ route('admin.orders.sync',$o->id) }}">
                @csrf
                <button type="submit" title="Sync" style="background:none;border:none;cursor:pointer;color:var(--c-muted);display:flex;padding:5px;border-radius:6px;transition:color .15s" onmouseover="this.style.color='var(--c-primary-l)'" onmouseout="this.style.color='var(--c-muted)'">
                  <span class="material-symbols-outlined" style="font-size:15px">sync</span>
                </button>
              </form>
              @if(!in_array($s,['completed','cancelled']))
              <form method="POST" action="{{ route('admin.orders.cancel',$o->id) }}" onsubmit="return confirm('Cancel order #{{ $o->id }}?')">
                @csrf
                <button type="submit" title="Cancel" style="background:none;border:none;cursor:pointer;color:var(--c-muted);display:flex;padding:5px;border-radius:6px;transition:color .15s" onmouseover="this.style.color='var(--c-danger)'" onmouseout="this.style.color='var(--c-muted)'">
                  <span class="material-symbols-outlined" style="font-size:15px">cancel</span>
                </button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="9" style="padding:60px;text-align:center;color:var(--c-muted)">
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
