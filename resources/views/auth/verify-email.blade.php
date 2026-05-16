<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Verify Your Email — {{ config('app.name','SMM Panel') }}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;background:#060d1a;font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;padding:16px;color:#dce8ff}
.orb{position:fixed;border-radius:50%;pointer-events:none}
@keyframes up{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
@keyframes pulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.06);opacity:.85}}
@keyframes spin{to{transform:rotate(360deg)}}
.up{animation:up .45s cubic-bezier(.4,0,.2,1) both}
.card{background:rgba(17,29,53,.9);border:1px solid rgba(30,45,74,.9);border-radius:20px;backdrop-filter:blur(24px);box-shadow:0 24px 64px rgba(0,0,0,.5);padding:40px 36px;max-width:460px;width:100%;text-align:center}
.email-icon{width:80px;height:80px;background:linear-gradient(135deg,rgba(79,142,247,.2),rgba(56,217,169,.15));border:1px solid rgba(79,142,247,.3);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:24px;animation:pulse 2.5s ease-in-out infinite}
.email-icon .material-symbols-outlined{font-size:36px;color:#4f8ef7;font-variation-settings:'FILL' 1}
h1{font-size:24px;font-weight:800;color:#dce8ff;letter-spacing:-.03em;margin-bottom:10px}
.subtitle{font-size:14px;color:#6a84a8;line-height:1.6;margin-bottom:28px}
.email-highlight{display:inline-block;background:rgba(79,142,247,.1);border:1px solid rgba(79,142,247,.2);border-radius:8px;padding:6px 14px;font-size:13px;font-weight:700;color:#4f8ef7;margin:8px 0 20px;word-break:break-all}
.steps{text-align:left;background:#0d1525;border:1px solid #1e2d4a;border-radius:12px;padding:18px 20px;margin-bottom:24px}
.step{display:flex;align-items:flex-start;gap:12px;padding:8px 0;border-bottom:1px solid #1a2540;font-size:13px;color:#8aa4c8}
.step:last-child{border-bottom:none;padding-bottom:0}
.step-num{width:22px;height:22px;min-width:22px;border-radius:50%;background:rgba(79,142,247,.15);border:1px solid rgba(79,142,247,.25);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:#4f8ef7;margin-top:1px}
.btn-main{width:100%;background:linear-gradient(135deg,#4f8ef7,#38d9a9);color:#fff;border:none;border-radius:10px;padding:13px;font-size:14px;font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;gap:8px;transition:filter .18s,transform .18s;box-shadow:0 0 24px rgba(79,142,247,.2);margin-bottom:12px}
.btn-main:hover{filter:brightness(1.08);transform:translateY(-1px)}
.btn-main:disabled{opacity:.5;cursor:not-allowed;transform:none}
.btn-ghost{width:100%;background:transparent;color:#6a84a8;border:1px solid #1e2d4a;border-radius:10px;padding:11px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .18s;text-decoration:none}
.btn-ghost:hover{border-color:#4f8ef7;color:#4f8ef7}
.alert{border-radius:10px;padding:11px 14px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:8px}
.alert-ok{background:rgba(56,217,169,.08);border:1px solid rgba(56,217,169,.2);color:#38d9a9}
.alert-err{background:rgba(247,111,111,.08);border:1px solid rgba(247,111,111,.2);color:#f76f6f}
.cooldown{font-size:11px;color:#4a6a9a;margin-top:8px}
</style>
</head>
<body>

<div class="orb" style="top:-10%;left:-8%;width:500px;height:500px;background:radial-gradient(circle,rgba(79,142,247,.1),transparent 65%);filter:blur(40px)"></div>
<div class="orb" style="bottom:-8%;right:-6%;width:420px;height:420px;background:radial-gradient(circle,rgba(56,217,169,.08),transparent 65%);filter:blur(40px)"></div>
<div style="position:fixed;inset:0;pointer-events:none;background-image:linear-gradient(rgba(79,142,247,.02) 1px,transparent 1px),linear-gradient(90deg,rgba(79,142,247,.02) 1px,transparent 1px);background-size:44px 44px"></div>

<div class="up">
<div class="card">

  {{-- Icon --}}
  <div class="email-icon">
    <span class="material-symbols-outlined">mark_email_unread</span>
  </div>

  <h1>Check Your Inbox</h1>
  <p class="subtitle">We've sent a verification link to:</p>
  <div class="email-highlight">{{ auth()->user()->email }}</div>

  {{-- Alerts --}}
  @if(session('status'))
  <div class="alert alert-ok">
    <span class="material-symbols-outlined" style="font-size:16px;font-variation-settings:'FILL' 1">check_circle</span>
    {{ session('status') }}
  </div>
  @endif
  @if(session('success'))
  <div class="alert alert-ok">
    <span class="material-symbols-outlined" style="font-size:16px;font-variation-settings:'FILL' 1">check_circle</span>
    {{ session('success') }}
  </div>
  @endif
  @if($errors->any())
  <div class="alert alert-err">
    <span class="material-symbols-outlined" style="font-size:16px;font-variation-settings:'FILL' 1">error</span>
    {{ $errors->first() }}
  </div>
  @endif

  {{-- Steps --}}
  <div class="steps">
    <div class="step">
      <div class="step-num">1</div>
      <span>Open the email from <strong style="color:#dce8ff">{{ config('app.name') }}</strong></span>
    </div>
    <div class="step">
      <div class="step-num">2</div>
      <span>Click the <strong style="color:#dce8ff">Verify Email Address</strong> button</span>
    </div>
    <div class="step">
      <div class="step-num">3</div>
      <span>You'll be logged into your account automatically</span>
    </div>
    <div class="step">
      <div class="step-num">!</div>
      <span style="color:#7a9ac8">Can't find it? Check your <strong style="color:#dce8ff">spam / junk</strong> folder</span>
    </div>
  </div>

  {{-- Resend form --}}
  <form method="POST" action="{{ route('verification.send') }}" id="resendForm">
    @csrf
    <button type="submit" class="btn-main" id="resendBtn">
      <span class="material-symbols-outlined" style="font-size:17px" id="resendIcon">send</span>
      <span id="resendLabel">Resend Verification Email</span>
    </button>
  </form>
  <div class="cooldown" id="cooldownMsg" style="display:none"></div>

  {{-- Logout --}}
  <form method="POST" action="{{ route('logout') }}" style="margin-top:8px">
    @csrf
    <button type="submit" class="btn-ghost">
      <span class="material-symbols-outlined" style="font-size:15px">logout</span>
      Sign in with a different account
    </button>
  </form>

</div>
</div>

<script>
// Cooldown timer so users don't spam resend
const COOLDOWN = 60;
let timer = null;

document.getElementById('resendForm').addEventListener('submit', function() {
  const btn   = document.getElementById('resendBtn');
  const label = document.getElementById('resendLabel');
  const icon  = document.getElementById('resendIcon');
  const msg   = document.getElementById('cooldownMsg');

  btn.disabled = true;
  icon.style.animation = 'spin 1s linear infinite';
  label.textContent = 'Sending…';

  let secs = COOLDOWN;
  msg.style.display = 'block';
  msg.textContent = `You can resend again in ${secs}s`;

  timer = setInterval(() => {
    secs--;
    msg.textContent = secs > 0
      ? `You can resend again in ${secs}s`
      : '';
    if (secs <= 0) {
      clearInterval(timer);
      btn.disabled = false;
      icon.style.animation = '';
      label.textContent = 'Resend Verification Email';
      msg.style.display = 'none';
    }
  }, 1000);
});
</script>
</body>
</html>
