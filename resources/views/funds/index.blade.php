@extends('layouts.app')
@section('title','Add Funds')
@section('page-title','Add Funds')

@section('css')
<style>
.method-card{background:var(--c-card);border:1.5px solid var(--c-border);border-radius:13px;padding:16px;cursor:pointer;transition:all .18s;text-align:left;width:100%}
.method-card:hover{border-color:rgba(79,142,247,.45);transform:translateY(-1px)}
.method-card.selected{border-color:var(--c-primary);background:rgba(79,142,247,.07);box-shadow:0 0 0 1px rgba(79,142,247,.15)}
.method-card .tick{width:20px;height:20px;border-radius:50%;border:1.5px solid var(--c-border);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .18s}
.method-card.selected .tick{background:var(--c-primary);border-color:var(--c-primary)}
.quick-amt{padding:7px 14px;border-radius:8px;border:1px solid var(--c-border);font-size:12.5px;font-weight:700;color:var(--c-muted);background:transparent;cursor:pointer;transition:all .15s}
.quick-amt:hover,.quick-amt.on{border-color:var(--c-primary);color:var(--c-primary-l);background:rgba(79,142,247,.08)}
</style>
@endsection

@section('content')
<div style="max-width:680px;margin:0 auto">

  {{-- Balance card --}}
  <div class="card fade-up" style="padding:24px;margin-bottom:20px;position:relative;overflow:hidden">
    <div style="position:absolute;top:0;right:0;bottom:0;width:40%;background:linear-gradient(135deg,rgba(79,142,247,.07),rgba(56,217,169,.05));pointer-events:none"></div>
    <div style="position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px">
      <div>
        <p style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted);margin-bottom:6px">Current Balance</p>
        <div style="display:flex;align-items:baseline;gap:20px;flex-wrap:wrap">
          <div>
            <p style="font-size:36px;font-weight:900;color:var(--c-text);line-height:1;letter-spacing:-.02em">${{ number_format(auth()->user()->funds??0,2) }}</p>
            <p style="font-size:11px;color:var(--c-muted);margin-top:2px">USD</p>
          </div>
          <div style="border-left:1px solid var(--c-border);padding-left:20px">
            <p style="font-size:24px;font-weight:800;color:var(--c-accent);line-height:1;letter-spacing:-.02em">₨{{ number_format((auth()->user()->funds??0)*session('usd_pkr_rate',280),0) }}</p>
            <p style="font-size:11px;color:var(--c-muted);margin-top:2px">PKR @ {{ session('usd_pkr_rate',280) }}</p>
          </div>
        </div>
      </div>
      <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,var(--c-primary),var(--c-accent));display:flex;align-items:center;justify-content:center">
        <span class="material-symbols-outlined" style="font-size:26px;color:#fff;font-variation-settings:'FILL' 1">account_balance_wallet</span>
      </div>
    </div>
  </div>

  @if($accounts->isEmpty())
  <div class="card fade-up" style="padding:48px;text-align:center">
    <span class="material-symbols-outlined" style="font-size:48px;opacity:.2;display:block;margin-bottom:12px;color:var(--c-muted)">payment</span>
    <p style="font-weight:700;color:var(--c-text);margin-bottom:6px">No Payment Methods Available</p>
    <p style="font-size:13px;color:var(--c-muted);margin-bottom:16px">Payment accounts are being configured. Contact support for manual deposits.</p>
    @if($whatsappLink)
    <a href="{{ $whatsappLink }}" target="_blank" class="btn-primary" style="display:inline-flex">
      <span class="material-symbols-outlined" style="font-size:17px">support_agent</span> Contact Support
    </a>
    @endif
  </div>
  @else

  {{-- Step 1: Method --}}
  <div class="fade-up" style="margin-bottom:20px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
      <div style="width:24px;height:24px;border-radius:50%;background:var(--c-primary);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0">1</div>
      <h2 style="font-size:14px;font-weight:700;color:var(--c-text)">Select Payment Method</h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px">
      @foreach($accounts as $account)
      <button type="button" class="method-card" onclick="selectAccount({{ $account->id }},this)" data-id="{{ $account->id }}">
        <div style="display:flex;align-items:center;gap:12px">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(79,142,247,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <span class="material-symbols-outlined" style="font-size:20px;color:var(--c-primary);font-variation-settings:'FILL' 1">account_balance</span>
          </div>
          <div style="flex:1;min-width:0">
            <p style="font-weight:700;font-size:13.5px;color:var(--c-text)">{{ $account->name }}</p>
            @if($account->iban)
            <p style="font-size:11px;color:var(--c-muted)">{{ $account->iban }}</p>
            @elseif($account->account_number)
            <p style="font-size:11px;color:var(--c-muted)">{{ $account->account_number }}</p>
            @endif
          </div>
          <div class="tick">
            <span class="material-symbols-outlined" style="font-size:12px;color:#fff;display:none" id="tick-{{ $account->id }}">check</span>
          </div>
        </div>
        @if($account->notes)
        <p style="font-size:11px;color:var(--c-muted);margin-top:10px;padding-top:10px;border-top:1px solid var(--c-border)">{{ Str::limit($account->notes,80) }}</p>
        @endif
      </button>
      @endforeach
    </div>
  </div>

  {{-- Step 2: Form (hidden until method selected) --}}
  <div id="dep-form" style="display:none" class="fade-up">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
      <div style="width:24px;height:24px;border-radius:50%;background:var(--c-primary);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0">2</div>
      <h2 style="font-size:14px;font-weight:700;color:var(--c-text)">Deposit Details</h2>
    </div>
    <div class="card" style="padding:24px">
      <form method="POST" action="{{ route('funds.manual') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="fund_account_id" id="sel-acc-id">

        {{-- Amount --}}
        <div style="margin-bottom:18px">
          <label style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted);display:block;margin-bottom:8px">Amount (PKR) *</label>
          <div style="display:flex;gap:7px;flex-wrap:wrap;margin-bottom:10px">
            @foreach([500,1000,2000,5000,10000] as $amt)
            <button type="button" class="quick-amt" onclick="setAmt({{ $amt }},this)">₨{{ number_format($amt) }}</button>
            @endforeach
          </div>
          <div style="position:relative">
            <span style="position:absolute;left:13px;top:50%;transform:translateY(-50%);font-weight:800;color:var(--c-muted)">₨</span>
            <input type="number" name="amount" id="amt-inp" class="inp" style="padding-left:28px;font-size:18px;font-weight:700" placeholder="0" min="{{ config('services.payments.min_deposit',100) }}" max="{{ config('services.payments.max_deposit',500000) }}" required oninput="previewAmt(this.value)">
          </div>
          <p id="usd-prev" style="font-size:12px;color:var(--c-accent);margin-top:5px;font-weight:600"></p>
        </div>

        {{-- Reference --}}
        <div style="margin-bottom:18px">
          <label style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted);display:block;margin-bottom:8px">Transaction ID / Reference *</label>
          <input type="text" name="reference" class="inp" placeholder="e.g. TXN-12345ABCDE" required maxlength="100" value="{{ old('reference') }}">
          <p style="font-size:11.5px;color:var(--c-muted);margin-top:5px">Copy the transaction ID from your payment app</p>
        </div>

        {{-- Screenshot --}}
        <div style="margin-bottom:22px">
          <label style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted);display:block;margin-bottom:8px">
            Payment Screenshot <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:11px">(optional, speeds up approval)</span>
          </label>
          <label id="upload-zone" style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;border:1.5px dashed var(--c-border);border-radius:11px;cursor:pointer;transition:border-color .15s;gap:6px" onmouseover="this.style.borderColor='var(--c-primary)'" onmouseout="this.style.borderColor='var(--c-border)'">
            <span class="material-symbols-outlined" style="font-size:28px;color:var(--c-muted)">upload</span>
            <p style="font-size:13px;color:var(--c-muted)">Click to upload screenshot</p>
            <p id="file-name" style="font-size:11px;color:var(--c-accent);font-weight:600"></p>
            <input type="file" name="screenshot" accept="image/*" style="display:none" onchange="showFileName(this)">
          </label>
        </div>

        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:13px;font-size:14px">
          <span class="material-symbols-outlined" style="font-size:18px;font-variation-settings:'FILL' 1">send</span>
          Submit Payment Request
        </button>
        <p style="text-align:center;font-size:11.5px;color:var(--c-muted);margin-top:12px">
          Requests are reviewed within 1–24 hours. Balance credited after verification.
        </p>
      </form>
    </div>
  </div>

  @if($whatsappLink)
  <div style="text-align:center;margin-top:16px" class="fade-up">
    <a href="{{ $whatsappLink }}" target="_blank" style="font-size:13px;color:var(--c-muted);text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:color .15s" onmouseover="this.style.color='var(--c-text)'" onmouseout="this.style.color='var(--c-muted)'">
      <span class="material-symbols-outlined" style="font-size:16px">support_agent</span> Need help? Chat on WhatsApp
    </a>
  </div>
  @endif

  @endif
