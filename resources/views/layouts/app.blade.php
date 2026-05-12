<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', config('app.name','SMM Panel')) — {{ config('app.name','SMM Panel') }}</title>
<script src="https://cdn.tailwindcss.com/3.4.17?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#060d1a">
<script>
tailwind.config={
  theme:{
    extend:{
      colors:{
        base:    '#060d1a',
        surface: '#0c1526',
        card:    '#111d35',
        border:  '#1e2d4a',
        muted:   '#8a9bc0',
        text:    '#dce8ff',
        primary: '#4f8ef7',
        'primary-light':'#a8c4ff',
        accent:  '#38d9a9',
        warn:    '#f7c948',
        danger:  '#f76f6f',
        purple:  '#a78bfa',
      },
      fontFamily:{sans:['Inter','sans-serif'],mono:['JetBrains Mono','monospace']},
    }
  }
}
</script>
<style>
*{box-sizing:border-box}
:root{
  --c-base:#060d1a;
  --c-surface:#0c1526;
  --c-card:#111d35;
  --c-border:#1e2d4a;
  --c-muted:#8a9bc0;
  --c-text:#dce8ff;
  --c-primary:#4f8ef7;
  --c-primary-l:#a8c4ff;
  --c-accent:#38d9a9;
  --c-warn:#f7c948;
  --c-danger:#f76f6f;
  --c-purple:#a78bfa;
  --sidebar-w:248px;
}
body{margin:0;background:var(--c-base);color:var(--c-text);font-family:'Inter',sans-serif;min-height:100vh;overflow-x:hidden}

/* Subtle grid background */
body::before{content:'';position:fixed;inset:0;z-index:0;pointer-events:none;
  background-image:linear-gradient(rgba(79,142,247,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(79,142,247,.035) 1px,transparent 1px);
  background-size:48px 48px}

/* Ambient orbs */
.orb-1{position:fixed;top:-120px;left:-80px;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(79,142,247,.12),transparent 70%);pointer-events:none;z-index:0}
.orb-2{position:fixed;bottom:-100px;right:-60px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(56,217,169,.08),transparent 70%);pointer-events:none;z-index:0}

/* Sidebar */
#sidebar{
  position:fixed;left:0;top:0;bottom:0;width:var(--sidebar-w);
  background:rgba(11,18,34,.92);
  border-right:1px solid var(--c-border);
  backdrop-filter:blur(20px);
  display:flex;flex-direction:column;z-index:100;
  transition:transform .28s cubic-bezier(.4,0,.2,1);
}
#sidebar.collapsed{transform:translateX(-100%)}

/* Scrollbar inside sidebar */
#sidebar-scroll{flex:1;overflow-y:auto;overflow-x:hidden;padding:8px 12px}
#sidebar-scroll::-webkit-scrollbar{width:3px}
#sidebar-scroll::-webkit-scrollbar-track{background:transparent}
#sidebar-scroll::-webkit-scrollbar-thumb{background:var(--c-border);border-radius:4px}

/* Nav links */
.nav-link{
  display:flex;align-items:center;gap:12px;padding:9px 12px;border-radius:10px;
  font-size:13.5px;font-weight:500;color:var(--c-muted);text-decoration:none;
  transition:all .18s;white-space:nowrap;position:relative;
}
.nav-link:hover{color:var(--c-text);background:rgba(255,255,255,.05)}
.nav-link.active{color:var(--c-primary-l);background:rgba(79,142,247,.12)}
.nav-link.active::before{
  content:'';position:absolute;left:0;top:20%;bottom:20%;width:3px;
  background:var(--c-primary);border-radius:0 3px 3px 0;
}
.nav-link .ms{font-size:18px;flex-shrink:0;transition:inherit}
.nav-link:hover .ms,.nav-link.active .ms{color:inherit}
.nav-section{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--c-border);padding:20px 12px 6px;margin-top:4px}

/* Main area */
#main{margin-left:var(--sidebar-w);min-height:100vh;display:flex;flex-direction:column;position:relative;z-index:1;transition:margin .28s cubic-bezier(.4,0,.2,1)}

/* Topbar */
#topbar{
  height:58px;display:flex;align-items:center;justify-content:space-between;
  padding:0 24px;background:rgba(6,13,26,.8);backdrop-filter:blur(20px);
  border-bottom:1px solid var(--c-border);position:sticky;top:0;z-index:50;
}

