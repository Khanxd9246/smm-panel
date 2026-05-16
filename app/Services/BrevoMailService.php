<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

/**
 * BrevoMailService
 *
 * Sends email via Brevo (formerly Sendinblue) HTTP API on port 443.
 * No SMTP, no domain verification required — just verify your sender
 * email address in the Brevo dashboard (Senders & IPs → Senders).
 *
 * Free tier: 300 emails/day, unlimited contacts.
 *
 * Required Railway env vars:
 *   BREVO_API_KEY=xkeysib-xxxxxxxxxxxx   ← from brevo.com → SMTP & API → API Keys
 *   MAIL_FROM_ADDRESS=you@gmail.com       ← must be verified in Brevo Senders
 *   MAIL_FROM_NAME=SMM Elite
 */
class BrevoMailService
{
    private const API_URL = 'https://api.brevo.com/v3/smtp/email';

    public function send(
        string  $to,
        string  $subject,
        string  $view,
        array   $data = [],
        ?string $fromAddress = null,
        ?string $fromName = null,
    ): bool {
        $apiKey = config('services.brevo.api_key') ?? env('BREVO_API_KEY');

        if (empty($apiKey)) {
            Log::error('BrevoMailService: BREVO_API_KEY is not set. Email not sent.', [
                'to'      => $to,
                'subject' => $subject,
            ]);
            return false;
        }

        try {
            $html = View::make($view, $data)->render();
        } catch (\Throwable $e) {
            Log::error('BrevoMailService: failed to render view', [
                'view'  => $view,
                'error' => $e->getMessage(),
            ]);
            return false;
        }

        $payload = [
            'sender' => [
                'name'  => $fromName    ?? config('mail.from.name',    'SMM Elite'),
                'email' => $fromAddress ?? config('mail.from.address', 'noreply@smmelite.com'),
            ],
            'to'          => [['email' => $to]],
            'subject'     => $subject,
            'htmlContent' => $html,
        ];

        try {
            $response = Http::withHeaders([
                    'api-key'      => $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ])
                ->timeout(10)
                ->post(self::API_URL, $payload);

            if ($response->successful()) {
                Log::info('BrevoMailService: email sent', [
                    'to'        => $to,
                    'subject'   => $subject,
                    'messageId' => $response->json('messageId'),
                ]);
                return true;
            }

            Log::error('BrevoMailService: API error', [
                'to'      => $to,
                'subject' => $subject,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);
            return false;

        } catch (\Throwable $e) {
            Log::error('BrevoMailService: HTTP exception', [
                'to'      => $to,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }
}
