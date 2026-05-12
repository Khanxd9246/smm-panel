<!DOCTYPE html>
<html lang="en" data-theme="dark">
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
<style>
*{box-sizing:border-box}

/* DARK THEME (default) */
:root,[data-theme="dark"]{
  --c-base:#060d1a;--c-surface:#0c1526;--c-card:#111d35;--c-card-hover:#162240;
  --c-border:#1e2d4a;--c-border-hover:#2d4070;--c-muted:#8a9bc0;--c-muted-light:#6b7fa8;
  --c-text:#dce8ff;--c-text-secondary:#b0c4e8;--c-primary:#4f8ef7;--c-primary-l:#a8c4ff;
  --c-primary-hover:#3d7de8;--c-accent:#38d9a9;--c-warn:#f7c948;--c-danger:#f76f6f;
  --c-purple:#a78bfa;--c-sidebar-bg:rgba(11,18,34,.92);--c-topbar-bg:rgba(6,13,26,.8);
  --c-overlay-bg:rgba(0,0,0,.6);--c-input-bg:rgba(255,255,255,.04);
  --c-input-focus-shadow:rgba(79,142,247,.12);--c-nav-hover:rgba(255,255,255,.05);
  --c-nav-active:rgba(79,142,247,.12);--c-btn-ghost-hover:rgba(255,255,255,.05);
  --c-grid-line:rgba(79,142,247,.035);--c-orb1:rgba(79,142,247,.12);--c-orb2:rgba(56,217,169,.08);
  --c-wallet-bg:linear-gradient(135deg,rgba(79,142,247,.15),rgba(56,217,169,.08));
  --c-wallet-border:rgba(79,142,247,.2);--c-success-bg:rgba(56,217,169,.08);
  --c-success-border:rgba(56,217,169,.2);--c-error-bg:rgba(247,111,111,.08);
  --c-error-border:rgba(247,111,111,.2);--c-chip-green-bg:rgba(56,217,169,.12);
  --c-chip-blue-bg:rgba(79,142,247,.12);--c-chip-yellow-bg:rgba(247,201,72,.12);
  --c-chip-red-bg:rgba(247,111,111,.12);--c-chip-gray-bg:rgba(138,155,192,.1);
  --c-scrollbar:#1e2d4a;--sidebar-w:248px;--c-logo-shadow:rgba(79,142,247,.3);
}

/* LIGHT THEME */
[data-theme="light"]{
  --c-base:#f0f4ff;--c-surface:#e6ecf8;--c-card:#ffffff;--c-card-hover:#f5f8ff;
  --c-border:#d0d9ef;--c-border-hover:#a8b9d8;--c-muted:#5a6d90;--c-muted-light:#7a8fae;
  --c-text:#0f1d38;--c-text-secondary:#2d4170;--c-primary:#2563eb;--c-primary-l:#1d4ed8;
  --c-primary-hover:#1a56d6;--c-accent:#059669;--c-warn:#b45309;--c-danger:#dc2626;
  --c-purple:#7c3aed;--c-sidebar-bg:rgba(240,244,255,.97);--c-topbar-bg:rgba(240,244,255,.93);
  --c-overlay-bg:rgba(0,0,0,.4);--c-input-bg:rgba(0,0,0,.03);
  --c-input-focus-shadow:rgba(37,99,235,.12);--c-nav-hover:rgba(0,0,0,.05);
  --c-nav-active:rgba(37,99,235,.08);--c-btn-ghost-hover:rgba(0,0,0,.05);
  --c-grid-line:rgba(37,99,235,.04);--c-orb1:rgba(37,99,235,.06);--c-orb2:rgba(5,150,105,.04);
  --c-wallet-bg:linear-gradient(135deg,rgba(37,99,235,.08),rgba(5,150,105,.05));
  --c-wallet-border:rgba(37,99,235,.2);--c-success-bg:rgba(5,150,105,.06);
  --c-success-border:rgba(5,150,105,.2);--c-error-bg:rgba(220,38,38,.06);
  --c-error-border:rgba(220,38,38,.2);--c-chip-green-bg:rgba(5,150,105,.08);
  --c-chip-blue-bg:rgba(37,99,235,.08);--c-chip-yellow-bg:rgba(180,83,9,.08);
  --c-chip-red-bg:rgba(220,38,38,.08);--c-chip-gray-bg:rgba(90,109,144,.08);
  --c-scrollbar:#d0d9ef;--c-logo-shadow:rgba(37,99,235,.2);
}

