<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Sign In — {{ config('app.name','SMM Panel') }}</title>
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
*{box-sizing:border-box}
body{margin:0;min-height:100vh;background:#060d1a;font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;padding:16px}
.orb{position:fixed;border-radius:50%;pointer-events:none}
.inp{width:100%;background:rgba(255,255,255,.04);border:1.5px solid rgba(30,45,74,1);color:#dce8ff;border-radius:10px;padding:12px 14px;font-size:14px;font-family:'Inter',sans-serif;outline:none;transition:border-color .18s,box-shadow .18s}
.inp:focus{border-color:#4f8ef7;box-shadow:0 0 0 3px rgba(79,142,247,.12)}
.inp::placeholder{color:#4a5a78}
.card{background:rgba(17,29,53,.85);border:1px solid rgba(30,45,74,.9);border-radius:18px;backdrop-filter:blur(24px);box-shadow:0 24px 64px rgba(0,0,0,.5)}
@keyframes up{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.up{animation:up .45s cubic-bezier(.4,0,.2,1) both}
label{display:block;font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#4a6a9a;margin-bottom:7px}
.btn-main{width:100%;background:linear-gradient(135deg,#4f8ef7,#38d9a9);color:#fff;border:none;border-radius:10px;padding:13px;font-size:14px;font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;gap:8px;transition:filter .18s,transform .18s;box-shadow:0 0 24px rgba(79,142,247,.25)}
.btn-main:hover{filter:brightness(1.08);transform:translateY(-1px)}
.divider{display:flex;align-items:center;gap:12px;color:#2a3a58;font-size:12px;margin:18px 0}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#1e2d4a}
</style>
</head>
<body>

{{-- Ambient background --}}
<div class="orb" style="top:-10%;left:-8%;width:500px;height:500px;background:radial-gradient(circle,rgba(79,142,247,.12),transparent 65%);filter:blur(40px)"></div>
<div class="orb" style="bottom:-8%;right:-6%;width:420px;height:420px;background:radial-gradient(circle,rgba(56,217,169,.09),transparent 65%);filter:blur(40px)"></div>
<div style="position:fixed;inset:0;pointer-events:none;background-image:linear-gradient(rgba(79,142,247,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(79,142,247,.025) 1px,transparent 1px);background-size:44px 44px"></div>

<div style="width:100%;max-width:420px;position:relative;z-index:1" class="up">

  {{-- Logo --}}
  <div style="text-align:center;margin-bottom:28px">
    <div style="display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#4f8ef7,#38d9a9);margin-bottom:14px;box-shadow:0 0 24px rgba(79,142,247,.3)">
      <span style="font-weight:900;font-size:22px;color:#fff">S</span>
    </div>
    <h1 style="font-size:26px;font-weight:900;color:#dce8ff;letter-spacing:-.03em;margin:0 0 4px">{{ config('app.name','SMM Panel') }}</h1>
    <p style="font-size:12px;color:#4a6a9a;text-transform:uppercase;letter-spacing:.1em;margin:0">Elite Control Panel</p>
  </div>

  <div class="card" style="padding:28px">

    @if($errors->any())
    <div style="background:rgba(247,111,111,.08);border:1px solid rgba(247,111,111,.2);border-radius:10px;padding:11px 14px;margin-bottom:18px">
      @foreach($errors->all() as $e)
      <div style="display:flex;align-items:center;gap:7px;font-size:13px;color:#f76f6f">
        <span class="material-symbols-outlined" style="font-size:15px;font-variation-settings:'FILL' 1">cancel</span>{{ $e }}
      </div>
      @endforeach
    </div>
    @endif

    @if(session('status'))
    <div style="background:rgba(56,217,169,.08);border:1px solid rgba(56,217,169,.2);border-radius:10px;padding:11px 14px;margin-bottom:18px;font-size:13px;color:#38d9a9;display:flex;align-items:center;gap:7px">
      <span class="material-symbols-outlined" style="font-size:15px;font-variation-settings:'FILL' 1">check_circle</span>{{ session('status') }}
    </div>
    @endif

    <form method="POST" action="{{ route('login') }}" style="display:flex;flex-direction:column;gap:16px">
      @csrf
      <div>
        <label>Email Address</label>
        <input type="email" name="email" class="inp" value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
      </div>
      <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px">
          <label style="margin:0">Password</label>
          <a href="{{ route('password.request') }}" style="font-size:12px;color:#4f8ef7;text-decoration:none;font-weight:600">Forgot?</a>
        </div>
        <div style="position:relative">
          <input type="password" name="password" id="pwd" class="inp" placeholder="••••••••" required style="padding-right:42px">
          <button type="button" onclick="togglePwd()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#4a6a9a;padding:0;display:flex">
            <span class="material-symbols-outlined" id="eye-icon" style="font-size:18px">visibility</span>
          </button>
        </div>
      </div>
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;text-transform:none;letter-spacing:0;font-size:13px;color:#8a9bc0;margin:0;font-weight:400">
        <input type="checkbox" name="remember" style="accent-color:#4f8ef7;width:15px;height:15px"> Remember me
      </label>
      <button type="submit" class="btn-main">
        <span class="material-symbols-outlined" style="font-size:18px;font-variation-settings:'FILL' 1">login</span> Sign In
      </button>
    </form>

    <div class="divider">or</div>
    <div style="text-align:center;font-size:13.5px;color:#4a6a9a">
      No account yet?
      <a href="{{ route('register') }}" style="color:#4f8ef7;font-weight:700;text-decoration:none;margin-left:4px">Create one free →</a>
    </div>
  </div>

  <p style="text-align:center;font-size:11px;color:#1e2d4a;margin-top:20px">© {{ date('Y') }} {{ config('app.name','SMM Panel') }}</p>
</div>

<script>
function togglePwd(){
  const i=document.getElementById('pwd');
  const e=document.getElementById('eye-icon');
  if(i.type==='password'){i.type='text';e.textContent='visibility_off';}
  else{i.type='password';e.textContent='visibility';}
}
</script>
</body>
</html>
