<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Confirmed</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { background:#0f1117; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#e2e8f0; }
  .wrapper { max-width:560px; margin:40px auto; padding:0 16px 60px; }
  .card { background:#1a1d2e; border:1px solid #2d3148; border-radius:16px; overflow:hidden; }
  .header { background:linear-gradient(135deg,#4f8ef7 0%,#7c5cfc 100%); padding:36px 32px; text-align:center; }
  .header .icon { width:52px; height:52px; background:rgba(255,255,255,.15); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px; }
  .header h1 { font-size:22px; font-weight:700; color:#fff; letter-spacing:-.3px; }
  .header p { font-size:14px; color:rgba(255,255,255,.75); margin-top:6px; }
  .body { padding:28px 32px; }
  .greeting { font-size:15px; color:#a0aec0; margin-bottom:20px; }
  .greeting strong { color:#e2e8f0; }
  .detail-box { background:#11141f; border:1px solid #2d3148; border-radius:12px; padding:20px; margin-bottom:20px; }
  .detail-row { display:flex; justify-content:space-between; align-items:flex-start; padding:8px 0; border-bottom:1px solid #1e2235; font-size:13.5px; gap:12px; }
  .detail-row:last-child { border-bottom:none; padding-bottom:0; }
  .detail-row .label { color:#718096; white-space:nowrap; }
  .detail-row .value { color:#e2e8f0; font-weight:600; text-align:right; word-break:break-all; }
  .value.mono { font-family:'Courier New',monospace; font-size:12px; }
  .status-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; background:rgba(79,142,247,.15); color:#4f8ef7; border:1px solid rgba(79,142,247,.3); }
  .note { background:rgba(79,142,247,.06); border:1px solid rgba(79,142,247,.15); border-radius:10px; padding:14px 16px; font-size:13px; color:#90aecf; line-height:1.6; margin-bottom:20px; }
  .footer-text { font-size:12px; color:#4a5568; text-align:center; line-height:1.6; margin-top:28px; }
  .app-name { color:#4f8ef7; font-weight:700; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="card">

    <div class="header">
      <div class="icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
        </svg>
      </div>
      <h1>Order Confirmed</h1>
      <p>We've received your order and it's being processed</p>
    </div>

    <div class="body">
      <p class="greeting">Hi <strong>{{ $order->user->name }}</strong>,</p>

      <div class="detail-box">
        <div class="detail-row">
          <span class="label">Order ID</span>
          <span class="value">#{{ $order->id }}</span>
        </div>
        <div class="detail-row">
          <span class="label">Service</span>
          <span class="value">{{ $order->service->name ?? 'N/A' }}</span>
        </div>
        <div class="detail-row">
          <span class="label">Link</span>
          <span class="value mono">{{ Str::limit($order->link, 50) }}</span>
        </div>
        <div class="detail-row">
          <span class="label">Quantity</span>
          <span class="value">{{ number_format($order->quantity) }}</span>
        </div>
        <div class="detail-row">
          <span class="label">Total Charged</span>
          <span class="value">${{ number_format($order->total, 4) }}</span>
        </div>
        <div class="detail-row">
          <span class="label">Status</span>
          <span class="value"><span class="status-badge">Pending</span></span>
        </div>
        <div class="detail-row">
          <span class="label">Placed At</span>
          <span class="value">{{ $order->created_at->format('d M Y, H:i') }} UTC</span>
        </div>
      </div>

      <div class="note">
        ⏳ Your order is now in our queue and will start shortly. You'll receive another email once it's completed. You can also track progress in your orders dashboard.
      </div>
    </div>

  </div>
  <p class="footer-text">
    This email was sent by <span class="app-name">{{ config('app.name') }}</span>.<br>
    Please do not reply to this email.
  </p>
</div>
</body>
</html>
