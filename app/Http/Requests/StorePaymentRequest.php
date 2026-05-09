<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
<<<<<<< HEAD
            'method' => 'required|in:easypaisa,jazzcash,crypto,pm',
=======
            'fund_account_id' => 'required|integer|exists:fund_accounts,id',
>>>>>>> 491ed81 (initial commit)
            'amount' => [
                'required',
                'numeric',
                'min:' . (config('services.payments.min_deposit', 100)),
                'max:' . (config('services.payments.max_deposit', 500000)),
            ],
            'reference' => 'required|string|max:100|regex:/^[a-zA-Z0-9_-]+$/',
        ];
    }

    public function messages(): array
    {
        return [
<<<<<<< HEAD
            'method.required' => 'Please select a payment method.',
            'method.in' => 'Invalid payment method.',
=======
            'fund_account_id.required' => 'Please select a payment account.',
            'fund_account_id.exists' => 'Selected payment account is invalid.',
>>>>>>> 491ed81 (initial commit)
            'amount.required' => 'Please enter an amount.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Minimum deposit is PKR ' . env('MIN_DEPOSIT_AMOUNT', 100),
            'amount.max' => 'Maximum deposit is PKR ' . env('MAX_DEPOSIT_AMOUNT', 500000),
<<<<<<< HEAD
            'reference.required' => 'Please provide a reference/transaction ID.',
=======
            'reference.required' => 'Please provide a transaction ID.',
>>>>>>> 491ed81 (initial commit)
            'reference.max' => 'Reference cannot exceed 100 characters.',
            'reference.regex' => 'Reference can only contain letters, numbers, hyphens, and underscores.',
        ];
    }
}
