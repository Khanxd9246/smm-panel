<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Create Account — {{ config('app.name','SMM Panel') }}</title>
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
.strength-bar{height:3px;border-radius:2px;background:#1e2d4a;overflow:hidden;margin-top:6px}
.strength-fill{height:100%;border-radius:2px;transition:width .3s,background .3s;width:0%}
</style>
</head>
<body>

<div class="orb" style="top:-8%;right:-8%;width:480px;height:480px;background:radial-gradient(circle,rgba(167,139,250,.1),transparent 65%);filter:blur(40px)"></div>
<div class="orb" style="bottom:-8%;left:-6%;width:400px;height:400px;background:radial-gradient(circle,rgba(79,142,247,.1),transparent 65%);filter:blur(40px)"></div>
<div style="position:fixed;inset:0;pointer-events:none;background-image:linear-gradient(rgba(79,142,247,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(79,142,247,.025) 1px,transparent 1px);background-size:44px 44px"></div>

<div style="width:100%;max-width:440px;position:relative;z-index:1" class="up">

  <div style="text-align:center;margin-bottom:28px">
    <div style="display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#4f8ef7,#38d9a9);margin-bottom:14px;box-shadow:0 0 24px rgba(79,142,247,.3)">
      <span style="font-weight:900;font-size:22px;color:#fff">S</span>
    </div>
    <h1 style="font-size:26px;font-weight:900;color:#dce8ff;letter-spacing:-.03em;margin:0 0 4px">Create Account</h1>
    <p style="font-size:12px;color:#4a6a9a;text-transform:uppercase;letter-spacing:.1em;margin:0">Start growing today — it's free</p>
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

    @if(request('ref'))
    <div style="background:rgba(56,217,169,.07);border:1px solid rgba(56,217,169,.18);border-radius:10px;padding:11px 14px;margin-bottom:18px;font-size:13px;color:#38d9a9;display:flex;align-items:center;gap:8px">
      <span class="material-symbols-outlined" style="font-size:16px;font-variation-settings:'FILL' 1">card_giftcard</span>
      Referral active — you'll both receive bonuses!
    </div>
    @endif

    <form method="POST" action="{{ route('register') }}" style="display:flex;flex-direction:column;gap:15px">
      @csrf
      @if(request('ref'))<input type="hidden" name="referral_code" value="{{ request('ref') }}">@endif

      <div>
        <label>Full Name</label>
        <input type="text" name="name" class="inp" value="{{ old('name') }}" placeholder="Your full name" required autofocus>
      </div>
      <div>
        <label>Email Address</label>
        <input type="email" name="email" class="inp" value="{{ old('email') }}" placeholder="you@example.com" required>
      </div>
      <div>
        <label>Password</label>
        <div style="position:relative">
          <input type="password" name="password" id="pwd" class="inp" placeholder="Minimum 8 characters" required style="padding-right:42px" oninput="strengthCheck(this.value)">
          <button type="button" onclick="togglePwd('pwd','eye1')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#4a6a9a;padding:0;display:flex">
            <span class="material-symbols-outlined" id="eye1" style="font-size:18px">visibility</span>
          </button>
        </div>
        <div class="strength-bar"><div class="strength-fill" id="str-fill"></div></div>
        <p id="str-label" style="font-size:11px;margin-top:3px;font-weight:600;color:#4a6a9a"></p>
      </div>
      <div>
        <label>Confirm Password</label>
        <div style="position:relative">
          <input type="password" name="password_confirmation" id="pwd2" class="inp" placeholder="Repeat password" required style="padding-right:42px">
          <button type="button" onclick="togglePwd('pwd2','eye2')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#4a6a9a;padding:0;display:flex">
            <span class="material-symbols-outlined" id="eye2" style="font-size:18px">visibility</span>
          </button>
        </div>
      </div>

      {{-- Features strip --}}
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:7px;margin:4px 0">
        @foreach(['⚡ Instant delivery','💰 Lowest prices','🔄 Refill guarantee','🔒 Secure panel'] as $f)
        <div style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:#4a6a9a;padding:7px 10px;border-radius:8px;background:rgba(255,255,255,.025)">{{ $f }}</div>
        @endforeach
      </div>

      <button type="submit" class="btn-main">
        <span class="material-symbols-outlined" style="font-size:18px;font-variation-settings:'FILL' 1">person_add</span> Create Account
      </button>
    </form>

    <div style="text-align:center;margin-top:18px;font-size:13.5px;color:#4a6a9a">
      Already have an account?
      <a href="{{ route('login') }}" style="color:#4f8ef7;font-weight:700;text-decoration:none;margin-left:4px">Sign in →</a>
    </div>
  </div>

  <p style="text-align:center;font-size:11px;color:#1e2d4a;margin-top:20px">© {{ date('Y') }} {{ config('app.name','SMM Panel') }}</p>
</div>

<script>
function togglePwd(id,iconId){
  const i=document.getElementById(id);const e=document.getElementById(iconId);
  if(i.type==='password'){i.type='text';e.textContent='visibility_off';}
  else{i.type='password';e.textContent='visibility';}
}
function strengthCheck(v){
  const fill=document.getElementById('str-fill');
  const lbl=document.getElementById('str-label');
  let score=0;
  if(v.length>=8)score++;if(v.length>=12)score++;
  if(/[A-Z]/.test(v))score++;if(/[0-9]/.test(v))score++;if(/[^A-Za-z0-9]/.test(v))score++;
  const pct=Math.min(100,(score/5)*100);
  const [clr,txt]=score<=1?['#f76f6f','Weak']:score<=3?['#f7c948','Fair']:['#38d9a9','Strong'];
  fill.style.width=pct+'%';fill.style.background=clr;
  lbl.textContent=v.length>0?txt:'';lbl.style.color=clr;
}
</script>
</body>
</html>
