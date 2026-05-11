@extends('layouts.app')

@section('title', 'Fund Requests')
@section('page-title', 'Fund Requests')

@section('content')
<div class="flex-1 p-6">
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-on-surface">Fund Requests</h1>
                <p class="text-on-surface-variant mt-1">Review manual deposit proofs and approve/reject</p>
            </div>
            {{-- Pending count badge --}}
            @php $pendingCount = $requests->where('status','pending')->count(); @endphp
            @if($pendingCount > 0)
            <span class="bg-yellow-500/20 text-yellow-400 text-sm font-bold px-4 py-2 rounded-full border border-yellow-500/30">
                {{ $pendingCount }} Pending
            </span>
            @endif
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

        {{-- Filter tabs --}}
        <div class="flex gap-2 mb-4">
            @foreach(['all'=>'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $val => $label)
            <a href="?status={{ $val === 'all' ? '' : $val }}"
               class="px-4 py-1.5 rounded-full text-sm font-medium border transition-all
               {{ (request('status','') === ($val === 'all' ? '' : $val))
                  ? 'bg-purple-500/20 text-purple-400 border-purple-500/40'
                  : 'text-slate-400 border-slate-700 hover:border-slate-500' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>

        <div class="glass-card rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-variant/50 text-on-surface-variant uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4">Method</th>
                            <th class="px-6 py-4">Amount (PKR)</th>
                            <th class="px-6 py-4">USD to Credit</th>
                            <th class="px-6 py-4">Transaction ID</th>
                            <th class="px-6 py-4">Screenshot</th>
                            <th class="px-6 py-4">Submitted</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse($requests as $req)
                        <tr class="hover:bg-surface-variant/10 transition-colors {{ $req->status === 'pending' ? 'bg-yellow-500/3' : '' }}">
                            <td class="px-6 py-4">
                                <div class="font-medium text-on-surface">{{ $req->user->name }}</div>
                                <div class="text-xs text-on-surface-variant">{{ $req->user->email }}</div>
                                <div class="text-xs text-outline">${{ number_format($req->user->funds, 2) }} balance</div>
                            </td>
                            <td class="px-6 py-4 text-on-surface">
                                @if($req->paymentAccount)
                                    <span class="capitalize">{{ $req->paymentAccount->typeLabel() }}</span>
                                    <div class="text-xs text-on-surface-variant">{{ $req->paymentAccount->name }}</div>
                                    <div class="text-xs text-outline font-mono">{{ $req->paymentAccount->account_number }}</div>
                                @else
                                    <span class="text-outline text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-medium text-on-surface">
                                ₨{{ number_format($req->amount, 2) }}
                            </td>
                            <td class="px-6 py-4 font-bold text-primary">
                                ${{ number_format($req->usd_amount, 4) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-sm text-on-surface-variant">
                                {{ $req->transaction_id }}
                            </td>

                            {{-- FIX: Screenshot column --}}
                            <td class="px-6 py-4">
                                @if(!empty($req->screenshot_path))
                                    <a href="{{ Storage::url($req->screenshot_path) }}" target="_blank"
                                       class="inline-flex items-center gap-1 text-xs bg-blue-500/10 text-blue-400 border border-blue-500/20 px-2 py-1 rounded-lg hover:bg-blue-500/20 transition-all">
                                        <span class="material-symbols-outlined text-[14px]">image</span>
                                        View Proof
                                    </a>
                                @else
                                    <span class="text-xs text-outline opacity-50">No screenshot</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-xs text-outline">
                                {{ $req->created_at->diffForHumans() }}<br>
                                <span class="opacity-60">{{ $req->created_at->format('d M Y H:i') }}</span>
                            </td>

                            <td class="px-6 py-4">
                                @php
                                    $colors = ['pending'=>'bg-yellow-500/20 text-yellow-400','approved'=>'bg-emerald-500/20 text-emerald-400','rejected'=>'bg-red-500/20 text-red-400'];
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $colors[$req->status] ?? 'bg-slate-500/20 text-slate-400' }}">
                                    {{ ucfirst($req->status) }}
                                </span>
                                @if($req->admin_note)
                                    <div class="text-xs text-outline mt-1">Note: {{ $req->admin_note }}</div>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                @if($req->status === 'pending')
                                <div class="flex gap-2">
                                    <form action="{{ route('admin.fund-requests.approve', $req) }}" method="POST"
                                          onsubmit="return confirm('Credit ${{ number_format($req->usd_amount, 4) }} to {{ $req->user->name }}?')">
                                        @csrf
                                        <button type="submit"
                                            class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-emerald-500/30 transition-all">
                                            ✓ Approve
                                        </button>
                                    </form>
                                    <button onclick="rejectRequest({{ $req->id }})"
                                        class="bg-red-500/10 text-red-400 border border-red-500/20 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-red-500/20 transition-all">
                                        ✕ Reject
                                    </button>
                                    <form id="reject-form-{{ $req->id }}" action="{{ route('admin.fund-requests.reject', $req) }}" method="POST" class="hidden">
                                        @csrf
                                        <input type="hidden" name="admin_note" id="reject-note-{{ $req->id }}">
                                    </form>
                                </div>
                                @else
                                <span class="text-xs text-outline">
                                    {{ $req->reviewed_at ? $req->reviewed_at->diffForHumans() : '—' }}
                                </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-[48px] text-outline-variant opacity-30 block mb-3">payments</span>
                                <p class="text-on-surface-variant text-sm">No fund requests found</p>
                                <p class="text-outline text-xs mt-1">When users submit manual payments, they'll appear here</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($requests->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant/20">
                {{ $requests->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function rejectRequest(id) {
    const note = prompt("Reason for rejection (min 5 chars):");
    if (note && note.length >= 5) {
        document.getElementById('reject-note-' + id).value = note;
        document.getElementById('reject-form-' + id).submit();
    } else if (note !== null) {
        alert("Please provide a reason (at least 5 characters).");
    }
}
</script>
@endsection
