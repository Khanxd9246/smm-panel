@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('css')
<style>
.stat-card{position:relative;overflow:hidden;border-radius:14px;padding:20px;background:var(--c-card);border:1px solid var(--c-border);transition:transform .2s,border-color .2s}
.stat-card:hover{transform:translateY(-2px);border-color:rgba(79,142,247,.35)}
.stat-card::after{content:'';position:absolute;inset:0;opacity:0;background:radial-gradient(circle at top right,rgba(79,142,247,.06),transparent 60%);transition:opacity .3s}
.stat-card:hover::after{opacity:1}
.stat-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.stat-num{font-size:30px;font-weight:800;line-height:1;letter-spacing:-.03em;margin:10px 0 4px}
.stat-sub{font-size:11.5px;color:var(--c-muted)}

/* sparkline bar */
.spark{display:flex;align-items:flex-end;gap:2px;height:28px}
.spark-bar{flex:1;border-radius:2px;background:rgba(79,142,247,.25);transition:all .3s}
.spark-bar.lit{background:var(--c-primary)}

/* quick order */
.qo-label{font-size:10.5px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted);margin-bottom:6px;display:block}

/* table rows */
.tr-row{border-bottom:1px solid rgba(255,255,255,.04);transition:background .15s;cursor:pointer}
.tr-row:hover{background:rgba(255,255,255,.025)}
.tr-row:last-child{border-bottom:none}

/* pulse ring for in progress */
@keyframes pulseRing{0%,100%{opacity:.6;transform:scale(1)}50%{opacity:.3;transform:scale(1.4)}}
.pulse-dot{width:6px;height:6px;border-radius:50%;display:inline-block;flex-shrink:0}
.pulse-dot.processing{background:var(--c-primary);animation:pulseRing 1.6s ease infinite}
.pulse-dot.completed{background:var(--c-accent)}
.pulse-dot.pending{background:var(--c-warn)}
.pulse-dot.cancelled{background:var(--c-danger)}

/* welcome gradient border */
.welcome-card{background:var(--c-card);border:1px solid var(--c-border);border-radius:16px;padding:28px;position:relative;overflow:hidden}
.welcome-card::before{content:'';position:absolute;top:-60px;right:-60px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(79,142,247,.12),transparent 70%)}
.welcome-card::after{content:'';position:absolute;bottom:-40px;left:20%;width:180px;height:180px;border-radius:50%;background:radial-gradient(circle,rgba(56,217,169,.08),transparent 70%)}
</style>
@endsection

@section('content')

{{-- ── Welcome ──────────────────────────────────────────────────────── --}}
<div class="welcome-card mb-6 fade-up">
  <div style="position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px">
    <div>
      <p style="font-size:12px;color:var(--c-muted);font-weight:600;letter-spacing:.06em;margin-bottom:4px">
        {{ now()->format('l, d M Y') }}
      </p>
      <h2 style="font-size:22px;font-weight:800;color:var(--c-text);margin-bottom:4px">
        Welcome back, <span style="background:linear-gradient(90deg,var(--c-primary),var(--c-accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent">{{ auth()->user()->name }}</span> 👋
      </h2>
      <p style="font-size:13px;color:var(--c-muted)">Your panel is live and all systems are running.</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a href="{{ route('orders.create') }}" class="btn-primary">
        <span class="material-symbols-outlined" style="font-size:17px;font-variation-settings:'FILL' 1">add_circle</span> New Order
      </a>
      <a href="{{ route('funds.index') }}" class="btn-ghost">
        <span class="material-symbols-outlined" style="font-size:17px">account_balance_wallet</span> Add Funds
      </a>
    </div>
  </div>
</div>