body{margin:0;background:var(--c-base);color:var(--c-text);font-family:'Inter',sans-serif;min-height:100vh;overflow-x:hidden;transition:background .3s,color .3s}
body::before{content:'';position:fixed;inset:0;z-index:0;pointer-events:none;background-image:linear-gradient(var(--c-grid-line) 1px,transparent 1px),linear-gradient(90deg,var(--c-grid-line) 1px,transparent 1px);background-size:48px 48px}
.orb-1{position:fixed;top:-120px;left:-80px;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,var(--c-orb1),transparent 70%);pointer-events:none;z-index:0;transition:background .4s}
.orb-2{position:fixed;bottom:-100px;right:-60px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,var(--c-orb2),transparent 70%);pointer-events:none;z-index:0;transition:background .4s}

/* SIDEBAR */
#sidebar{position:fixed;left:0;top:0;bottom:0;width:var(--sidebar-w);background:var(--c-sidebar-bg);border-right:1px solid var(--c-border);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);display:flex;flex-direction:column;z-index:100;transition:transform .28s cubic-bezier(.4,0,.2,1),background .3s,border-color .3s}
#sidebar.collapsed{transform:translateX(-100%)}
#sidebar-scroll{flex:1;overflow-y:auto;overflow-x:hidden;padding:8px 12px}
#sidebar-scroll::-webkit-scrollbar{width:3px}#sidebar-scroll::-webkit-scrollbar-track{background:transparent}#sidebar-scroll::-webkit-scrollbar-thumb{background:var(--c-scrollbar);border-radius:4px}

/* NAV */
.nav-link{display:flex;align-items:center;gap:12px;padding:9px 12px;border-radius:10px;font-size:13.5px;font-weight:500;color:var(--c-muted);text-decoration:none;transition:all .18s;white-space:nowrap;position:relative}
.nav-link:hover{color:var(--c-text);background:var(--c-nav-hover)}
.nav-link.active{color:var(--c-primary);background:var(--c-nav-active)}
.nav-link.active::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:3px;background:var(--c-primary);border-radius:0 3px 3px 0}
.nav-link .ms{font-size:18px;flex-shrink:0;transition:inherit}
.nav-section{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--c-muted);padding:20px 12px 6px;margin-top:4px;opacity:.7}

/* MAIN */
#main{margin-left:var(--sidebar-w);min-height:100vh;display:flex;flex-direction:column;position:relative;z-index:1;transition:margin .28s cubic-bezier(.4,0,.2,1)}

/* TOPBAR */
#topbar{height:58px;display:flex;align-items:center;justify-content:space-between;padding:0 24px;background:var(--c-topbar-bg);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid var(--c-border);position:sticky;top:0;z-index:50;transition:background .3s,border-color .3s}

/* CARDS */
.card{background:var(--c-card);border:1px solid var(--c-border);border-radius:14px;transition:background .3s,border-color .3s}
.card-sm{background:var(--c-card);border:1px solid var(--c-border);border-radius:10px;transition:background .3s,border-color .3s}

