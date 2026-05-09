@extends('layouts.app')
@section('title', 'Add Funds – Manual Payment')
@section('page-title', 'Add Funds')

@section('content')
<div class="max-w-2xl mx-auto p-6">

    {{-- Balance --}}
    <div class="glass-card rounded-xl p-6 mb-6">
        <p class="text-on-surface-variant text-sm mb-1">Current Balance</p>
        <p class="text-3xl font-bold text-on-surface">${{ number_format(auth()->user()->funds ?? 0, 2) }}</p>
        <p class="text-on-surface-variant text-sm mt-1">≈ ₨{{ number_format((auth()->user()->funds ?? 0) * $rate, 0) }} PKR</p>
    </div>

    @if(session('success'))
    <div class="glass-card p-4 rounded-xl mb-6 border-l-4 border-tertiary bg-tertiary/5">
        <p class="text-tertiary font-medium">{{ session('success') }}</p>
    </div>
    @endif
    @if($errors->any())
    <div class="glass-card p-4 rounded-xl mb-6 border-l-4 border-error bg-error/5">
        @foreach($errors->all() as $error)
            <p class="text-error font-medium">{{ $error }}</p>
        @endforeach
    </div>
    @endif

    @if($accounts->isEmpty())
    <div class="glass-card p-6 rounded-xl text-center">
        <p class="text-on-surface-variant">No payment accounts available yet. Please contact support.</p>
    </div>
    @else

    {{-- Step 1: Select Account --}}
    <div class="glass-card rounded-xl p-6 mb-6">
        <h2 class="text-lg font-bold text-on-surface mb-4">
            <span class="text-primary mr-2">1.</span> Select a payment account
        </h2>
        <div class="space-y-3">
            @foreach($accounts as $account)
            <button type="button"
                    id="acc-{{ $account->id }}"
                    onclick="selectAccount(
                        {{ $account->id }},
                        @json($account->name),
                        @json($account->account_number),
                        @json($account->account_title ?? ''),
                        @json($account->bank_name ?? ''),
                        @json($account->typeLabel())
                    )"
                    class="account-btn w-full text-left glass-card border border-outline-variant/30
                           hover:border-primary/50 rounded-xl p-4 transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-on-surface font-semibold">{{ $account->name }}</p>
                        <p class="text-on-surface-variant text-sm font-mono mt-0.5">{{ $account->account_number }}</p>
                        @if($account->account_title)
                            <p class="text-on-surface-variant text-xs mt-0.5">{{ $account->account_title }}</p>
                        @endif
                        @if($account->bank_name)
                            <p class="text-on-surface-variant text-xs">{{ $account->bank_name }}</p>
                        @endif
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-primary/20 text-primary flex-shrink-0 ml-3">
                        {{ $account->typeLabel() }}
                    </span>
                </div>
            </button>
            @endforeach
        </div>
    </div>

    {{-- Step 2: Instructions --}}
    <div id="instructions-section" class="glass-card rounded-xl p-6 mb-6 hidden">
        <h2 class="text-lg font-bold text-on-surface mb-3">
            <span class="text-primary mr-2">2.</span> Send money to this account
        </h2>
        <div class="bg-surface-container rounded-xl p-4 border border-outline-variant/30">
            <p class="text-on-surface font-semibold" id="inst-name"></p>
            <p class="text-on-surface-variant text-sm" id="inst-type"></p>
            <div class="mt-3 pt-3 border-t border-outline-variant/30">
                <p class="text-xs text-on-surface-variant uppercase tracking-widest mb-1">Account Number</p>
                <p class="text-on-surface font-mono font-bold text-lg select-all" id="inst-number"></p>
            </div>
            <div id="inst-title-wrap" class="mt-2 hidden">
                <p class="text-xs text-on-surface-variant uppercase tracking-widest mb-1">Account Title</p>
                <p class="text-on-surface font-semibold" id="inst-title"></p>
            </div>
            <div id="inst-bank-wrap" class="mt-2 hidden">
                <p class="text-xs text-on-surface-variant uppercase tracking-widest mb-1">Bank</p>
                <p class="text-on-surface font-semibold" id="inst-bank"></p>
            </div>
        </div>
        <p class="text-on-surface-variant text-sm mt-3">
            After sending, fill in the form below with your transaction details.
        </p>
    </div>

    {{-- Step 3: Submit --}}
    <div id="submit-section" class="glass-card rounded-xl p-6 mb-6 hidden">
        <h2 class="text-lg font-bold text-on-surface mb-4">
            <span class="text-primary mr-2">3.</span> Submit transaction details
        </h2>
        <form action="{{ route('funds.request.store') }}" method="POST">
            @csrf
            <input type="hidden" name="payment_account_id" id="selected-account-id"
                   value="{{ old('payment_account_id') }}">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-on-surface mb-2">Amount sent (PKR)</label>
                    <input type="number" name="amount" min="100" step="1" class="glass-input w-full"
                           placeholder="e.g. 5000" required value="{{ old('amount') }}">
                    <p class="text-on-surface-variant text-xs mt-1">
                        Rate: 1 USD = ₨{{ $rate }} — your account will be credited in USD
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-on-surface mb-2">Transaction ID / Reference</label>
                    <input type="text" name="transaction_id" class="glass-input w-full font-mono"
                           placeholder="TXN reference from your payment app" required
                           value="{{ old('transaction_id') }}">
                    <p class="text-on-surface-variant text-xs mt-1">
                        Find this in your payment app's transaction history
                    </p>
                </div>
            </div>
            <button type="submit" class="w-full btn-primary py-3 rounded-xl font-semibold mt-6">
                Submit for Review
            </button>
        </form>
    </div>

    {{-- WhatsApp --}}
    @if($waNumber)
    <div class="glass-card rounded-xl p-5 mb-6 border border-green-500/30 bg-green-500/5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-green-500/20 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.552 4.117 1.516 5.845L.057 23.454c-.066.263.154.486.418.422l5.794-1.401A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.015-1.374l-.36-.214-3.732.902.937-3.62-.235-.374A9.818 9.818 0 1112 21.818z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-on-surface font-semibold text-sm">Want faster approval?</p>
                <p class="text-on-surface-variant text-xs mt-0.5">
                    Message admin on WhatsApp with your TXN ID for priority processing.
                </p>
            </div>
            <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waMessage) }}"
               target="_blank" rel="noopener"
               class="flex-shrink-0 flex items-center gap-2 px-4 py-2 rounded-xl
                      bg-green-500/20 text-green-400 font-semibold text-sm
                      hover:bg-green-500/30 transition-colors">
                Chat Now
            </a>
        </div>
    </div>
    @endif

    @endif

    {{-- My previous requests --}}
    @if($myRequests->count())
    <div class="glass-card rounded-xl overflow-hidden mt-2">
        <div class="px-6 py-4 border-b border-outline-variant/30">
            <h2 class="font-bold text-on-surface">My Fund Requests</h2>
        </div>
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-surface-container">
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-on-surface-variant">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-on-surface-variant">TXN ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-on-surface-variant">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-on-surface-variant">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($myRequests as $req)
                <tr class="border-t border-outline-variant/30">
                    <td class="px-4 py-3">
                        <p class="text-on-surface font-semibold">₨{{ number_format($req->amount, 0) }}</p>
                        <p class="text-primary text-xs">${{ number_format($req->usd_amount, 4) }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-on-surface font-mono text-sm">{{ $req->transaction_id }}</p>
                        <p class="text-on-surface-variant text-xs">{{ $req->paymentAccount->name }}</p>
                    </td>
                    <td class="px-4 py-3">
                        @if($req->status === 'pending')
                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-yellow-500/20 text-yellow-400">Pending</span>
                        @elseif($req->status === 'approved')
                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-tertiary/20 text-tertiary">Approved ✓</span>
                        @else
                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-error/20 text-error">Rejected</span>
                        @endif
                        @if($req->admin_note)
                            <p class="text-on-surface-variant text-xs mt-0.5">{{ $req->admin_note }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-on-surface-variant text-sm">{{ $req->created_at->format('d M Y') }}</p>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>

<script>
function selectAccount(id, name, number, title, bank, type) {
    // Highlight selected
    document.querySelectorAll('.account-btn').forEach(b => {
        b.classList.remove('border-primary');
        b.classList.add('border-outline-variant/30');
    });
    const btn = document.getElementById('acc-' + id);
    btn.classList.remove('border-outline-variant/30');
    btn.classList.add('border-primary');

    document.getElementById('selected-account-id').value = id;

    // Populate instructions
    document.getElementById('inst-name').textContent = name;
    document.getElementById('inst-type').textContent = type;
    document.getElementById('inst-number').textContent = number;

    const titleWrap = document.getElementById('inst-title-wrap');
    const bankWrap  = document.getElementById('inst-bank-wrap');

    if (title) {
        document.getElementById('inst-title').textContent = title;
        titleWrap.classList.remove('hidden');
    } else {
        titleWrap.classList.add('hidden');
    }
    if (bank) {
        document.getElementById('inst-bank').textContent = bank;
        bankWrap.classList.remove('hidden');
    } else {
        bankWrap.classList.add('hidden');
    }

    document.getElementById('instructions-section').classList.remove('hidden');
    document.getElementById('submit-section').classList.remove('hidden');
}

// Re-select on validation error
@if(old('payment_account_id'))
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('acc-{{ old('payment_account_id') }}');
    if (btn) btn.click();
});
@endif
</script>
@endsection
