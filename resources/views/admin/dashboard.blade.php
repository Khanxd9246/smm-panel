@extends('layouts.app')
@section('title','Command Center')
@section('page-title','Command Center')
@section('css')
<style>
.a-stat{position:relative;overflow:hidden;border-radius:14px;padding:20px 22px;background:var(--c-card);border:1px solid var(--c-border);transition:transform .18s,border-color .2s}
.a-stat:hover{transform:translateY(-2px);border-color:rgba(79,142,247,.35)}
.a-stat::after{content:'';position:absolute;inset:0;opacity:0;background:radial-gradient(circle at 80% 20%,rgba(79,142,247,.06),transparent 60%);transition:opacity .3s;pointer-events:none}
.a-stat:hover::after{opacity:1}
.a-num{font-size:32px;font-weight:900;letter-spacing:-.03em;line-height:1;margin:10px 0 4px}
.aicon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
@keyframes pulseGlow{0%,100%{box-shadow:0 0 6px rgba(247,111,111,.4)}50%{box-shadow:0 0 16px rgba(247,111,111,.7)}}
.alert-card{background:rgba(247,111,111,.04);border:1px solid rgba(247,111,111,.22);border-radius:14px;padding:20px 22px;margin-bottom:18px}
.dep-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 14px;border-radius:10px;background:var(--c-card);border:1px solid var(--c-border);transition:border-color .15s}
.dep-row:hover{border-color:rgba(247,111,111,.3)}
.provider-row{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:11px 14px;border-radius:10px;background:var(--c-card);border:1px solid var(--c-border);margin-bottom:8px;transition:border-color .15s}
.provider-row:hover{border-color:rgba(79,142,247,.3)}
.q-action{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;background:var(--c-card);border:1px solid var(--c-border);transition:all .15s;text-decoration:none;position:relative;cursor:pointer;width:100%;text-align:left}
.q-action:hover{border-color:rgba(79,142,247,.35);background:rgba(79,142,247,.04)}
.tr-row{border-bottom:1px solid rgba(255,255,255,.04);transition:background .12s}
.tr-row:hover{background:rgba(255,255,255,.025)}
.tr-row:last-child{border-bottom:none}
</style>
@endsection

@section('content')

