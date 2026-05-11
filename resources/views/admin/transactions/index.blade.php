@extends('layouts.app')
@section('title', 'Transactions')
@section('page-title', 'Transactions')

@section('content')

{{-- Filters --}}
<div class="glass-card rounded-xl p-4 mb-6 fade-up">
    <form method="GET" action="{{ route('admin.transactions.index') }}" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-outline mb-1">Status</label>
            <select name="status" class="glass-input text-sm py-2 px-3 rounded-lg border border-outline-variant/40 bg-transparent">
                <option value="">All Statuses</option>
                @foreach(['pending','completed','failed'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-outline mb-1">Type</label>
            <select name="type" class="glass-input text-sm py-2 px-3 rounded-lg border border-outline-variant/40 bg-transparent">
                <option value="">All Types</option>
                @foreach(['deposit','deduction','refund'] as $t)
                <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-gradient-primary text-white px-4 py-2 rounded-lg text-sm font-semibold hover:brightness-110 transition-all">
            Filter
        </button>
        @if(request()->hasAny(['status','type']))
        <a href="{{ route('admin.transactions.index') }}" class="text-outline text-sm hover:text-on-surface transition-colors py-2">
            Clear
        </a>
        @endif
    </form>
</div>

{{-- Success / error flash --}}
@if(session('success'))
<div class="glass-card p-4 rounded-xl mb-4 border-l-4 border-tertiary bg-tertiary/5 text-tertiary text-sm fade-up">
    {{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="glass-card p-4 rounded-xl mb-4 border-l-4 border-error bg-error/5 text-error text-sm fade-up">
    {{ $errors->first() }}
</div>
@endif

{{-- Transactions table --}}
<div class="glass-card rounded-xl overflow-hidden fade-up">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="border-b border-outline-variant/30">
                    <th class="px-4 py-3 text-left text-outline font-label-caps text-xs font-normal">ID</th>
                    <th class="px-4 py-3 text-left text-outline font-label-caps text-xs font-normal">User</th>
                    <th class="px-4 py-3 text-left text-outline font-label-caps text-xs font-normal">Type</th>
                    <th class="px-4 py-3 text-left text-outline font-label-caps text-xs font-normal">Amount</th>
                    <th class="px-4 py-3 text-left text-outline font-label-caps text-xs font-normal">Reference</th>
                    <th class="px-4 py-3 text-left text-outline font-label-caps text-xs font-normal">Screenshot</th>
                    <th class="px-4 py-3 text-left text-outline font-label-caps text-xs font-normal">Status</th>
                    <th class="px-4 py-3 text-left text-outline font-label-caps text-xs font-normal">Date</th>
                    <th class="px-4 py-3 text-right text-outline font-label-caps text-xs font-normal">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                @php
                    $sc = match($tx->status) {
                        'completed' => 'bg-tertiary/10 text-tertiary border-tertiary/30',
                        'pending'   => 'bg-[#fcd34d]/10 text-[#fcd34d] border-[#fcd34d]/30',
                        'failed'    => 'bg-error/10 text-error border-error/30',
                        default     => 'bg-surface-container text-outline border-outline/30',
                    };
                @endphp
                <tr class="border-b border-surface-container-high hover:bg-white/5 transition-colors">
                    <td class="px-4 py-3 font-mono text-outline text-xs">#{{ $tx->id }}</td>

                    <td class="px-4 py-3">
                        <p class="text-on-surface text-xs font-medium">{{ $tx->user->name ?? 'N/A' }}</p>
                        <p class="text-outline text-[10px]">{{ $tx->user->email ?? '' }}</p>
                    </td>

                    <td class="px-4 py-3">
                        <span class="text-xs font-semibold {{ $tx->type === 'deposit' ? 'text-tertiary' : ($tx->type === 'deduction' ? 'text-error' : 'text-primary') }}">
                            {{ ucfirst($tx->type) }}
                        </span>
                    </td>

                    <td class="px-4 py-3 text-primary font-bold text-sm">
                        ${{ number_format($tx->amount, 2) }}
                    </td>

                    <td class="px-4 py-3 font-mono text-outline text-xs max-w-[120px] truncate" title="{{ $tx->reference }}">
                        {{ $tx->reference ?? '—' }}
                    </td>

                    {{-- Screenshot column --}}
                    <td class="px-4 py-3">
                        @if(!empty($tx->screenshot_path))
                        <button type="button"
                                onclick="openScreenshot('{{ Storage::url($tx->screenshot_path) }}')"
                                class="flex items-center gap-1 text-tertiary hover:text-primary transition-colors text-xs font-semibold">
                            <span class="material-symbols-outlined text-[16px]">image</span> View
                        </button>
                        @else
                        <span class="text-outline text-xs">—</span>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded border text-[10px] font-bold uppercase {{ $sc }}">
                            {{ ucfirst($tx->status) }}
                        </span>
                    </td>

                    <td class="px-4 py-3 text-outline text-xs whitespace-nowrap">
                        {{ $tx->created_at->format('d M y, H:i') }}
                    </td>

                    {{-- Approve / Reject --}}
                    <td class="px-4 py-3 text-right">
                        @if($tx->status === 'pending' && $tx->type === 'deposit')
                        <div class="flex items-center justify-end gap-2">
                            <form method="POST" action="{{ route('admin.transactions.approve', $tx) }}"
                                  onsubmit="return confirm('Approve ${{ number_format($tx->amount,2) }} for {{ addslashes($tx->user->name ?? '') }}?')">
                                @csrf
                                <button type="submit"
                                        class="bg-tertiary/10 border border-tertiary/30 text-tertiary px-3 py-1 rounded-lg text-xs font-bold hover:bg-tertiary/20 transition-all whitespace-nowrap">
                                    ✓ Approve
                                </button>
                            </form>

                            <button type="button"
                                    onclick="openRejectModal({{ $tx->id }}, '{{ route('admin.transactions.reject', $tx) }}')"
                                    class="bg-error/10 border border-error/30 text-error px-3 py-1 rounded-lg text-xs font-bold hover:bg-error/20 transition-all">
                                ✕ Reject
                            </button>
                        </div>
                        @else
                        <span class="text-outline text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-12 text-center text-outline text-sm">
                        <span class="material-symbols-outlined text-[40px] block mb-2 opacity-20">receipt_long</span>
                        No transactions found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($transactions->hasPages())
    <div class="px-4 py-3 border-t border-outline-variant/30">
        {{ $transactions->withQueryString()->links() }}
    </div>
    @endif
</div>

{{-- Screenshot lightbox modal --}}
<div id="screenshot-modal"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm hidden"
     onclick="closeScreenshot()">
    <div class="relative max-w-3xl max-h-[90vh] mx-4" onclick="event.stopPropagation()">
        <button onclick="closeScreenshot()"
                class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-surface-container border border-outline-variant/30 text-outline hover:text-on-surface transition-colors flex items-center justify-center z-10">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
        <img id="screenshot-img"
             src=""
             alt="Payment Screenshot"
             class="max-w-full max-h-[85vh] object-contain rounded-xl border border-outline-variant/30 shadow-2xl">
        <a id="screenshot-link" href="#" target="_blank"
           class="block text-center text-primary text-xs mt-2 hover:underline">
            Open full size ↗
        </a>
    </div>
</div>

{{-- Reject modal --}}
<div id="reject-modal"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm hidden">
    <div class="glass-card rounded-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-on-surface font-semibold text-lg mb-4">Reject Transaction</h3>
        <form id="reject-form" method="POST" action="">
            @csrf
            <div class="mb-4">
                <label class="block text-sm text-outline mb-2">Reason for rejection <span class="text-error">*</span></label>
                <textarea name="reason" rows="3" required minlength="5" maxlength="255"
                          class="w-full glass-input py-2 px-3 rounded-lg border border-outline-variant/40 bg-transparent text-sm resize-none focus:border-error transition-colors"
                          placeholder="e.g. Transaction ID not found in our records"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit"
                        class="flex-1 bg-error/10 border border-error/30 text-error py-2.5 rounded-lg text-sm font-bold hover:bg-error/20 transition-all">
                    Confirm Reject
                </button>
                <button type="button" onclick="closeRejectModal()"
                        class="flex-1 bg-surface-container border border-outline-variant/20 text-outline py-2.5 rounded-lg text-sm font-semibold hover:text-on-surface transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
function openScreenshot(url) {
    document.getElementById('screenshot-img').src = url;
    document.getElementById('screenshot-link').href = url;
    document.getElementById('screenshot-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeScreenshot() {
    document.getElementById('screenshot-modal').classList.add('hidden');
    document.getElementById('screenshot-img').src = '';
    document.body.style.overflow = '';
}
function openRejectModal(id, action) {
    document.getElementById('reject-form').action = action;
    document.getElementById('reject-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeRejectModal() {
    document.getElementById('reject-modal').classList.add('hidden');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeScreenshot(); closeRejectModal(); }
});
</script>
@endsection
