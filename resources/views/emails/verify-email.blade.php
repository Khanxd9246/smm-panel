<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Verify Your Email</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{background:#0f1117;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#e2e8f0}
  .wrapper{max-width:560px;margin:40px auto;padding:0 16px 60px}
  .card{background:#1a1d2e;border:1px solid #2d3148;border-radius:16px;overflow:hidden}
  .header{background:linear-gradient(135deg,#4f8ef7 0%,#7c5cfc 100%);padding:40px 32px;text-align:center}
  .header .icon{width:64px;height:64px;background:rgba(255,255,255,.15);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px}
  .header h1{font-size:24px;font-weight:800;color:#fff;letter-spacing:-.3px}
  .header p{font-size:14px;color:rgba(255,255,255,.75);margin-top:8px}
  .body{padding:32px}
  .greeting{font-size:15px;color:#a0aec0;margin-bottom:24px;line-height:1.6}
  .greeting strong{color:#e2e8f0}
  .btn-wrap{text-align:center;margin:28px 0}
  .btn{display:inline-block;background:linear-gradient(135deg,#4f8ef7,#38d9a9);color:#fff;text-decoration:none;border-radius:12px;padding:15px 40px;font-size:15px;font-weight:700;letter-spacing:.01em;box-shadow:0 8px 24px rgba(79,142,247,.35)}
  .divider{border:none;border-top:1px solid #1e2235;margin:24px 0}
  .fallback{background:#11141f;border:1px solid #1e2235;border-radius:10px;padding:16px;font-size:12px;color:#718096;line-height:1.7;word-break:break-all}
  .fallback a{color:#4f8ef7;word-break:break-all}
  .expire-note{font-size:12px;color:#4a5568;text-align:center;margin-top:20px}
  .footer-text{font-size:12px;color:#4a5568;text-align:center;line-height:1.6;margin-top:28px}
  .app-name{color:#4f8ef7;font-weight:700}
</style>
</head>
<body>
<div class="wrapper">
  <div class="card">

    <div class="header">
      <div class="icon">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
          <polyline points="22,6 12,13 2,6"/>
        </svg>
      </div>
      <h1>Verify your email</h1>
      <p>One click and you're in</p>
    </div>

    <div class="body">
      <p class="greeting">
        Welcome to <strong>{{ $appName }}</strong>!<br><br>
        Click the button below to verify your email address and activate your account. This link expires in <strong>60 minutes</strong>.
      </p>

      <div class="btn-wrap">
        <a href="{{ $url }}" class="btn">Verify Email Address</a>
      </div>

      <hr class="divider">

      <div class="fallback">
        <strong style="color:#a0aec0;display:block;margin-bottom:6px">Button not working?</strong>
        Copy and paste this link into your browser:<br>
        <a href="{{ $url }}">{{ $url }}</a>
      </div>

      <p class="expire-note">If you didn't create an account, you can safely ignore this email.</p>
    </div>

  </div>
  <p class="footer-text">
    Sent by <span class="app-name">{{ $appName }}</span> · Please do not reply to this email.
  </p>
</div>
</body>
</html>