{{-- ── Stat Cards ────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:24px">

  {{-- Balance --}}
  <div class="stat-card fade-up fade-up-d1">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <span style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted)">Balance</span>
      <div class="stat-icon" style="background:rgba(79,142,247,.12)">
        <span class="material-symbols-outlined" style="font-size:18px;color:var(--c-primary);font-variation-settings:'FILL' 1">account_balance_wallet</span>
      </div>
    </div>
    <div class="stat-num" style="color:var(--c-text)">${{ number_format($balance, 2) }}</div>
    <div style="display:flex;align-items:center;justify-content:space-between">
      <span class="stat-sub">₨{{ number_format($balance * session('usd_pkr_rate',280), 0) }} PKR</span>
      <a href="{{ route('funds.index') }}" style="font-size:11px;color:var(--c-primary);font-weight:600;text-decoration:none">+ Top up</a>
    </div>
    <div style="margin-top:14px;height:3px;border-radius:2px;background:rgba(255,255,255,.06)">
      <div style="height:100%;border-radius:2px;background:linear-gradient(90deg,var(--c-primary),var(--c-accent));width:{{ min(100, ($balance/50)*100) }}%"></div>
    </div>
  </div>

  {{-- Total Orders --}}
  <div class="stat-card fade-up fade-up-d2">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <span style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted)">Total Orders</span>
      <div class="stat-icon" style="background:rgba(167,139,250,.12)">
        <span class="material-symbols-outlined" style="font-size:18px;color:var(--c-purple);font-variation-settings:'FILL' 1">shopping_cart</span>
      </div>
    </div>
    <div class="stat-num" style="color:var(--c-text)">{{ number_format($total_orders) }}</div>
    <div style="display:flex;align-items:center;gap:5px">
      <span class="material-symbols-outlined" style="font-size:13px;color:var(--c-accent)">trending_up</span>
      <span class="stat-sub" style="color:var(--c-accent);font-weight:600">+{{ $orders_this_week }}</span>
      <span class="stat-sub">this week</span>
    </div>
    {{-- sparkline --}}
    <div class="spark" style="margin-top:12px">
      @for($i=0;$i<7;$i++)
      <div class="spark-bar {{ $i===6?'lit':'' }}" style="height:{{ rand(30,100) }}%"></div>
      @endfor
    </div>
  </div>

  {{-- Pending --}}
  <div class="stat-card fade-up fade-up-d3">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <span style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted)">Pending</span>
      <div class="stat-icon" style="background:rgba(247,201,72,.1)">
        <span class="material-symbols-outlined" style="font-size:18px;color:var(--c-warn);font-variation-settings:'FILL' 1">hourglass_empty</span>
      </div>
    </div>
    <div class="stat-num" style="color:{{ $pending_orders > 0 ? 'var(--c-warn)' : 'var(--c-text)' }}">{{ $pending_orders }}</div>
    @if($pending_orders > 0)
    <div style="display:flex;align-items:center;gap:5px">
      <div class="pulse-dot pending"></div>
      <span class="stat-sub" style="color:var(--c-warn)">Needs attention</span>
    </div>
    @else
    <div style="display:flex;align-items:center;gap:5px">
      <div class="pulse-dot completed"></div>
      <span class="stat-sub" style="color:var(--c-accent)">All clear</span>
    </div>
    @endif
  </div>

  {{-- Success Rate --}}
  <div class="stat-card fade-up fade-up-d4">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <span style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted)">Success Rate</span>
      <div class="stat-icon" style="background:rgba(56,217,169,.1)">
        <span class="material-symbols-outlined" style="font-size:18px;color:var(--c-accent);font-variation-settings:'FILL' 1">verified</span>
      </div>
    </div>
    <div class="stat-num" style="color:var(--c-text)">{{ $success_rate }}%</div>
    <div style="display:flex;align-items:center;gap:5px;margin-bottom:10px">
      <span class="material-symbols-outlined" style="font-size:13px;color:var(--c-accent)">bolt</span>
      <span class="stat-sub" style="color:var(--c-accent);font-weight:600">{{ $success_rate >= 90 ? 'Excellent' : ($success_rate >= 70 ? 'Good' : 'Fair') }}</span>
    </div>
    <div style="height:5px;border-radius:3px;background:rgba(255,255,255,.06);overflow:hidden">
      <div id="success-bar" style="height:100%;border-radius:3px;width:0%;transition:width 1.2s cubic-bezier(.4,0,.2,1);background:linear-gradient(90deg,var(--c-primary),var(--c-accent))"></div>
    </div>
  </div>