/* BUTTONS */
.btn-primary{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;background:var(--c-primary);color:#fff;font-size:13px;font-weight:600;border-radius:9px;text-decoration:none;border:none;cursor:pointer;transition:all .18s}
.btn-primary:hover{background:var(--c-primary-hover);transform:translateY(-1px)}
.btn-ghost{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;background:transparent;color:var(--c-text);font-size:13px;font-weight:600;border-radius:9px;text-decoration:none;border:1px solid var(--c-border);cursor:pointer;transition:all .18s}
.btn-ghost:hover{background:var(--c-btn-ghost-hover);border-color:var(--c-primary)}

/* INPUTS */
.inp{width:100%;background:var(--c-input-bg);border:1px solid var(--c-border);color:var(--c-text);border-radius:9px;padding:10px 14px;font-size:13.5px;font-family:'Inter',sans-serif;outline:none;transition:border .18s,box-shadow .18s,background .3s,color .3s}
.inp:focus{border-color:var(--c-primary);box-shadow:0 0 0 3px var(--c-input-focus-shadow)}
.inp::placeholder{color:var(--c-muted)}
select.inp{appearance:none;cursor:pointer}

/* CHIPS */
.chip{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.04em}
.chip-green{background:var(--c-chip-green-bg);color:var(--c-accent);border:1px solid color-mix(in srgb,var(--c-accent) 35%,transparent)}
.chip-blue{background:var(--c-chip-blue-bg);color:var(--c-primary);border:1px solid color-mix(in srgb,var(--c-primary) 35%,transparent)}
.chip-yellow{background:var(--c-chip-yellow-bg);color:var(--c-warn);border:1px solid color-mix(in srgb,var(--c-warn) 35%,transparent)}
.chip-red{background:var(--c-chip-red-bg);color:var(--c-danger);border:1px solid color-mix(in srgb,var(--c-danger) 35%,transparent)}
.chip-gray{background:var(--c-chip-gray-bg);color:var(--c-muted);border:1px solid color-mix(in srgb,var(--c-muted) 30%,transparent)}

/* TOAST */
#toast-root{position:fixed;top:16px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none}
.toast{background:var(--c-card);border:1px solid var(--c-border);border-radius:11px;padding:11px 16px;font-size:13px;color:var(--c-text);display:flex;align-items:center;gap:10px;pointer-events:all;opacity:0;transform:translateX(16px);transition:all .25s cubic-bezier(.4,0,.2,1);min-width:260px;max-width:360px;backdrop-filter:blur(16px);box-shadow:0 4px 24px rgba(0,0,0,.12)}
.toast.show{opacity:1;transform:none}
.toast-bar{width:3px;height:32px;border-radius:4px;flex-shrink:0}

/* WALLET */
.wallet-card{margin:12px;padding:14px 16px;border-radius:12px;background:var(--c-wallet-bg);border:1px solid var(--c-wallet-border);transition:background .3s,border-color .3s}

/* FLASH */
.flash-success{display:flex;align-items:center;gap:10px;background:var(--c-success-bg);border:1px solid var(--c-success-border);border-radius:10px;padding:11px 16px;color:var(--c-accent);font-size:13px;margin-bottom:10px}
.flash-error{display:flex;align-items:center;gap:10px;background:var(--c-error-bg);border:1px solid var(--c-error-border);border-radius:10px;padding:11px 16px;color:var(--c-danger);font-size:13px;margin-bottom:10px}

/* THEME TOGGLE */
.theme-toggle{position:relative;display:flex;align-items:center;width:52px;height:28px;cursor:pointer;flex-shrink:0;user-select:none}
.theme-toggle input{opacity:0;width:0;height:0;position:absolute}
.theme-track{position:absolute;inset:0;background:var(--c-card);border:1.5px solid var(--c-border);border-radius:999px;transition:all .3s cubic-bezier(.4,0,.2,1);display:flex;align-items:center;justify-content:space-between;padding:0 6px}
.theme-track .t-sun,.theme-track .t-moon{font-size:11px;line-height:1;font-style:normal;pointer-events:none;transition:opacity .3s}
.theme-track .t-sun{opacity:0}.theme-track .t-moon{opacity:.6}
[data-theme="light"] .theme-track{background:rgba(37,99,235,.06);border-color:rgba(37,99,235,.3)}
[data-theme="light"] .theme-track .t-sun{opacity:.8}
[data-theme="light"] .theme-track .t-moon{opacity:0}
.theme-thumb{position:absolute;width:20px;height:20px;background:var(--c-primary);border-radius:50%;top:3px;left:4px;transition:transform .3s cubic-bezier(.4,0,.2,1),background .3s;box-shadow:0 2px 8px rgba(0,0,0,.25);display:flex;align-items:center;justify-content:center}
[data-theme="light"] .theme-thumb{transform:translateX(24px)}
.theme-thumb .ms{font-size:11px;color:#fff;font-variation-settings:'FILL' 1}

/* TOPBAR ICON BTN */
.topbar-icon-btn{width:34px;height:34px;border-radius:9px;border:1px solid var(--c-border);background:var(--c-input-bg);display:flex;align-items:center;justify-content:center;color:var(--c-muted);text-decoration:none;cursor:pointer;transition:all .15s;flex-shrink:0}
.topbar-icon-btn:hover{border-color:var(--c-primary);color:var(--c-primary)}

/* RATE BADGE */
.rate-badge{font-size:12px;color:var(--c-muted);background:var(--c-input-bg);border:1px solid var(--c-border);padding:5px 12px;border-radius:8px;font-family:'JetBrains Mono',monospace;transition:background .3s,border-color .3s,color .3s}

/* MOBILE */
#bottom-nav{display:none;position:fixed;bottom:0;left:0;right:0;height:60px;background:var(--c-sidebar-bg);border-top:1px solid var(--c-border);backdrop-filter:blur(20px);z-index:100;padding:0 8px;align-items:center;justify-content:space-around;transition:background .3s,border-color .3s}
.bnav-btn{display:flex;flex-direction:column;align-items:center;gap:2px;padding:8px 16px;border-radius:10px;text-decoration:none;color:var(--c-muted);font-size:9.5px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;border:none;background:none;cursor:pointer;transition:color .15s}
.bnav-btn.active{color:var(--c-primary)}
.bnav-btn .ms{font-size:20px}
#overlay{display:none;position:fixed;inset:0;background:var(--c-overlay-bg);z-index:99;backdrop-filter:blur(2px)}

/* BRAND */
.brand-logo{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--c-primary),var(--c-accent));display:flex;align-items:center;justify-content:center;font-weight:900;font-size:15px;color:#fff;flex-shrink:0;box-shadow:0 2px 12px var(--c-logo-shadow)}
.brand-name{font-weight:800;font-size:14px;color:var(--c-text);line-height:1.2;transition:color .3s}
.brand-sub{font-size:10px;color:var(--c-muted);letter-spacing:.06em;transition:color .3s}

/* ANIMATIONS */
@keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp .35s ease both}
.fade-up-d1{animation-delay:.05s}.fade-up-d2{animation-delay:.1s}.fade-up-d3{animation-delay:.15s}.fade-up-d4{animation-delay:.2s}
@keyframes themeSwitch{0%,100%{opacity:1}50%{opacity:.85}}
body.theme-switching{animation:themeSwitch .3s ease}