</div>
@endsection

@section('scripts')
<script>
const RATE={{ session('usd_pkr_rate',280) }};
function selectAccount(id,btn){
  document.querySelectorAll('.method-card').forEach(c=>{
    c.classList.remove('selected');
    const t=c.querySelector('.tick span');
    if(t) t.style.display='none';
  });
  btn.classList.add('selected');
  const t=btn.querySelector('.tick span');
  if(t) t.style.display='';
  document.getElementById('sel-acc-id').value=id;
  const f=document.getElementById('dep-form');
  f.style.display='';
  setTimeout(()=>f.scrollIntoView({behavior:'smooth',block:'start'}),60);
}
function setAmt(pkr,btn){
  document.getElementById('amt-inp').value=pkr;
  document.querySelectorAll('.quick-amt').forEach(b=>b.classList.remove('on'));
  btn.classList.add('on');
  previewAmt(pkr);
}
function previewAmt(pkr){
  const p=document.getElementById('usd-prev');
  const v=parseFloat(pkr)||0;
  p.textContent=v>0?`≈ $${(v/RATE).toFixed(2)} USD at ₨${RATE}/USD`:'';
  document.querySelectorAll('.quick-amt').forEach(b=>{
    if(parseInt(b.textContent.replace(/[^0-9]/g,''))!==v) b.classList.remove('on');
  });
}
function showFileName(inp){
  const f=inp.files[0];
  document.getElementById('file-name').textContent=f?f.name:'';
}
document.getElementById('amt-inp')?.addEventListener('input',e=>previewAmt(e.target.value));
@if(old('fund_account_id'))
const btn=document.querySelector('.method-card[data-id="{{ old('fund_account_id') }}"]');
if(btn) selectAccount({{ old('fund_account_id') }},btn);
@endif
</script>
@endsection
