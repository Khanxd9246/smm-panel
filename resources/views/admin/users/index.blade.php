@extends('layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')

@section('content')
<div class="flex-1 p-6">
  <div class="max-w-7xl mx-auto">

    {{-- Page header --}}
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-on-surface">Users</h1>
        <p class="text-on-surface-variant mt-1">Manage all platform users</p>
      </div>
    </div>

    {{-- Search --}}
    <div class="glass-card p-4 rounded-xl mb-6">
      <form action="{{ route('admin.users.index') }}" method="GET" class="flex gap-4">
        <input type="text" name="search" placeholder="Search by name or email..."
               value="{{ request('search') }}" class="glass-input flex-1">
        <button type="submit" class="btn-primary px-6 rounded-lg">Search</button>
      </form>
    </div>

    {{-- Flash alerts --}}
    @if(session('success'))
    <div class="glass-card p-4 rounded-xl mb-6 border-l-4 border-tertiary bg-tertiary/5">
      <p class="text-tertiary font-medium">{{ session('success') }}</p>
    </div>
    @endif
    @if($errors->any())
    <div class="glass-card p-4 rounded-xl mb-6 border-l-4 border-error bg-error/5">
      @foreach($errors->all() as $e)
      <p class="text-error text-sm">{{ $e }}</p>
      @endforeach
    </div>
    @endif

    {{-- Users Table --}}
    <div class="glass-card rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full border-collapse">
          <thead>
            <tr class="border-b border-outline-variant/30 bg-surface-container">
              <th class="px-6 py-4 text-left"><p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">User</p></th>
              <th class="px-6 py-4 text-left"><p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Email</p></th>
              <th class="px-6 py-4 text-left"><p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Balance</p></th>
              <th class="px-6 py-4 text-left"><p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Orders</p></th>
              <th class="px-6 py-4 text-left"><p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Status</p></th>
              <th class="px-6 py-4 text-left"><p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Joined</p></th>
              <th class="px-6 py-4 text-right"><p class="text-on-surface-variant text-xs font-semibold uppercase tracking-widest">Actions</p></th>
            </tr>
          </thead>
          <tbody>
            @forelse($users as $user)
            <tr class="border-b border-outline-variant/30 hover:bg-surface-container/50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-gradient-primary flex items-center justify-center text-white font-bold text-xs">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                  </div>
                  <p class="text-on-surface font-medium">{{ $user->name }}</p>
                </div>
              </td>
              <td class="px-6 py-4">
                <p class="text-on-surface-variant text-sm">{{ $user->email }}</p>
              </td>
              <td class="px-6 py-4">
                <p class="text-on-surface font-semibold">${{ number_format($user->funds, 2) }}</p>
                <p class="text-on-surface-variant text-xs">₨{{ number_format($user->funds * session('usd_pkr_rate', 280), 0) }}</p>
              </td>
              <td class="px-6 py-4">
                <p class="text-on-surface font-semibold">{{ $user->orders_count }}</p>
              </td>
              <td class="px-6 py-4">
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $user->status === 'active' ? 'bg-tertiary/20 text-tertiary' : 'bg-error/20 text-error' }}">
                  {{ ucfirst($user->status) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <p class="text-on-surface-variant text-sm">{{ $user->created_at->format('d M Y') }}</p>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end items-center gap-2 flex-wrap">
                  {{-- Ban / Unban --}}
                  @if($user->status === 'active')
                  <form action="{{ route('admin.users.ban', $user->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('Ban this user?')"
                            class="text-error hover:text-error/80 font-semibold text-sm transition-colors">Ban</button>
                  </form>
                  @else
                  <form action="{{ route('admin.users.unban', $user->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-tertiary hover:text-tertiary/80 font-semibold text-sm transition-colors">Unban</button>
                  </form>
                  @endif

                  {{-- Add Funds --}}
                  <button onclick="openFundsModal('add', {{ $user->id }}, '{{ addslashes($user->name) }}', {{ $user->funds }})"
                          class="text-primary hover:text-primary/80 font-semibold text-sm transition-colors">
                    + Funds
                  </button>

                  {{-- Deduct Funds --}}
                  <button onclick="openFundsModal('deduct', {{ $user->id }}, '{{ addslashes($user->name) }}', {{ $user->funds }})"
                          class="text-yellow-500 hover:text-yellow-400 font-semibold text-sm transition-colors"
                          {{ $user->funds <= 0 ? 'disabled style=opacity:.4;cursor:not-allowed' : '' }}>
                    − Funds
                  </button>

                  {{-- Empty Wallet --}}
                  <button onclick="openFundsModal('empty', {{ $user->id }}, '{{ addslashes($user->name) }}', {{ $user->funds }})"
                          class="text-error hover:text-error/80 font-semibold text-sm transition-colors"
                          {{ $user->funds <= 0 ? 'disabled style=opacity:.4;cursor:not-allowed' : '' }}>
                    Clear
                  </button>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="7" class="px-6 py-12 text-center">
                <span class="material-symbols-outlined text-[40px] text-outline-variant opacity-40 block mb-2">people</span>
                <p class="text-on-surface-variant text-sm">No users found</p>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">{{ $users->links() }}</div>

  </div>
</div>

{{-- ══════════════════════════════════════════════════════
     UNIFIED FUNDS MODAL  (add / deduct / empty)
══════════════════════════════════════════════════════ --}}
<div id="fundsModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4" onclick="if(event.target===this)closeFundsModal()">
  <div class="glass-card p-6 rounded-xl w-full max-w-sm" style="background:var(--c-card);border:1px solid var(--c-border)">

    {{-- Header --}}
    <div class="flex items-center gap-10 mb-5">
      <div id="modalIcon" class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
        <span class="material-symbols-outlined" style="font-size:20px;color:#fff">payments</span>
      </div>
      <div>
        <h3 id="modalTitle" class="text-base font-bold" style="color:var(--c-text)">Funds</h3>
        <p id="modalSubtitle" class="text-xs mt-0.5" style="color:var(--c-muted)">User</p>
      </div>
      <button onclick="closeFundsModal()" style="margin-left:auto;background:none;border:none;color:var(--c-muted);cursor:pointer;font-size:20px;line-height:1">✕</button>
    </div>

    {{-- Current balance strip --}}
    <div id="balanceStrip" class="rounded-lg p-3 mb-5 flex items-center justify-between" style="background:var(--c-input-bg);border:1px solid var(--c-border)">
      <span style="font-size:11.5px;color:var(--c-muted);font-weight:600;text-transform:uppercase;letter-spacing:.06em">Current Balance</span>
      <span id="currentBalance" style="font-size:14px;font-weight:800;color:var(--c-text)">$0.00</span>
    </div>

    {{-- Add form --}}
    <form id="addForm" method="POST" class="hidden">
      @csrf
      <div class="mb-4">
        <label style="font-size:11.5px;font-weight:700;color:var(--c-muted);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:5px">Amount (USD)</label>
        <input type="number" name="amount" step="0.01" min="0.01" max="10000" required
               class="inp" style="padding:9px 12px" placeholder="0.00">
      </div>
      <div class="mb-5">
        <label style="font-size:11.5px;font-weight:700;color:var(--c-muted);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:5px">Reason</label>
        <textarea name="reason" rows="2" required minlength="5" class="inp" style="padding:9px 12px;resize:none" placeholder="Reason for adding funds…"></textarea>
      </div>
      <div class="flex gap-3">
        <button type="button" onclick="closeFundsModal()" class="btn-ghost flex-1" style="justify-content:center">Cancel</button>
        <button type="submit" class="btn-primary flex-1" style="justify-content:center;background:var(--c-primary)">
          <span class="material-symbols-outlined" style="font-size:15px">add_circle</span> Add Funds
        </button>
      </div>
    </form>

    {{-- Deduct form --}}
    <form id="deductForm" method="POST" class="hidden">
      @csrf
      <div class="mb-4">
        <label style="font-size:11.5px;font-weight:700;color:var(--c-muted);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:5px">Amount to Deduct (USD)</label>
        <input type="number" name="amount" step="0.01" min="0.01" required
               class="inp" style="padding:9px 12px" placeholder="0.00" id="deductAmountInput">
        <p id="deductHint" style="font-size:10.5px;color:var(--c-muted);margin-top:4px">Cannot exceed current balance. Any excess will be capped.</p>
      </div>
      <div class="mb-5">
        <label style="font-size:11.5px;font-weight:700;color:var(--c-muted);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:5px">Reason</label>
        <textarea name="reason" rows="2" required minlength="5" class="inp" style="padding:9px 12px;resize:none" placeholder="Reason for deduction…"></textarea>
      </div>
      <div class="flex gap-3">
        <button type="button" onclick="closeFundsModal()" class="btn-ghost flex-1" style="justify-content:center">Cancel</button>
        <button type="submit" class="flex-1 btn-primary" style="justify-content:center;background:var(--c-warn);color:#000">
          <span class="material-symbols-outlined" style="font-size:15px">remove_circle</span> Deduct
        </button>
      </div>
    </form>

    {{-- Empty form --}}
    <form id="emptyForm" method="POST" class="hidden">
      @csrf
      <div class="rounded-lg p-3 mb-5" style="background:rgba(247,111,111,.08);border:1px solid rgba(247,111,111,.25)">
        <p style="font-size:12.5px;color:var(--c-danger);font-weight:600">
          ⚠️ This will set the wallet balance to <strong>$0.00</strong>. This action is logged and cannot be undone.
        </p>
      </div>
      <div class="mb-5">
        <label style="font-size:11.5px;font-weight:700;color:var(--c-muted);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:5px">Reason</label>
        <textarea name="reason" rows="2" required minlength="5" class="inp" style="padding:9px 12px;resize:none" placeholder="Reason for clearing wallet…"></textarea>
      </div>
      <div class="flex gap-3">
        <button type="button" onclick="closeFundsModal()" class="btn-ghost flex-1" style="justify-content:center">Cancel</button>
        <button type="submit" class="btn-primary flex-1" style="justify-content:center;background:var(--c-danger)">
          <span class="material-symbols-outlined" style="font-size:15px">delete_sweep</span> Clear Wallet
        </button>
      </div>
    </form>

  </div>
</div>

<script>
var _currentUserId = null;

function openFundsModal(type, userId, userName, balance) {
  _currentUserId = userId;

  // Reset all forms hidden
  ['addForm','deductForm','emptyForm'].forEach(function(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).reset();
  });

  var modal   = document.getElementById('fundsModal');
  var icon    = document.getElementById('modalIcon');
  var title   = document.getElementById('modalTitle');
  var sub     = document.getElementById('modalSubtitle');
  var bal     = document.getElementById('currentBalance');

  bal.textContent = '$' + parseFloat(balance).toFixed(2);
  sub.textContent = userName;

  var baseUrl = '/admin/users/' + userId + '/';

  if (type === 'add') {
    title.textContent = 'Add Funds';
    icon.style.background = 'var(--c-primary)';
    icon.querySelector('span').textContent = 'add_circle';
    var f = document.getElementById('addForm');
    f.action = baseUrl + 'add-funds';
    f.classList.remove('hidden');

  } else if (type === 'deduct') {
    title.textContent = 'Deduct Funds';
    icon.style.background = '#b45309';
    icon.querySelector('span').textContent = 'remove_circle';
    document.getElementById('deductAmountInput').max = parseFloat(balance).toFixed(2);
    var f = document.getElementById('deductForm');
    f.action = baseUrl + 'deduct-funds';
    f.classList.remove('hidden');

  } else if (type === 'empty') {
    title.textContent = 'Clear Wallet';
    icon.style.background = 'var(--c-danger)';
    icon.querySelector('span').textContent = 'delete_sweep';
    var f = document.getElementById('emptyForm');
    f.action = baseUrl + 'empty-funds';
    f.classList.remove('hidden');
  }

  modal.classList.remove('hidden');
}

function closeFundsModal() {
  document.getElementById('fundsModal').classList.add('hidden');
}
</script>
@endsection