/* SCROLLBAR */
::-webkit-scrollbar{width:4px;height:4px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:var(--c-scrollbar);border-radius:4px}

/* LIGHT MODE TEXT SAFETY — prevent white-on-white */
[data-theme="light"] table,
[data-theme="light"] td,
[data-theme="light"] th{color:var(--c-text)}
[data-theme="light"] .text-white:not(.btn-primary){color:var(--c-text)!important}
[data-theme="light"] .text-gray-400{color:var(--c-muted)!important}
[data-theme="light"] .text-gray-300{color:var(--c-text-secondary)!important}
[data-theme="light"] .bg-white\/5{background:rgba(0,0,0,.04)!important}
[data-theme="light"] .bg-white\/10{background:rgba(0,0,0,.07)!important}

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

<aside id="sidebar">
  <div style="padding:20px 16px 12px;border-bottom:1px solid var(--c-border);display:flex;align-items:center;gap:10px">
    <div class="brand-logo">S</div>
    <div>
      <div class="brand-name">{{ config('app.name','SMM Panel') }}</div>
      <div class="brand-sub">Control Panel</div>
    </div>
  </div>

  <div id="sidebar-scroll">
    <div class="wallet-card" style="margin-bottom:8px">
      <div style="font-size:10px;color:var(--c-muted);font-weight:600;letter-spacing:.06em;margin-bottom:4px">WALLET</div>
      <div style="font-size:20px;font-weight:800;color:var(--c-text)">${{ number_format(auth()->user()->funds ?? 0, 2) }}</div>
      <div style="font-size:11px;color:var(--c-muted);margin-bottom:10px">₨{{ number_format((auth()->user()->funds ?? 0) * session('usd_pkr_rate', 280), 0) }}</div>
      <a href="{{ route('funds.index') }}" class="btn-primary" style="width:100%;justify-content:center;font-size:12px;padding:7px">+ Add Funds</a>
    </div>

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
    <a href="/admin/ai/services" class="nav-link"><span class="material-symbols-outlined ms">smart_toy</span> AI Services</a>
    <a href="/admin/ai/pricing" class="nav-link"><span class="material-symbols-outlined ms">price_change</span> AI Pricing</a>
    <a href="/admin/ai/suppliers/health" class="nav-link"><span class="material-symbols-outlined ms">monitor_heart</span> Supplier Health</a>
    <a href="/admin/ai/quality/low" class="nav-link"><span class="material-symbols-outlined ms">grade</span> Quality Scores</a>
    @endif
  </div>

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