</div>

{{-- ── Main Grid ────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 2fr;gap:16px" class="main-grid">

  {{-- Quick Order ──────────────────────────────────────────────────── --}}
  <div class="card fade-up" style="padding:22px;display:flex;flex-direction:column">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px">
      <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,var(--c-primary),var(--c-accent));display:flex;align-items:center;justify-content:center">
        <span class="material-symbols-outlined" style="font-size:16px;color:#fff;font-variation-settings:'FILL' 1">rocket_launch</span>
      </div>
      <h3 style="font-size:15px;font-weight:700;color:var(--c-text)">Quick Order</h3>
    </div>

    <form method="POST" action="{{ route('orders.store') }}" id="qo-form" style="display:flex;flex-direction:column;gap:14px;flex:1">
      @csrf
      <div>
        <span class="qo-label">Category</span>
        <div style="position:relative">
          <select name="category_id" id="qo-cat" class="inp" onchange="loadSvcs(this.value)" style="padding-right:36px">
            <option value="">Select category</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
          </select>
          <span class="material-symbols-outlined" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--c-muted);font-size:18px">expand_more</span>
        </div>
      </div>
      <div>
        <span class="qo-label">Service</span>
        <div style="position:relative">
          <select name="service_id" id="qo-svc" class="inp" onchange="updateQO()" style="padding-right:36px">
            <option value="">Select service</option>
          </select>
          <span class="material-symbols-outlined" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--c-muted);font-size:18px">expand_more</span>
        </div>
      </div>
      <div>
        <span class="qo-label">Link</span>
        <input type="url" name="link" class="inp" placeholder="https://" required>
      </div>
      <div>
        <span class="qo-label">Quantity</span>
        <input type="number" name="quantity" id="qo-qty" class="inp" placeholder="1000" min="1" required oninput="updateQO()">
      </div>
      <div style="margin-top:auto;padding:14px;border-radius:10px;background:rgba(255,255,255,.03);border:1px solid var(--c-border)">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div>
            <p style="font-size:11px;color:var(--c-muted);margin-bottom:2px">Estimated cost</p>
            <p style="font-size:11px;color:var(--c-muted)" id="qo-pkr">₨0</p>
          </div>
          <p style="font-size:22px;font-weight:800;color:var(--c-text)" id="qo-price">$0.0000</p>
        </div>
      </div>
      <button type="submit" class="btn-primary" style="justify-content:center;padding:12px">
        <span class="material-symbols-outlined" style="font-size:17px;font-variation-settings:'FILL' 1">send</span> Place Order
      </button>
    </form>
  </div>

  {{-- Recent Orders ────────────────────────────────────────────────── --}}
  <div class="card fade-up fade-up-d1" style="display:flex;flex-direction:column;overflow:hidden">
    <div style="padding:20px 22px 0;display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <h3 style="font-size:15px;font-weight:700;color:var(--c-text)">Recent Activity</h3>
      <a href="{{ route('orders.index') }}" style="font-size:12px;color:var(--c-primary);font-weight:600;text-decoration:none;display:flex;align-items:center;gap:3px">
        View all <span class="material-symbols-outlined" style="font-size:14px">arrow_forward</span>
      </a>
    </div>
    <div style="overflow-x:auto;flex:1">
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
          <tr style="border-bottom:1px solid var(--c-border)">
            @foreach(['ID','Service','Link','Qty','Cost','Status'] as $h)
            <th style="padding:8px {{ in_array($h,['Cost','Status','Qty']) ? '16px 8px' : '8px' }};text-align:{{ in_array($h,['Cost','Status','Qty']) ? 'right' : 'left' }};font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted);font-weight:600;{{ $h==='Link' ? 'display:none' : '' }}">{{ $h }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @forelse($recent_orders as $order)
          @php
            $s=strtolower($order->status??'pending');
            [$chipCls,$dotCls]= match(true){
              $s==='completed'                         =>['chip-green','completed'],
              in_array($s,['in progress','processing'])=>['chip-blue','processing'],
              in_array($s,['cancelled','refunded'])    =>['chip-red','cancelled'],
              $s==='partial'                           =>['chip-yellow','pending'],
              default                                  =>['chip-gray','pending'],
            };
          @endphp
          <tr class="tr-row" onclick="window.location='{{ route('orders.show',$order->id) }}'">
            <td style="padding:13px 8px;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--c-muted)">#{{ $order->id }}</td>
            <td style="padding:13px 8px;max-width:150px">
              <p style="font-weight:600;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px">{{ $order->service->name ?? 'N/A' }}</p>
            </td>
            <td style="padding:13px 8px;max-width:100px;display:none">
              <p style="font-size:11px;color:var(--c-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">{{ $order->link }}</p>
            </td>
            <td style="padding:13px 16px 13px 8px;text-align:right;color:var(--c-muted)">{{ number_format($order->quantity) }}</td>
            <td style="padding:13px 16px 13px 8px;text-align:right;font-weight:700;color:var(--c-primary-l)">${{ number_format($order->total,4) }}</td>
            <td style="padding:13px 16px 13px 8px;text-align:right">
              <span class="chip {{ $chipCls }}" style="white-space:nowrap">
                <span class="pulse-dot {{ $dotCls }}"></span>
                {{ ucfirst($order->status ?? 'pending') }}
              </span>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" style="padding:48px;text-align:center;color:var(--c-muted)">
              <span class="material-symbols-outlined" style="font-size:40px;opacity:.2;display:block;margin-bottom:10px">receipt_long</span>
              No orders yet —
              <a href="{{ route('orders.create') }}" style="color:var(--c-primary);font-weight:600;text-decoration:none">place your first →</a>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>

@endsection

@section('scripts')
<script>
const PKR={{ session('usd_pkr_rate',280) }};
const SVC_URL='{{ route("orders.services_by_category") }}';
let cache={};

// Animate success bar
window.addEventListener('load',()=>{
  setTimeout(()=>document.getElementById('success-bar').style.width='{{ $success_rate }}%',200);
});

function loadSvcs(catId){
  const sel=document.getElementById('qo-svc');
  sel.innerHTML='<option value="">Loading...</option>';
  sel.disabled=true;
  if(cache[catId]){renderSvcs(sel,cache[catId]);return;}
  fetch(SVC_URL+'?category_id='+catId,{headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(list=>{cache[catId]=list;renderSvcs(sel,list);})
    .catch(()=>{sel.innerHTML='<option value="">Error loading</option>';sel.disabled=false;});
}
function renderSvcs(sel,list){
  sel.innerHTML='<option value="">Select service</option>';
  list.forEach(s=>{
    const o=document.createElement('option');
    o.value=s.id;o.dataset.rate=s.rate;o.dataset.min=s.min;o.dataset.max=s.max;
    o.textContent=s.name+' — $'+parseFloat(s.rate).toFixed(4)+'/1K';
    sel.appendChild(o);
  });
  sel.disabled=false;updateQO();
}
function updateQO(){
  const sel=document.getElementById('qo-svc');
  const opt=sel.options[sel.selectedIndex];
  const qty=parseInt(document.getElementById('qo-qty').value)||0;
  const rate=parseFloat(opt?.dataset?.rate||0);
  const t=(qty/1000)*rate;
  document.getElementById('qo-price').textContent='$'+t.toFixed(4);
  document.getElementById('qo-pkr').textContent='₨'+Math.round(t*PKR).toLocaleString();
}
</script>
<style>
@media(max-width:768px){
  .main-grid{grid-template-columns:1fr!important}
}
</style>
@endsection