/* Cards */
.card{background:var(--c-card);border:1px solid var(--c-border);border-radius:14px}
.card-sm{background:var(--c-card);border:1px solid var(--c-border);border-radius:10px}

/* Buttons */
.btn-primary{
  display:inline-flex;align-items:center;gap:7px;padding:9px 18px;
  background:var(--c-primary);color:#fff;font-size:13px;font-weight:600;
  border-radius:9px;text-decoration:none;border:none;cursor:pointer;
  transition:all .18s;
}
.btn-primary:hover{background:#3d7de8;transform:translateY(-1px)}
.btn-ghost{
  display:inline-flex;align-items:center;gap:7px;padding:9px 18px;
  background:transparent;color:var(--c-text);font-size:13px;font-weight:600;
  border-radius:9px;text-decoration:none;border:1px solid var(--c-border);cursor:pointer;
  transition:all .18s;
}
.btn-ghost:hover{background:rgba(255,255,255,.05);border-color:rgba(79,142,247,.5)}

/* Inputs */
.inp{
  width:100%;background:rgba(255,255,255,.04);border:1px solid var(--c-border);
  color:var(--c-text);border-radius:9px;padding:10px 14px;font-size:13.5px;
  font-family:'Inter',sans-serif;outline:none;transition:border .18s,box-shadow .18s;
}
.inp:focus{border-color:var(--c-primary);box-shadow:0 0 0 3px rgba(79,142,247,.12)}
.inp::placeholder{color:var(--c-muted)}
select.inp{appearance:none;cursor:pointer}

/* Status chips */
.chip{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.04em}
.chip-green{background:rgba(56,217,169,.12);color:var(--c-accent);border:1px solid rgba(56,217,169,.25)}
.chip-blue{background:rgba(79,142,247,.12);color:var(--c-primary-l);border:1px solid rgba(79,142,247,.25)}
.chip-yellow{background:rgba(247,201,72,.12);color:var(--c-warn);border:1px solid rgba(247,201,72,.25)}
.chip-red{background:rgba(247,111,111,.12);color:var(--c-danger);border:1px solid rgba(247,111,111,.25)}
.chip-gray{background:rgba(138,155,192,.1);color:var(--c-muted);border:1px solid rgba(138,155,192,.2)}

/* Toast */
#toast-root{position:fixed;top:16px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none}
.toast{
  background:var(--c-card);border:1px solid var(--c-border);border-radius:11px;
  padding:11px 16px;font-size:13px;color:var(--c-text);
  display:flex;align-items:center;gap:10px;pointer-events:all;
  opacity:0;transform:translateX(16px);transition:all .25s cubic-bezier(.4,0,.2,1);
  min-width:260px;max-width:360px;backdrop-filter:blur(16px);
}
.toast.show{opacity:1;transform:none}
.toast-bar{width:3px;height:32px;border-radius:4px;flex-shrink:0}

/* Wallet card in sidebar */
.wallet-card{
  margin:12px;padding:14px 16px;border-radius:12px;
  background:linear-gradient(135deg,rgba(79,142,247,.15),rgba(56,217,169,.08));
  border:1px solid rgba(79,142,247,.2);
}

/* Mobile bottom nav */
#bottom-nav{
  display:none;position:fixed;bottom:0;left:0;right:0;height:60px;
  background:rgba(11,18,34,.96);border-top:1px solid var(--c-border);
  backdrop-filter:blur(20px);z-index:100;padding:0 8px;
  align-items:center;justify-content:space-around;
}
.bnav-btn{display:flex;flex-direction:column;align-items:center;gap:2px;padding:8px 16px;border-radius:10px;text-decoration:none;color:var(--c-muted);font-size:9.5px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;border:none;background:none;cursor:pointer;transition:color .15s}
.bnav-btn.active{color:var(--c-primary-l)}
.bnav-btn .ms{font-size:20px}

/* Overlay */
#overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99;backdrop-filter:blur(2px)}

/* Fade animations */
@keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp .35s ease both}
.fade-up-d1{animation-delay:.05s}.fade-up-d2{animation-delay:.1s}.fade-up-d3{animation-delay:.15s}.fade-up-d4{animation-delay:.2s}

/* Scrollbar global */
::-webkit-scrollbar{width:4px;height:4px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--c-border);border-radius:4px}

/* Mobile */
@media(max-width:768px){
  #sidebar{transform:translateX(-100%)}
  #sidebar.open{transform:none}
  #main{margin-left:0!important}
  #bottom-nav{display:flex}
  #main{padding-bottom:60px}
}
</style>
@yield('css')
</head>
<body>