{{-- ── KPI Strip ─────────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:20px">
@php
$kpis=[
  ['Total Revenue',   '$'.number_format($total_revenue,2),  'payments',     'var(--c-accent)',   'rgba(56,217,169,.1)'],
  ['Total Orders',    number_format($total_orders),          'shopping_cart','var(--c-primary-l)','rgba(79,142,247,.1)'],
  ['Active Users',    number_format($active_users),          'people',       'var(--c-purple)',   'rgba(167,139,250,.1)'],
  ['Pending Orders',  $pending_orders,                       'hourglass',    $pending_orders>0?'var(--c-warn)':'var(--c-accent)', 'rgba(247,201,72,.1)'],
  ['Pending Deposits',$pending_deposits_count,               'account_balance_wallet',$pending_deposits_count>0?'var(--c-danger)':'var(--c-accent)','rgba(247,111,111,.1)'],
];
@endphp
@foreach($kpis as [$label,$val,$icon,$clr,$bg])
<div class="a-stat fade-up">
  <div style="display:flex;justify-content:space-between;align-items:flex-start">
    <span style="font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted)">{{ $label }}</span>
    <div class="aicon" style="background:{{ $bg }}">
      <span class="material-symbols-outlined" style="font-size:18px;color:{{ $clr }};font-variation-settings:'FILL' 1">{{ $icon }}</span>
    </div>
  </div>
  <div class="a-num" style="color:{{ $clr }}">{{ $val }}</div>
</div>
@endforeach
</div>

{{-- ── Pending Deposits Alert ────────────────────────────────────────────── --}}
@if($pending_deposits->count() > 0)
<div class="alert-card fade-up" style="margin-bottom:20px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px">
    <h3 style="font-size:14px;font-weight:700;color:var(--c-danger);display:flex;align-items:center;gap:7px">
      <span class="material-symbols-outlined" style="font-size:18px;font-variation-settings:'FILL' 1;animation:pulseGlow 1.8s ease infinite;border-radius:50%">notifications_active</span>
      {{ $pending_deposits->count() }} Deposit{{ $pending_deposits->count()>1?'s':'' }} Awaiting Approval
    </h3>
    <a href="{{ route('admin.transactions.index',['status'=>'pending','type'=>'deposit']) }}" style="font-size:12px;color:var(--c-primary-l);text-decoration:none;display:flex;align-items:center;gap:3px;font-weight:600">
      View all <span class="material-symbols-outlined" style="font-size:14px">arrow_forward</span>
    </a>
  </div>
  <div style="display:flex;flex-direction:column;gap:8px">
    @foreach($pending_deposits as $dep)
    <div class="dep-row">
      <div style="display:flex;align-items:center;gap:10px;min-width:0">
        <div style="width:34px;height:34px;border-radius:9px;background:rgba(247,111,111,.12);border:1px solid rgba(247,111,111,.25);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:var(--c-danger);flex-shrink:0">{{ strtoupper(substr($dep->user->name??'U',0,1)) }}</div>
        <div style="min-width:0">
          <p style="font-size:13px;font-weight:600;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $dep->user->name??'Unknown' }}</p>
          <p style="font-size:11px;color:var(--c-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">Ref: {{ $dep->reference }}</p>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:12px;flex-shrink:0">
        <div style="text-align:right">
          <p style="font-size:15px;font-weight:800;color:var(--c-accent)">${{ number_format($dep->amount,2) }}</p>
          @if($dep->screenshot_path)
          <a href="{{ Storage::url($dep->screenshot_path) }}" target="_blank" style="font-size:10px;color:var(--c-primary-l);text-decoration:none;display:flex;align-items:center;gap:2px;justify-content:flex-end">
            <span class="material-symbols-outlined" style="font-size:12px">image</span> Screenshot
          </a>
          @else
          <p style="font-size:10px;color:var(--c-muted)">No screenshot</p>
          @endif
        </div>
        <form method="POST" action="{{ route('admin.transactions.approve',$dep) }}" onsubmit="return confirm('Credit ${{ number_format($dep->amount,2) }} to {{ addslashes($dep->user->name??'') }}?')">
          @csrf
          <button type="submit" style="background:rgba(56,217,169,.12);border:1px solid rgba(56,217,169,.3);color:var(--c-accent);padding:6px 14px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;transition:all .15s" onmouseover="this.style.background='rgba(56,217,169,.22)'" onmouseout="this.style.background='rgba(56,217,169,.12)'">✓ Approve</button>
        </form>
      </div>
    </div>
    @endforeach
  </div>
</div>
@endif

{{-- ── Main Grid ─────────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 2fr;gap:14px;margin-bottom:14px" class="admin-main-grid">

  {{-- Providers ──────────────────────────────────────────────────────────── --}}
  <div class="card fade-up" style="padding:20px;display:flex;flex-direction:column;gap:12px">
    <div style="display:flex;align-items:center;justify-content:space-between">
      <h3 style="font-size:14px;font-weight:700;color:var(--c-text)">API Providers</h3>
      <a href="{{ route('admin.providers.create') }}" class="btn-primary" style="padding:5px 12px;font-size:11px">+ Add</a>
    </div>
    <div style="display:flex;flex-direction:column;gap:0">
      @forelse($providers as $p)
      <div class="provider-row">
        <div style="display:flex;align-items:center;gap:10px;min-width:0">
          <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,var(--c-primary),var(--c-accent));display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;color:#fff;flex-shrink:0">{{ strtoupper(substr($p->name,0,1)) }}</div>
          <div style="min-width:0">
            <p style="font-size:13px;font-weight:600;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $p->name }}</p>
            <p style="font-size:11px;color:var(--c-muted)">{{ $p->services_count }} services</p>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
          <div style="width:7px;height:7px;border-radius:50%;background:{{ $p->status==='active'?'var(--c-accent)':'var(--c-danger)' }};box-shadow:0 0 6px {{ $p->status==='active'?'var(--c-accent)':'var(--c-danger)' }}"></div>
          <form method="POST" action="{{ route('admin.providers.sync',$p->id) }}">
            @csrf
            <button type="submit" title="Sync" style="background:none;border:none;cursor:pointer;color:var(--c-muted);display:flex;padding:4px;border-radius:6px;transition:color .15s" onmouseover="this.style.color='var(--c-primary-l)'" onmouseout="this.style.color='var(--c-muted)'">
              <span class="material-symbols-outlined" style="font-size:17px">sync</span>
            </button>
          </form>
        </div>
      </div>
      @empty
      <div style="text-align:center;padding:32px;color:var(--c-muted)">
        <span class="material-symbols-outlined" style="font-size:36px;opacity:.2;display:block;margin-bottom:8px">cloud_off</span>
        <p style="font-size:13px">No providers — <a href="{{ route('admin.providers.create') }}" style="color:var(--c-primary-l);font-weight:600;text-decoration:none">Add one</a></p>
      </div>
      @endforelse
    </div>
    <div style="padding:12px 14px;border-radius:10px;background:rgba(79,142,247,.05);border:1px solid rgba(79,142,247,.15);margin-top:auto">
      <p style="font-size:11px;font-weight:700;color:var(--c-primary-l);display:flex;align-items:center;gap:4px;margin-bottom:4px">
        <span class="material-symbols-outlined" style="font-size:13px">key</span> API Setup
      </p>
      <p style="font-size:11px;color:var(--c-muted);line-height:1.5">Paste your provider key in <a href="{{ route('admin.providers.create') }}" style="color:var(--c-primary-l);font-weight:600;text-decoration:none">Add Provider</a>, then click Sync to import services.</p>
    </div>
  </div>

  {{-- Recent Orders ───────────────────────────────────────────────────────── --}}
  <div class="card fade-up fade-up-d1" style="overflow:hidden;display:flex;flex-direction:column">
    <div style="padding:18px 20px 0;display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
      <h3 style="font-size:14px;font-weight:700;color:var(--c-text)">Recent Orders</h3>
      <a href="{{ route('admin.orders.index') }}" style="font-size:12px;color:var(--c-primary-l);font-weight:600;text-decoration:none;display:flex;align-items:center;gap:3px">
        View all <span class="material-symbols-outlined" style="font-size:14px">arrow_forward</span>
      </a>
    </div>
    <div style="overflow-x:auto;flex:1">
      <table style="width:100%;border-collapse:collapse;font-size:12.5px">
        <thead>
          <tr style="border-bottom:1px solid var(--c-border)">
            @foreach(['ID','User','Service','Total','Status'] as $h)
            <th style="padding:9px {{ in_array($h,['Total','Status'])?'16px 9px':'14px' }};text-align:{{ in_array($h,['Total','Status'])?'right':'left' }};font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted)">{{ $h }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach($recent_orders as $o)
          @php
            $s=strtolower($o->status??'pending');
            [$chip,$dot]=match(true){
              $s==='completed'                         =>['chip-green','var(--c-accent)'],
              in_array($s,['in progress','processing'])=>['chip-blue','var(--c-primary-l)'],
              in_array($s,['cancelled','refunded'])    =>['chip-red','var(--c-danger)'],
              default                                  =>['chip-gray','var(--c-muted)'],
            };
          @endphp
          <tr class="tr-row">
            <td style="padding:11px 14px;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--c-muted)">#{{ $o->id }}</td>
            <td style="padding:11px 14px;max-width:100px;font-size:12px;color:var(--c-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $o->user->name??'N/A' }}</td>
            <td style="padding:11px 14px;max-width:160px">
              <p style="font-size:12px;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px">{{ $o->service->name??'N/A' }}</p>
            </td>
            <td style="padding:11px 16px 11px 14px;text-align:right;font-weight:700;color:var(--c-primary-l)">${{ number_format($o->total,4) }}</td>
            <td style="padding:11px 16px 11px 14px;text-align:right">
              <span class="chip {{ $chip }}" style="white-space:nowrap">
                <span style="width:5px;height:5px;border-radius:50%;background:{{ $dot }};display:inline-block;flex-shrink:0"></span>
                {{ ucfirst($o->status??'pending') }}
              </span>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

</div>

{{-- ── Bottom Grid ───────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px" class="admin-bottom-grid">

  {{-- Recent Users ──────────────────────────────────────────────────────── --}}
  <div class="card fade-up" style="padding:20px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <h3 style="font-size:14px;font-weight:700;color:var(--c-text)">Recent Users</h3>
      <a href="{{ route('admin.users.index') }}" style="font-size:12px;color:var(--c-primary-l);font-weight:600;text-decoration:none">View all →</a>
    </div>
    <div style="display:flex;flex-direction:column;gap:10px">
      @foreach($recent_users as $u)
      <div style="display:flex;align-items:center;gap:10px">
        <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,var(--c-primary),var(--c-purple));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:#fff;flex-shrink:0">{{ strtoupper(substr($u->name,0,1)) }}</div>
        <div style="flex:1;min-width:0">
          <p style="font-size:13px;font-weight:600;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $u->name }}</p>
          <p style="font-size:11px;color:var(--c-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $u->email }}</p>
        </div>
        <div style="text-align:right;flex-shrink:0">
          <p style="font-size:12px;font-weight:700;color:var(--c-accent)">${{ number_format($u->funds,2) }}</p>
          <p style="font-size:10px;color:var(--c-muted)">{{ $u->created_at->diffForHumans() }}</p>
        </div>
      </div>
      @endforeach
    </div>
  </div>

  {{-- Quick Actions ─────────────────────────────────────────────────────── --}}
  <div class="card fade-up fade-up-d1" style="padding:20px">
    <h3 style="font-size:14px;font-weight:700;color:var(--c-text);margin-bottom:14px">Quick Actions</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
      @php
      $actions=[
        ['Pending Deposits','payments',         route('admin.transactions.index',['status'=>'pending','type'=>'deposit']),'var(--c-danger)','GET',$pending_deposits_count],
        ['Sync Services',   'sync',             route('admin.sync.all'),                                                  'var(--c-primary-l)','POST',0],
        ['Services',        'storefront',       route('admin.services.index'),                                            'var(--c-accent)','GET',0],
        ['Users',           'people',           route('admin.users.index'),                                               'var(--c-purple)','GET',0],
        ['Open Tickets',    'confirmation_number',route('admin.tickets.index'),                                           'var(--c-warn)','GET',$open_tickets],
        ['AI Pricing',      'price_change',     '/admin/ai/pricing',                                                      'var(--c-accent)','GET',0],
        ['Supplier Health', 'monitor_heart',    '/admin/ai/suppliers/health',                                             'var(--c-primary-l)','GET',0],
        ['Settings',        'settings',         route('admin.settings'),                                                   'var(--c-muted)','GET',0],
      ];
      @endphp
      @foreach($actions as [$label,$icon,$url,$clr,$method,$badge])
      @if($method==='POST')
      <form method="POST" action="{{ $url }}" style="display:contents">@csrf
        <button type="submit" class="q-action">
          <span class="material-symbols-outlined" style="font-size:18px;color:{{ $clr }};flex-shrink:0">{{ $icon }}</span>
          <span style="font-size:12px;font-weight:600;color:var(--c-muted)">{{ $label }}</span>
        </button>
      </form>
      @else
      <a href="{{ $url }}" class="q-action">
        <span class="material-symbols-outlined" style="font-size:18px;color:{{ $clr }};flex-shrink:0">{{ $icon }}</span>
        <span style="font-size:12px;font-weight:600;color:var(--c-muted)">{{ $label }}</span>
        @if($badge>0)
        <span style="position:absolute;top:6px;right:6px;background:var(--c-danger);color:#fff;font-size:9px;font-weight:700;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center">{{ $badge>9?'9+':$badge }}</span>
        @endif
      </a>
      @endif
      @endforeach
    </div>
  </div>

</div>

@endsection
@section('scripts')
<style>
@media(max-width:900px){.admin-main-grid,.admin-bottom-grid{grid-template-columns:1fr!important}}
</style>
@endsection
