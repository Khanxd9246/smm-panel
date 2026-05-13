@extends('layouts.app')
@section('title','Support Tickets')
@section('page-title','Support Tickets')
@section('css')
<style>
.tr-row{border-bottom:1px solid rgba(255,255,255,.04);transition:background .12s;cursor:pointer}
.tr-row:hover{background:rgba(255,255,255,.025)}
.tr-row:last-child{border-bottom:none}
.filter-pill{padding:5px 12px;border-radius:20px;border:1px solid var(--c-border);font-size:11px;font-weight:700;color:var(--c-muted);background:transparent;cursor:pointer;transition:all .15s;text-decoration:none;white-space:nowrap}
.filter-pill.on{border-color:var(--c-primary);color:var(--c-primary-l);background:rgba(79,142,247,.08)}
</style>
@endsection

@section('content')

<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px" class="fade-up">
  @foreach([''=>'All','open'=>'Open','in_progress'=>'In Progress','closed'=>'Closed'] as $v=>$l)
  <a href="{{ request()->fullUrlWithQuery(['status'=>$v?:null,'page'=>null]) }}"
     class="filter-pill {{ request('status','')===$v?'on':'' }}">
    {{ $l }}
    @if($v==='open'&&($c=$tickets->where('status','open')->count())>0)
    <span style="background:var(--c-danger);color:#fff;font-size:9px;font-weight:700;padding:0 5px;border-radius:10px;margin-left:2px">{{ $c }}</span>
    @endif
  </a>
  @endforeach
</div>

<div class="card fade-up" style="overflow:hidden">
  <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:12.5px">
      <thead>
        <tr style="border-bottom:1px solid var(--c-border)">
          @foreach(['#','User','Subject','Priority','Status','Messages','Last Update'] as $h)
          <th style="padding:11px 14px;text-align:left;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted);white-space:nowrap">{{ $h }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @forelse($tickets as $ticket)
        @php
          $ts=strtolower($ticket->status??'open');
          [$chip]=match($ts){
            'open'       =>['chip-red'],
            'in_progress'=>['chip-yellow'],
            'closed'     =>['chip-green'],
            default      =>['chip-gray'],
          };
          $prio=strtolower($ticket->priority??'normal');
          [$pchip]=match($prio){
            'high'  =>['chip-red'],
            'medium'=>['chip-yellow'],
            default =>['chip-gray'],
          };
        @endphp
        <tr class="tr-row" onclick="window.location='{{ route('admin.tickets.show',$ticket->id) }}'">
          <td style="padding:13px 14px;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--c-muted)">#{{ $ticket->id }}</td>
          <td style="padding:13px 14px">
            <div style="display:flex;align-items:center;gap:9px">
              <div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,var(--c-primary),var(--c-purple));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:#fff;flex-shrink:0">{{ strtoupper(substr($ticket->user->name??'U',0,1)) }}</div>
              <p style="font-size:12.5px;font-weight:600;color:var(--c-text)">{{ $ticket->user->name??'N/A' }}</p>
            </div>
          </td>
          <td style="padding:13px 14px;max-width:200px">
            <p style="font-size:13px;font-weight:600;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $ticket->subject }}</p>
          </td>
          <td style="padding:13px 14px"><span class="chip {{ $pchip }}" style="text-transform:capitalize">{{ ucfirst($prio) }}</span></td>
          <td style="padding:13px 14px"><span class="chip {{ $chip }}" style="text-transform:capitalize">{{ ucfirst(str_replace('_',' ',$ts)) }}</span></td>
          <td style="padding:13px 14px;color:var(--c-muted)">{{ $ticket->messages_count??0 }}</td>
          <td style="padding:13px 14px;font-size:11.5px;color:var(--c-muted)">{{ $ticket->updated_at->diffForHumans() }}</td>
        </tr>
        @empty
        <tr>
          <td colspan="7" style="padding:60px;text-align:center;color:var(--c-muted)">
            <span class="material-symbols-outlined" style="font-size:44px;opacity:.15;display:block;margin-bottom:10px">support_agent</span>
            No tickets found
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($tickets->hasPages())
  <div style="padding:14px 18px;border-top:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <p style="font-size:12px;color:var(--c-muted)">{{ $tickets->firstItem() }}–{{ $tickets->lastItem() }} of {{ $tickets->total() }}</p>
    <div style="display:flex;gap:6px">
      @if($tickets->onFirstPage())<span class="btn-ghost" style="opacity:.35;cursor:not-allowed;padding:6px 14px;font-size:12px">← Prev</span>
      @else<a href="{{ $tickets->previousPageUrl() }}" class="btn-ghost" style="padding:6px 14px;font-size:12px">← Prev</a>@endif
      @if($tickets->hasMorePages())<a href="{{ $tickets->nextPageUrl() }}" class="btn-ghost" style="padding:6px 14px;font-size:12px">Next →</a>
      @else<span class="btn-ghost" style="opacity:.35;cursor:not-allowed;padding:6px 14px;font-size:12px">Next →</span>@endif
    </div>
  </div>
  @endif
</div>
@endsection
