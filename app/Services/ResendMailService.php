<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

/**
 * BrevoMailService
 *
 * Sends email via Brevo's transactional HTTP API (api.brevo.com:443).
 * Bypasses SMTP entirely — safe for Railway and cloud environments.
 *
 * Required env vars:
 *   BREVO_API_KEY=xkeysib-xxxxxxxxxxxx
 *   MAIL_FROM_ADDRESS=your_verified_personal_email@gmail.com
 *   MAIL_FROM_NAME="SMM Elite"
 */
class BrevoMailService
{
    private const API_URL = 'https://api.brevo.com/v3/smtp/email';

    public function send(
        string $to,
        string $subject,
        string $view,
        array  $data = [],
        ?string $fromAddress = null,
        ?string $fromName = null
    ): bool {
        $apiKey = config('services.brevo.api_key') ?? env('BREVO_API_KEY');

        if (empty($apiKey)) {
            Log::error('BrevoMailService: BREVO_API_KEY is not set. Email not sent.', [
                'to'      => $to,
                'subject' => $subject,
            ]);
            return false;
        }

        // Use the passed arguments, or fallback to config, then fallback to env directly
        $finalFromAddress = $fromAddress ?? config('mail.from.address', env('MAIL_FROM_ADDRESS'));
        $finalFromName    = $fromName    ?? config('mail.from.name', env('MAIL_FROM_NAME', 'SMM Elite'));

        // Process raw HTML strings from custom transports or render blade templates
        try {
            if ($view === 'raw_html_string') {
                $html = $data['raw_html'] ?? '';
            } else {
                $html = View::make($view, $data)->render();
            }
        } catch (\Throwable $e) {
            Log::error('BrevoMailService: failed to render view', [
                'view'  => $view,
                'error' => $e->getMessage(),
            ]);
            return false;
        }

        // Post request to Brevo over secure Port 443 HTTPS
        try {
            $response = Http::withHeaders([
                'api-key'      => $apiKey,
                'accept'       => 'application/json',
                'content-type' => 'application/json',
            ])
            ->timeout(12)
            ->post(self::API_URL, [
                'sender'      => ['name' => $finalFromName, 'email' => $finalFromAddress],
                'to'          => [['email' => $to]],
                'subject'     => $subject,
                'htmlContent' => $html,
            ]);

            if ($response->successful()) {
                Log::info('BrevoMailService: email sent successfully via API', [
                    'to'        => $to,
                    'subject'   => $subject,
                    'messageId' => $response->json('messageId'),
                ]);
                return true;
            }

            Log::error('BrevoMailService: API response error mismatch', [
                'to'      => $to,
                'subject' => $subject,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);
            return false;

        } catch (\Throwable $e) {
            Log::error('BrevoMailService: HTTP connection exception', [
                'to'      => $to,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }
}
