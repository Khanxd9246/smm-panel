@extends('layouts.app')

@section('title', 'Payment Accounts')
@section('page-title', 'Payment Accounts')

@section('content')
<div class="flex-1 p-6">
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-on-surface">Payment Accounts</h1>
                <p class="text-on-surface-variant mt-1">Manage external accounts for manual top-up requests.</p>
            </div>
            <a href="{{ route('admin.fund_accounts.create') }}" class="btn-primary px-4 py-3 rounded-xl">Add Account</a>
        </div>

        @if(session('success'))
            <div class="glass-card p-4 rounded-xl mb-6 border-l-4 border-tertiary bg-tertiary/5">
                <p class="text-tertiary font-medium">{{ session('success') }}</p>
            </div>
        @endif

        <div class="glass-card rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="border-b border-outline-variant/30 bg-surface-container">
                            <th class="px-6 py-4 text-left text-xs uppercase tracking-wider text-on-surface-variant">Name</th>
                            <th class="px-6 py-4 text-left text-xs uppercase tracking-wider text-on-surface-variant">IBAN</th>
                            <th class="px-6 py-4 text-left text-xs uppercase tracking-wider text-on-surface-variant">Account #</th>
                            <th class="px-6 py-4 text-left text-xs uppercase tracking-wider text-on-surface-variant">Status</th>
                            <th class="px-6 py-4 text-left text-xs uppercase tracking-wider text-on-surface-variant">Notes</th>
                            <th class="px-6 py-4 text-right text-xs uppercase tracking-wider text-on-surface-variant">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                            <tr class="border-b border-outline-variant/30 hover:bg-surface-container/50 transition-colors">
                                <td class="px-6 py-4 text-on-surface">{{ $account->name }}</td>
                                <td class="px-6 py-4 text-on-surface-variant">{{ $account->iban ?? '—' }}</td>
                                <td class="px-6 py-4 text-on-surface-variant">{{ $account->account_number ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $account->status === 'active' ? 'bg-tertiary/20 text-tertiary' : 'bg-outline/20 text-outline' }} uppercase">{{ $account->status }}</span>
                                </td>
                                <td class="px-6 py-4 text-on-surface-variant">{{ Str::limit($account->notes, 80) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.fund_accounts.edit', $account->id) }}" class="text-primary font-semibold">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[40px] opacity-30 block mb-2">account_balance</span>
                                    <p>No manual payment accounts configured yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $accounts->links() }}</div>
    </div>
</div>
@endsection