<div class="orb-1"></div>
<div class="orb-2"></div>
<div id="overlay" onclick="closeSidebar()"></div>

{{-- ── SIDEBAR ─────────────────────────────────────────────────────────── --}}
<aside id="sidebar">
  {{-- Logo --}}
  <div style="padding:20px 16px 12px;border-bottom:1px solid var(--c-border);display:flex;align-items:center;gap:10px">
    <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#4f8ef7,#38d9a9);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:15px;color:#fff;flex-shrink:0">S</div>
    <div>
      <div style="font-weight:800;font-size:14px;color:var(--c-text);line-height:1.2">{{ config('app.name','SMM Panel') }}</div>
      <div style="font-size:10px;color:var(--c-muted);letter-spacing:.06em">Control Panel</div>
    </div>
  </div>

  <div id="sidebar-scroll">
    {{-- Wallet quick --}}
    <div class="wallet-card" style="margin-bottom:8px">
      <div style="font-size:10px;color:var(--c-muted);font-weight:600;letter-spacing:.06em;margin-bottom:4px">WALLET</div>
      <div style="font-size:20px;font-weight:800;color:var(--c-text)">${{ number_format(auth()->user()->funds ?? 0, 2) }}</div>
      <div style="font-size:11px;color:var(--c-muted);margin-bottom:10px">₨{{ number_format((auth()->user()->funds ?? 0) * session('usd_pkr_rate', 280), 0) }}</div>
      <a href="{{ route('funds.index') }}" class="btn-primary" style="width:100%;justify-content:center;font-size:12px;padding:7px">+ Add Funds</a>
    </div>

    {{-- Nav --}}
    <div class="nav-section">Main</div>
    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
      <span class="material-symbols-outlined ms" style="{{ request()->routeIs('dashboard') ? "font-variation-settings:'FILL' 1" : '' }}">home</span> Dashboard
    </a>
    <a href="{{ route('orders.create') }}" class="nav-link {{ request()->routeIs('orders.create') ? 'active' : '' }}">
      <span class="material-symbols-outlined ms">add_circle</span> New Order
    </a>
    <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.index') ? 'active' : '' }}">
      <span class="material-symbols-outlined ms" style="{{ request()->routeIs('orders.index') ? "font-variation-settings:'FILL' 1" : '' }}">receipt_long</span> My Orders
    </a>
    <a href="{{ route('services.index') }}" class="nav-link {{ request()->routeIs('services.*') ? 'active' : '' }}">
      <span class="material-symbols-outlined ms">storefront</span> Services
    </a>

    <div class="nav-section">Account</div>
    <a href="{{ route('funds.index') }}" class="nav-link {{ request()->routeIs('funds.*') ? 'active' : '' }}">
      <span class="material-symbols-outlined ms">account_balance_wallet</span> Add Funds
    </a>
    <a href="{{ route('analytics.index') }}" class="nav-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
      <span class="material-symbols-outlined ms">analytics</span> Analytics
    </a>
    <a href="{{ route('referral.index') }}" class="nav-link {{ request()->routeIs('referral.*') ? 'active' : '' }}">
      <span class="material-symbols-outlined ms">group_add</span> Referrals
    </a>
    <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
      <span class="material-symbols-outlined ms">support_agent</span> Support
    </a>

    @if(auth()->user()->is_admin ?? false)
    @php $pending = \App\Models\Transaction::where('status','pending')->where('type','deposit')->where('gateway','manual')->count(); @endphp
    <div class="nav-section">Admin</div>
    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
      <span class="material-symbols-outlined ms">shield</span> Command Center
    </a>
    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
      <span class="material-symbols-outlined ms">people</span> Users
    </a>
    <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
      <span class="material-symbols-outlined ms">list_alt</span> All Orders
    </a>
    <a href="{{ route('admin.transactions.index', ['status'=>'pending','type'=>'deposit']) }}" class="nav-link {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}" style="justify-content:space-between">
      <div style="display:flex;align-items:center;gap:12px">
        <span class="material-symbols-outlined ms">payments</span> Deposits
      </div>
      @if($pending > 0)<span style="background:var(--c-danger);color:#fff;font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px">{{ $pending }}</span>@endif
    </a>
    <a href="{{ route('admin.fund_accounts.index') }}" class="nav-link">
      <span class="material-symbols-outlined ms">account_balance</span> Payment Accounts
    </a>
    <div class="nav-section">AI Tools</div>
    <a href="/admin/ai/services"  class="nav-link"><span class="material-symbols-outlined ms">smart_toy</span> AI Services</a>
    <a href="/admin/ai/pricing"   class="nav-link"><span class="material-symbols-outlined ms">price_change</span> AI Pricing</a>
    <a href="/admin/ai/suppliers/health" class="nav-link"><span class="material-symbols-outlined ms">monitor_heart</span> Supplier Health</a>
    <a href="/admin/ai/quality/low" class="nav-link"><span class="material-symbols-outlined ms">grade</span> Quality Scores</a>
    @endif
  </div>

  {{-- User footer --}}
  <div style="padding:12px 14px;border-top:1px solid var(--c-border);display:flex;align-items:center;gap:10px">
    <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,var(--c-primary),var(--c-accent));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;flex-shrink:0">
      {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
    </div>
    <div style="flex:1;min-width:0">
      <div style="font-size:13px;font-weight:600;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ auth()->user()->name ?? 'User' }}</div>
      <div style="font-size:11px;color:var(--c-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ auth()->user()->email ?? '' }}</div>
    </div>
    <form method="POST" action="{{ route('logout') }}" style="flex-shrink:0">
      @csrf
      <button type="submit" title="Logout" style="background:none;border:none;color:var(--c-muted);cursor:pointer;padding:4px;display:flex;border-radius:7px;transition:color .15s" onmouseover="this.style.color='var(--c-danger)'" onmouseout="this.style.color='var(--c-muted)'">
        <span class="material-symbols-outlined" style="font-size:18px">logout</span>
      </button>
    </form>
  </div>
</aside>

{{-- ── MAIN ────────────────────────────────────────────────────────────── --}}
<div id="main">

  {{-- Topbar --}}
  <header id="topbar">
    <div style="display:flex;align-items:center;gap:12px">
      <button onclick="toggleSidebar()" style="background:none;border:none;color:var(--c-muted);cursor:pointer;padding:6px;border-radius:8px;display:flex;transition:all .15s" onmouseover="this.style.background='rgba(255,255,255,.06)'" onmouseout="this.style.background='none'">
        <span class="material-symbols-outlined" style="font-size:22px">menu</span>
      </button>
      <h1 style="font-size:15px;font-weight:600;color:var(--c-text)">@yield('page-title','Dashboard')</h1>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
      <div style="font-size:12px;color:var(--c-muted);background:rgba(255,255,255,.04);border:1px solid var(--c-border);padding:5px 12px;border-radius:8px;font-family:'JetBrains Mono',monospace">
        ₨{{ number_format(session('usd_pkr_rate', 280), 1) }}/$
      </div>
      <a href="{{ route('tickets.index') }}" style="width:34px;height:34px;border-radius:9px;border:1px solid var(--c-border);background:rgba(255,255,255,.03);display:flex;align-items:center;justify-content:center;color:var(--c-muted);text-decoration:none;transition:all .15s" onmouseover="this.style.borderColor='var(--c-primary)';this.style.color='var(--c-primary-l)'" onmouseout="this.style.borderColor='var(--c-border)';this.style.color='var(--c-muted)'">
        <span class="material-symbols-outlined" style="font-size:18px">notifications</span>
      </a>
      <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,var(--c-primary),var(--c-accent));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff">
        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
      </div>
    </div>
  </header>

  {{-- Flash messages --}}
  @if(session('success') || session('error') || $errors->any())
  <div style="padding:16px 24px 0">
    @if(session('success'))
    <div style="display:flex;align-items:center;gap:10px;background:rgba(56,217,169,.08);border:1px solid rgba(56,217,169,.2);border-radius:10px;padding:11px 16px;color:var(--c-accent);font-size:13px;margin-bottom:10px">
      <span class="material-symbols-outlined" style="font-size:16px;font-variation-settings:'FILL' 1">check_circle</span> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="display:flex;align-items:center;gap:10px;background:rgba(247,111,111,.08);border:1px solid rgba(247,111,111,.2);border-radius:10px;padding:11px 16px;color:var(--c-danger);font-size:13px;margin-bottom:10px">
      <span class="material-symbols-outlined" style="font-size:16px;font-variation-settings:'FILL' 1">error</span> {{ session('error') }}
    </div>
    @endif
    @if($errors->any())
    <div style="background:rgba(247,111,111,.08);border:1px solid rgba(247,111,111,.2);border-radius:10px;padding:11px 16px;color:var(--c-danger);font-size:13px;margin-bottom:10px">
      @foreach($errors->all() as $e)
      <div style="display:flex;align-items:center;gap:6px"><span class="material-symbols-outlined" style="font-size:14px">cancel</span>{{ $e }}</div>
      @endforeach
    </div>
    @endif
  </div>
  @endif

  {{-- Page content --}}
  <div style="padding:24px;flex:1;max-width:1400px;width:100%">
    @yield('content')
  </div>

  {{-- Footer --}}
  <footer style="padding:16px 24px;border-top:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <p style="font-size:12px;color:var(--c-muted)">© {{ date('Y') }} {{ config('app.name','SMM Panel') }}</p>
    <div style="display:flex;gap:16px">
      @foreach(['Terms'=>'#','Privacy'=>'#','Status'=>'#','Support'=>route('tickets.create')] as $label=>$href)
      <a href="{{ $href }}" style="font-size:12px;color:var(--c-muted);text-decoration:none;transition:color .15s" onmouseover="this.style.color='var(--c-primary-l)'" onmouseout="this.style.color='var(--c-muted)'">{{ $label }}</a>
      @endforeach
    </div>
  </footer>