<div id="main">
  <header id="topbar">
    <div style="display:flex;align-items:center;gap:12px">
      <button onclick="toggleSidebar()" class="topbar-icon-btn" title="Toggle sidebar">
        <span class="material-symbols-outlined" style="font-size:22px">menu</span>
      </button>
      <h1 style="font-size:15px;font-weight:600;color:var(--c-text)">@yield('page-title','Dashboard')</h1>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
      <div class="rate-badge">₨{{ number_format(session('usd_pkr_rate', 280), 1) }}/$</div>

      <!-- THEME TOGGLE SWITCH -->
      <label class="theme-toggle" title="Toggle light / dark mode" aria-label="Toggle theme">
        <input type="checkbox" id="themeCheckbox" onchange="toggleTheme(this)">
        <div class="theme-track">
          <em class="t-sun">☀️</em>
          <em class="t-moon">🌙</em>
        </div>
        <div class="theme-thumb">
          <span class="material-symbols-outlined ms" id="themeIcon">dark_mode</span>
        </div>
      </label>

      <a href="{{ route('tickets.index') }}" class="topbar-icon-btn" style="color:var(--c-muted)">
        <span class="material-symbols-outlined" style="font-size:18px">notifications</span>
      </a>
      <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,var(--c-primary),var(--c-accent));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff">
        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
      </div>
    </div>
  </header>

  @if(session('success') || session('error') || $errors->any())
  <div style="padding:16px 24px 0">
    @if(session('success'))
    <div class="flash-success">
      <span class="material-symbols-outlined" style="font-size:16px;font-variation-settings:'FILL' 1">check_circle</span> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="flash-error">
      <span class="material-symbols-outlined" style="font-size:16px;font-variation-settings:'FILL' 1">error</span> {{ session('error') }}
    </div>
    @endif
    @if($errors->any())
    <div class="flash-error" style="flex-direction:column;align-items:flex-start;gap:4px">
      @foreach($errors->all() as $e)
      <div style="display:flex;align-items:center;gap:6px"><span class="material-symbols-outlined" style="font-size:14px">cancel</span>{{ $e }}</div>
      @endforeach
    </div>
    @endif
  </div>
  @endif

  <div style="padding:24px;flex:1;max-width:1400px;width:100%">
    @yield('content')
  </div>

  <footer style="padding:16px 24px;border-top:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <p style="font-size:12px;color:var(--c-muted)">© {{ date('Y') }} {{ config('app.name','SMM Panel') }}</p>
    <div style="display:flex;gap:16px">
      @foreach(['Terms'=>'#','Privacy'=>'#','Status'=>'#','Support'=>route('tickets.create')] as $label=>$href)
      <a href="{{ $href }}" style="font-size:12px;color:var(--c-muted);text-decoration:none;transition:color .15s" onmouseover="this.style.color='var(--c-primary)'" onmouseout="this.style.color='var(--c-muted)'">{{ $label }}</a>
      @endforeach
    </div>
  </footer>
</div>

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

<div id="toast-root"></div>

<script>
/* THEME SYSTEM */
(function(){
  var saved=localStorage.getItem('smm-theme')||'dark';
  applyTheme(saved,false);
})();

function applyTheme(theme,animate){
  var html=document.documentElement;
  var icon=document.getElementById('themeIcon');
  var cb=document.getElementById('themeCheckbox');
  if(animate){
    document.body.classList.add('theme-switching');
    setTimeout(function(){document.body.classList.remove('theme-switching')},320);
  }
  html.setAttribute('data-theme',theme);
  localStorage.setItem('smm-theme',theme);
  if(cb)cb.checked=(theme==='light');
  if(icon)icon.textContent=theme==='light'?'light_mode':'dark_mode';
}

function toggleTheme(cb){
  applyTheme(cb.checked?'light':'dark',true);
}

/* SIDEBAR */
function toggleSidebar(){
  var s=document.getElementById('sidebar');
  var o=document.getElementById('overlay');
  var isOpen=s.classList.contains('open');
  if(window.innerWidth<768){
    s.classList.toggle('open');
    o.style.display=isOpen?'none':'block';
  } else {
    s.classList.toggle('collapsed');
    var m=document.getElementById('main');
    m.style.marginLeft=s.classList.contains('collapsed')?'0':'var(--sidebar-w)';
  }
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('overlay').style.display='none';
}

/* TOAST */
function showToast(msg,type){
  type=type||'info';
  var colors={success:'var(--c-accent)',danger:'var(--c-danger)',info:'var(--c-primary)',warning:'var(--c-warn)'};
  var icons={success:'check_circle',danger:'cancel',info:'info',warning:'warning'};
  var t=document.createElement('div');
  t.className='toast';
  t.innerHTML='<div class="toast-bar" style="background:'+colors[type]+'"></div><span class="material-symbols-outlined" style="color:'+colors[type]+';font-size:17px;font-variation-settings:\'FILL\' 1;flex-shrink:0">'+icons[type]+'</span><span style="flex:1">'+msg+'</span><button onclick="this.parentElement.remove()" style="background:none;border:none;color:var(--c-muted);cursor:pointer;padding:0;font-size:16px;line-height:1;flex-shrink:0">&times;</button>';
  document.getElementById('toast-root').appendChild(t);
  requestAnimationFrame(function(){t.classList.add('show')});
  setTimeout(function(){t.classList.remove('show');setTimeout(function(){t.remove()},280)},4500);
}
@if(session('success')) showToast("{{ addslashes(session('success')) }}",'success'); @endif
@if(session('error'))   showToast("{{ addslashes(session('error')) }}",'danger');  @endif
</script>
@yield('scripts')
</body>
</html>
