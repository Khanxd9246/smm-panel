@extends('layouts.app')
@section('title','Transactions')
@section('page-title','Transactions')
@section('css')
<style>
.tr-row{border-bottom:1px solid rgba(255,255,255,.04);transition:background .12s}
.tr-row:hover{background:rgba(255,255,255,.025)}
.tr-row:last-child{border-bottom:none}
.filter-pill{padding:5px 12px;border-radius:20px;border:1px solid var(--c-border);font-size:11px;font-weight:700;color:var(--c-muted);background:transparent;cursor:pointer;transition:all .15s;text-decoration:none;white-space:nowrap}
.filter-pill.on{border-color:var(--c-primary);color:var(--c-primary-l);background:rgba(79,142,247,.08)}
</style>
@endsection

@section('content')

{{-- Filter bar --}}
<div class="card fade-up" style="padding:16px;margin-bottom:14px">
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
    @foreach([''=>'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $v=>$l)
    <a href="{{ request()->fullUrlWithQuery(['status'=>$v?:null,'page'=>null]) }}"
       class="filter-pill {{ request('status','')===$v?'on':'' }}">{{ $l }}</a>
    @endforeach
    <span style="width:1px;background:var(--c-border);align-self:stretch;margin:0 4px"></span>
    @foreach([''=>'All Types','deposit'=>'Deposits','deduction'=>'Deductions','refund'=>'Refunds'] as $v=>$l)
    <a href="{{ request()->fullUrlWithQuery(['type'=>$v?:null,'page'=>null]) }}"
       class="filter-pill {{ request('type','')===$v?'on':'' }}">{{ $l }}</a>
    @endforeach
  </div>
  <form method="GET" action="{{ route('admin.transactions.index') }}">
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
      @if(request('type'))  <input type="hidden" name="type"   value="{{ request('type') }}">@endif
      <div style="position:relative;flex:1;min-width:180px">
        <span class="material-symbols-outlined" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);font-size:17px;color:var(--c-muted);pointer-events:none">search</span>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search user or reference…" class="inp" style="padding-left:36px">
      </div>
      <button type="submit" class="btn-primary" style="padding:10px 18px">Search</button>
      @if(request()->hasAny(['search','status','type']))
      <a href="{{ route('admin.transactions.index') }}" class="btn-ghost" style="padding:10px 14px">Clear</a>
      @endif
    </div>
  </form>
</div>

{{-- Table --}}
<div class="card fade-up" style="overflow:hidden">
  <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:12.5px">
      <thead>
        <tr style="border-bottom:1px solid var(--c-border)">
          @foreach(['#ID','User','Type','Amount','Reference','Gateway','Status','Date','Actions'] as $h)
          <th style="padding:11px 14px;text-align:{{ $h==='Actions'?'right':($h==='Amount'?'right':'left') }};font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted);white-space:nowrap">{{ $h }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @forelse($transactions as $txn)
        @php
          $ts=strtolower($txn->status??'pending');
          $tt=strtolower($txn->type??'deposit');
          [$tchip]=match($ts){
            'approved'=>['chip-green'],
            'pending' =>['chip-yellow'],
            'rejected'=>['chip-red'],
            default   =>['chip-gray'],
          };
          $amtClr=$tt==='deposit'||$tt==='refund'?'var(--c-accent)':'var(--c-danger)';
          $amtPfx=$tt==='deposit'||$tt==='refund'?'+':'-';
        @endphp
        <tr class="tr-row">
          <td style="padding:12px 14px;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--c-muted)">#{{ $txn->id }}</td>
          <td style="padding:12px 14px">
            <div style="display:flex;align-items:center;gap:8px">
              <div style="width:28px;height:28px;border-radius:7px;background:linear-gradient(135deg,var(--c-primary),var(--c-accent));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;color:#fff;flex-shrink:0">{{ strtoupper(substr($txn->user->name??'U',0,1)) }}</div>
              <div style="min-width:0">
                <p style="font-size:12px;font-weight:600;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">{{ $txn->user->name??'N/A' }}</p>
              </div>
            </div>
          </td>
          <td style="padding:12px 14px">
            <span class="chip {{ $tt==='deposit'?'chip-blue':($tt==='refund'?'chip-green':'chip-gray') }}" style="text-transform:capitalize">{{ $tt }}</span>
          </td>
          <td style="padding:12px 14px;text-align:right;font-weight:800;font-size:14px;color:{{ $amtClr }}">
            {{ $amtPfx }}${{ number_format($txn->amount,2) }}
          </td>
          <td style="padding:12px 14px;font-size:11.5px;color:var(--c-muted);max-width:130px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="{{ $txn->reference }}">{{ $txn->reference??'—' }}</td>
          <td style="padding:12px 14px;font-size:12px;color:var(--c-muted)">{{ ucfirst($txn->gateway??'manual') }}</td>
          <td style="padding:12px 14px"><span class="chip {{ $tchip }}" style="text-transform:capitalize">{{ ucfirst($ts) }}</span></td>
          <td style="padding:12px 14px;font-size:11px;color:var(--c-muted);white-space:nowrap">{{ $txn->created_at->format('d M Y H:i') }}</td>
          <td style="padding:12px 14px;text-align:right">
            @if($ts==='pending' && $tt==='deposit')
            <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px">
              @if($txn->screenshot_path)
              <a href="{{ Storage::url($txn->screenshot_path) }}" target="_blank" class="btn-xs btn-ghost" title="View screenshot">
                <span class="material-symbols-outlined" style="font-size:13px">image</span>
              </a>
              @endif
              <form method="POST" action="{{ route('admin.transactions.approve',$txn) }}" onsubmit="return confirm('Approve ${{ number_format($txn->amount,2) }} for {{ addslashes($txn->user->name??'') }}?')" style="display:inline">
                @csrf
                <button type="submit" class="btn-xs btn-outline-success">✓ Approve</button>
              </form>
              <form method="POST" action="{{ route('admin.transactions.reject',$txn) }}" onsubmit="return confirm('Reject this deposit?')" style="display:inline">
                @csrf
                <button type="submit" class="btn-xs btn-outline-danger">✗ Reject</button>
              </form>
            </div>
            @else
            <span style="font-size:11px;color:var(--c-muted)">—</span>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="9" style="padding:60px;text-align:center;color:var(--c-muted)">
            <span class="material-symbols-outlined" style="font-size:44px;opacity:.15;display:block;margin-bottom:10px">payments</span>
            No transactions found
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($transactions->hasPages())
  <div style="padding:14px 18px;border-top:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <p style="font-size:12px;color:var(--c-muted)">{{ $transactions->firstItem() }}–{{ $transactions->lastItem() }} of {{ $transactions->total() }}</p>
    <div style="display:flex;gap:6px">
      @if($transactions->onFirstPage())<span class="btn-ghost" style="opacity:.35;cursor:not-allowed;padding:6px 14px;font-size:12px">← Prev</span>
      @else<a href="{{ $transactions->previousPageUrl() }}" class="btn-ghost" style="padding:6px 14px;font-size:12px">← Prev</a>@endif
      @if($transactions->hasMorePages())<a href="{{ $transactions->nextPageUrl() }}" class="btn-ghost" style="padding:6px 14px;font-size:12px">Next →</a>
      @else<span class="btn-ghost" style="opacity:.35;cursor:not-allowed;padding:6px 14px;font-size:12px">Next →</span>@endif
    </div>
  </div>
  @endif
</div>
@endsection