</div>

{{-- ── MOBILE BOTTOM NAV ───────────────────────────────────────────────── --}}
<nav id="bottom-nav">
  <a href="{{ route('dashboard') }}" class="bnav-btn {{ request()->routeIs('dashboard')?'active':'' }}">
    <span class="material-symbols-outlined ms" style="{{ request()->routeIs('dashboard')?"font-variation-settings:'FILL' 1":'' }}">home</span>Home
  </a>
  <a href="{{ route('orders.create') }}" class="bnav-btn {{ request()->routeIs('orders.create')?'active':'' }}">
    <span class="material-symbols-outlined ms">add_circle</span>Order
  </a>
  <a href="{{ route('orders.index') }}" class="bnav-btn {{ request()->routeIs('orders.index')?'active':'' }}">
    <span class="material-symbols-outlined ms">receipt_long</span>Orders
  </a>
  <a href="{{ route('funds.index') }}" class="bnav-btn {{ request()->routeIs('funds.*')?'active':'' }}">
    <span class="material-symbols-outlined ms">account_balance_wallet</span>Wallet
  </a>
  <button onclick="toggleSidebar()" class="bnav-btn">
    <span class="material-symbols-outlined ms">menu</span>More
  </button>
</nav>

{{-- Toast container --}}
<div id="toast-root"></div>

