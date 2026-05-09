<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\PaymentLog;
use App\Models\FundAccount;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class FundsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $accounts = FundAccount::active()->orderBy('name')->get();
        $whatsappLink = Setting::get('whatsapp_link');

        return view('funds.index', compact('accounts', 'whatsappLink'));
    }

    // ── Stripe ────────────────────────────────────────────────────────────────

    public function stripe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:10000',
        ]);

        $stripeSecret = config('services.stripe.secret');
        if (empty($stripeSecret)) {
            Log::error('STRIPE_SECRET not configured');
            return response()->json(['error' => 'Payment system not configured.'], 500);
        }

        try {
            $stripe = new StripeClient($stripeSecret);
            $amountCents = (int) round($validated['amount'] * 100);

            $paymentIntent = $stripe->paymentIntents->create([
                'amount'   => $amountCents,
                'currency' => 'usd',
                'metadata' => [
                    'user_id'    => Auth::id(),
                    'user_email' => Auth::user()->email,
                ],
                'automatic_payment_methods' => ['enabled' => true],
                'statement_descriptor' => substr(config('app.name'), 0, 22),
            ]);

            Log::info('Stripe PaymentIntent created', [
                'user_id'           => Auth::id(),
                'amount'            => $validated['amount'],
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return response()->json([
                'client_secret' => $paymentIntent->client_secret,
                'amount'        => $validated['amount'],
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe PaymentIntent creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Could not initiate payment.'], 500);
        }
    }

    // ── PayPal ────────────────────────────────────────────────────────────────

    public function paypal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:10000',
        ]);

        $clientId = config('services.paypal.client_id');
        $secret   = config('services.paypal.secret');
        $mode     = config('services.paypal.mode', 'live');

        if (empty($clientId) || empty($secret)) {
            return response()->json(['error' => 'PayPal not configured.'], 500);
        }

        $baseUrl = $mode === 'sandbox' ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

        try {
            $client   = new \GuzzleHttp\Client(['timeout' => 15]);
            $tokenRes = $client->post("{$baseUrl}/v1/oauth2/token", [
                'auth'        => [$clientId, $secret],
                'form_params' => ['grant_type' => 'client_credentials'],
            ]);
            $token = json_decode((string) $tokenRes->getBody(), true)['access_token'];

            $orderRes = $client->post("{$baseUrl}/v2/checkout/orders", [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'amount' => [
                            'currency_code' => 'USD',
                            'value'         => number_format($validated['amount'], 2, '.', ''),
                        ],
                        'custom_id' => (string) Auth::id(),
                    ]],
                ],
            ]);

            $order = json_decode((string) $orderRes->getBody(), true);
            return response()->json(['order_id' => $order['id']]);

        } catch (\Throwable $e) {
            Log::error('PayPal order creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Could not initiate PayPal payment.'], 500);
        }
    }

    // ── Manual Payment (EasyPaisa / JazzCash / Bank Transfer) ────────────────

    public function manual(StorePaymentRequest $request)
    {
        $validated = $request->validated();
        $reference = strtoupper($validated['reference']);

        // Check for duplicate reference
        $duplicate = Transaction::where('reference', $reference)->exists();
        if ($duplicate) {
            return back()->withErrors(['reference' => 'This transaction ID has already been submitted.']);
        }

        $account = FundAccount::active()->find($validated['fund_account_id']);
        if (!$account) {
            return back()->withErrors(['fund_account_id' => 'Selected payment account is not available.']);
        }

        // Convert PKR → USD
        $rate = ExchangeRateService::getUsdToPkr();
        $amountInUsd = round($validated['amount'] / $rate, 6);

        try {
            $transaction = Transaction::create([
                'user_id'         => Auth::id(),
                'amount'          => $amountInUsd,
                'type'            => 'deposit',
                'description'     => 'Manual deposit request to ' . $account->name . " (PKR " . number_format($validated['amount'], 2) . ")",
                'status'          => 'pending',
                'reference'       => $reference,
                'gateway'         => 'manual',
                'fund_account_id' => $account->id,
            ]);

            PaymentLog::create([
                'user_id'        => Auth::id(),
                'transaction_id' => $transaction->id,
                'gateway'        => 'manual',
                'status'         => 'pending',
                'amount'         => $amountInUsd,
                'reference'      => $reference,
                'ip_address'     => $request->ip(),
            ]);

            Log::info('Manual payment submitted', ['user_id' => Auth::id(), 'reference' => $reference]);

            return redirect()->route('transactions.index')
                ->with('success', 'Payment request submitted and pending admin verification.');

        } catch (\Throwable $e) {
            Log::error('Manual payment submission failed: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Submission failed. Please try again.']);
        }
    }
}
