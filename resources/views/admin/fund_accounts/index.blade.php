@extends('layouts.app')
@section('title','Payment Accounts')
@section('page-title','Payment Accounts')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:12px" class="fade-up">
  <p style="font-size:13px;color:var(--c-muted)">Manage bank / payment accounts users deposit funds into.</p>
  <a href="{{ route('admin.fund_accounts.create') }}" class="btn-primary">
    <span class="material-symbols-outlined" style="font-size:16px;font-variation-settings:'FILL' 1">add</span> Add Account
  </a>
</div>

@if($accounts->isEmpty())
<div class="card fade-up" style="padding:60px;text-align:center">
  <span class="material-symbols-outlined" style="font-size:52px;opacity:.15;display:block;margin-bottom:12px;color:var(--c-muted)">account_balance</span>
  <p style="font-size:14px;font-weight:600;color:var(--c-text);margin-bottom:6px">No payment accounts yet</p>
  <p style="font-size:13px;color:var(--c-muted);margin-bottom:16px">Add your first bank or payment method so users can deposit funds.</p>
  <a href="{{ route('admin.fund_accounts.create') }}" class="btn-primary" style="display:inline-flex">
    <span class="material-symbols-outlined" style="font-size:16px">add</span> Add Account
  </a>
</div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px">
  @foreach($accounts as $account)
  <div class="card fade-up" style="padding:22px;transition:border-color .18s,transform .18s" onmouseover="this.style.borderColor='rgba(79,142,247,.35)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='var(--c-border)';this.style.transform='none'">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px">
      <div style="display:flex;align-items:center;gap:12px;min-width:0">
        <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,var(--c-primary),var(--c-accent));display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <span class="material-symbols-outlined" style="font-size:22px;color:#fff;font-variation-settings:'FILL' 1">account_balance</span>
        </div>
        <div style="min-width:0">
          <p style="font-size:15px;font-weight:700;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $account->name }}</p>
          <span class="chip {{ $account->is_active?'chip-green':'chip-gray' }}" style="font-size:10px;margin-top:2px;display:inline-flex">
            <span style="width:5px;height:5px;border-radius:50%;background:{{ $account->is_active?'var(--c-accent)':'var(--c-muted)' }};display:inline-block"></span>
            {{ $account->is_active?'Active':'Inactive' }}
          </span>
        </div>
      </div>
      <div style="display:flex;gap:6px;flex-shrink:0">
        <a href="{{ route('admin.fund_accounts.edit',$account->id) }}" style="background:none;border:none;cursor:pointer;color:var(--c-muted);display:flex;padding:6px;border-radius:7px;transition:color .15s;text-decoration:none" onmouseover="this.style.color='var(--c-primary-l)'" onmouseout="this.style.color='var(--c-muted)'" title="Edit">
          <span class="material-symbols-outlined" style="font-size:17px">edit</span>
        </a>
        <form method="POST" action="{{ route('admin.fund_accounts.destroy',$account->id) }}" onsubmit="return confirm('Delete this account?')">
          @csrf @method('DELETE')
          <button type="submit" style="background:none;border:none;cursor:pointer;color:var(--c-muted);display:flex;padding:6px;border-radius:7px;transition:color .15s" onmouseover="this.style.color='var(--c-danger)'" onmouseout="this.style.color='var(--c-muted)'" title="Delete">
            <span class="material-symbols-outlined" style="font-size:17px">delete</span>
          </button>
        </form>
      </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:8px">
      @foreach([
        ['Account Title', $account->account_title??null,'person'],
        ['Account No.',   $account->account_number??null,'tag'],
        ['IBAN',          $account->iban??null,'credit_card'],
        ['Bank',          $account->bank_name??null,'account_balance'],
        ['Min Deposit',   $account->min_deposit?'$'.number_format($account->min_deposit,2):null,'south_west'],
        ['Max Deposit',   $account->max_deposit?'$'.number_format($account->max_deposit,2):null,'north_east'],
      ] as [$label,$val,$icon])
      @if($val)
      <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.04)">
        <span class="material-symbols-outlined" style="font-size:14px;color:var(--c-muted);flex-shrink:0">{{ $icon }}</span>
        <span style="font-size:11px;color:var(--c-muted);min-width:80px">{{ $label }}</span>
        <span style="font-size:12.5px;font-weight:600;color:var(--c-text);font-family:'JetBrains Mono',monospace">{{ $val }}</span>
      </div>
      @endif
      @endforeach
    </div>

    @if($account->notes)
    <div style="margin-top:12px;padding:10px;border-radius:8px;background:rgba(247,201,72,.05);border:1px solid rgba(247,201,72,.15)">
      <p style="font-size:11.5px;color:var(--c-muted);line-height:1.5">{{ $account->notes }}</p>
    </div>
    @endif

    <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between">
      <span style="font-size:11px;color:var(--c-muted)">{{ $account->transactions_count??0 }} transactions</span>
      <form method="POST" action="{{ route('admin.fund_accounts.toggle',$account->id) }}">
        @csrf
        <button type="submit" class="btn-xs {{ $account->is_active?'btn-outline-danger':'btn-outline-success' }}">
          {{ $account->is_active?'Deactivate':'Activate' }}
        </button>
      </form>
    </div>
  </div>
  @endforeach
</div>
@endif
@endsection