<script>
function toggleSidebar(){
  const s=document.getElementById('sidebar');
  const o=document.getElementById('overlay');
  const isOpen=s.classList.contains('open');
  if(window.innerWidth<768){
    s.classList.toggle('open');
    o.style.display=isOpen?'none':'block';
  } else {
    s.classList.toggle('collapsed');
    const m=document.getElementById('main');
    m.style.marginLeft=s.classList.contains('collapsed')?'0':'var(--sidebar-w)';
  }
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('overlay').style.display='none';
}
function showToast(msg,type='info'){
  const colors={success:'var(--c-accent)',danger:'var(--c-danger)',info:'var(--c-primary-l)',warning:'var(--c-warn)'};
  const icons={success:'check_circle',danger:'cancel',info:'info',warning:'warning'};
  const t=document.createElement('div');
  t.className='toast';
  t.innerHTML=`<div class="toast-bar" style="background:${colors[type]}"></div><span class="material-symbols-outlined" style="color:${colors[type]};font-size:17px;font-variation-settings:'FILL' 1;flex-shrink:0">${icons[type]}</span><span style="flex:1">${msg}</span><button onclick="this.parentElement.remove()" style="background:none;border:none;color:var(--c-muted);cursor:pointer;padding:0;font-size:16px;line-height:1;flex-shrink:0">×</button>`;
  document.getElementById('toast-root').appendChild(t);
  requestAnimationFrame(()=>t.classList.add('show'));
  setTimeout(()=>{t.classList.remove('show');setTimeout(()=>t.remove(),280)},4500);
}
@if(session('success')) showToast("{{ addslashes(session('success')) }}",'success'); @endif
@if(session('error'))   showToast("{{ addslashes(session('error')) }}",'danger');  @endif
</script>
@yield('scripts')
</body>
</html>
