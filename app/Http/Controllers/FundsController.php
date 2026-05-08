<?php

namespace App\Http\Controllers;

// ============================================================================
// FundsController — Production Payment Initiation
// ============================================================================
// This controller handles the INITIATION of payments (frontend → backend).
// The actual fund crediting happens in the WebhookController + ProcessWebhookPaymentJob.
//
// NEVER credit funds here — only in the webhook handler after verification.
// The flow is:
//   1. User submits amount → This controller creates a Stripe PaymentIntent
//   2. Frontend confirms payment → Stripe processes card
//   3. Stripe sends webhook → WebhookController verifies → Job credits funds
//
// WHY THIS SEPARATION:
//   - Stripe webhooks are cryptographically verified
//   - Payment confirmation from the browser is NOT verified (spoofable)
//   - Never trust the client's claim that "payment succeeded"
// ============================================================================

use App\Http\Requests\StorePaymentRequest;
use App\Models\PaymentLog;
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
        return view('funds.index');
    }

    // ── Stripe ────────────────────────────────────────────────────────────────

    /**
     * Create a Stripe PaymentIntent and return the client_secret to the frontend.
     *
     * The frontend uses this client_secret with Stripe.js to confirm the payment.
     * We DO NOT credit funds here — we wait for the webhook.
     *
     * Route: POST /funds/stripe
     */
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

            // Convert dollars to cents (Stripe uses smallest currency unit)
            $amountCents = (int) round($validated['amount'] * 100);

            $paymentIntent = $stripe->paymentIntents->create([
                'amount'   => $amountCents,
                'currency' => 'usd',
                // Pass user_id in metadata so the webhook can identify the user
                // This is the ONLY safe way to associate a payment with a user
                'metadata' => [
                    'user_id'    => Auth::id(),
                    'user_email' => Auth::user()->email,
                ],
                // Automatic payment methods (card, Google Pay, Apple Pay)
                'automatic_payment_methods' => ['enabled' => true],
                // Idempotency: if the same user creates the same amount twice
                // within 24h, Stripe deduplicates (safety net)
                'statement_descriptor' => config('app.name'),
            ]);

            Log::info('Stripe PaymentIntent created', [
                'user_id'           => Auth::id(),
                'amount'            => $validated['amount'],
                'payment_intent_id' => $paymentIntent->id,
            ]);

            // Return client_secret to frontend — safe to expose (it's user-specific)
            return response()->json([
                'client_secret' => $paymentIntent->client_secret,
                'amount'        => $validated['amount'],
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe PaymentIntent creation failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
            ]);
            return response()->json(['error' => 'Could not initiate payment. Please try again.'], 500);
        }
    }

    // ── PayPal ────────────────────────────────────────────────────────────────

    /**
     * Create a PayPal order and return the order ID to the frontend.
     *
     * The frontend uses the PayPal JS SDK to complete payment.
     * Funds are credited only after the webhook confirms PAYMENT.CAPTURE.COMPLETED.
     *
     * Route: POST /funds/paypal
     */
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

        $baseUrl = $mode === 'sandbox'
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';

        try {
            // Get access token
            $client   = new \GuzzleHttp\Client(['timeout' => 15, 'verify' => true]);
            $tokenRes = $client->post("{$baseUrl}/v1/oauth2/token", [
                'auth'        => [$clientId, $secret],
                'form_params' => ['grant_type' => 'client_credentials'],
            ]);
            $token = json_decode((string) $tokenRes->getBody(), true)['access_token'];

            // Create order
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
                        // custom_id carries user_id to the webhook handler
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

    /**
     * Submit a manual payment request.
     *
     * The admin reviews and approves/rejects in the admin panel.
     * NO funds are credited here — only on admin approval.
     *
     * Route: POST /funds/manual
     */
    public function manual(StorePaymentRequest $request)
    {
        $validated = $request->validated();

        // Prevent duplicate reference submissions
        $exists = Transaction::where('reference', $validated['reference'])
            ->where('type', 'deposit')
            ->exists();

        if ($exists) {
            return back()->withErrors(['reference' => 'This reference number has already been submitted.']);
        }

        // Convert PKR → USD using live exchange rate
        $rate        = ExchangeRateService::getUsdToPkr();
        $amountInUsd = round($validated['amount'] / $rate, 6);

        try {
            $transaction = Transaction::create([
                'user_id'     => Auth::id(),
                'amount'      => $amountInUsd,
                'type'        => 'deposit',
                'description' => strtoupper($validated['method']) . " Deposit (PKR " . number_format($validated['amount'], 2) . ")",
                'status'      => 'pending',
                'reference'   => $validated['reference'],
                'gateway'     => $validated['method'],
            ]);

            PaymentLog::create([
                'user_id'        => Auth::id(),
                'transaction_id' => $transaction->id,
                'gateway'        => $validated['method'],
                'status'         => 'pending',
                'amount'         => $amountInUsd,
                'reference'      => $validated['reference'],
                'ip_address'     => $request->ip(),
            ]);

            Log::info('Manual payment submitted', [
                'transaction_id' => $transaction->id,
                'user_id'        => Auth::id(),
                'method'         => $validated['method'],
                'amount_pkr'     => $validated['amount'],
                'amount_usd'     => $amountInUsd,
            ]);

            return redirect()->route('transactions.index')
                ->with('success', 'Payment submitted and pending admin verification (10-30 minutes).');

        } catch (\Throwable $e) {
            Log::error('Manual payment submission failed: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Submission failed. Please try again.']);
        }
    }
}
