@extends('layouts.app')

@section('title', 'Add Payment Account')
@section('page-title', 'Add Payment Account')

@section('content')
<div class="flex-1 p-6">
    <div class="max-w-3xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-on-surface">Add Payment Account</h1>
            <p class="text-on-surface-variant mt-1">Create an external account for manual fund requests.</p>
        </div>

        <div class="glass-card rounded-xl p-6">
            @if($errors->any())
                <div class="mb-4 p-4 bg-error/10 border border-error/20 rounded-xl text-error">
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.fund_accounts.store') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-2">Account Name</label>
                    <input name="name" value="{{ old('name') }}" type="text" required class="glass-input w-full" placeholder="e.g. Bank Transfer - ABC Bank">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-2">IBAN</label>
                    <input name="iban" value="{{ old('iban') }}" type="text" class="glass-input w-full" placeholder="Optional IBAN">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-2">Account Number</label>
                    <input name="account_number" value="{{ old('account_number') }}" type="text" class="glass-input w-full" placeholder="Optional account number">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-2">Notes</label>
                    <textarea name="notes" rows="4" class="glass-input w-full" placeholder="Optional instructions or branch details">{{ old('notes') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-2">Status</label>
                    <select name="status" class="glass-input w-full">
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary px-5 py-3 rounded-xl">Save Account</button>
                    <a href="{{ route('admin.fund_accounts.index') }}" class="btn-ghost px-5 py-3 rounded-xl">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
