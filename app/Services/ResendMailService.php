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
 *   MAIL_FROM_ADDRESS=noreply@yourdomain.com
 *   MAIL_FROM_NAME=SMM Elite
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

        $finalFromAddress = $fromAddress ?? config('mail.from.address', 'noreply@smmelite.com');
        $finalFromName    = $fromName    ?? config('mail.from.name',    'SMM Elite');

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

        try {
            $response = Http::withHeaders([
                'api-key'      => $apiKey,
                'accept'       => 'application/json',
                'content-type' => 'application/json',
            ])
            ->timeout(10)
            ->post(self::API_URL, [
                'sender'      => ['name' => $finalFromName, 'email' => $finalFromAddress],
                'to'          => [['email' => $to]],
                'subject'     => $subject,
                'htmlContent' => $html,
            ]);

            if ($response->successful()) {
                Log::info('BrevoMailService: email sent', [
                    'to'      => $to,
                    'subject' => $subject,
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
}        array  $data = [],
        ?string $fromAddress = null,
        ?string $fromName = null,
    ): bool {
        $apiKey = config('services.resend.api_key') ?? env('RESEND_API_KEY');

        if (empty($apiKey)) {
            Log::error('ResendMailService: RESEND_API_KEY is not set. Email not sent.', [
                'to'      => $to,
                'subject' => $subject,
            ]);
            return false;
        }

        $from = sprintf(
            '%s <%s>',
            $fromName    ?? config('mail.from.name',    'SMM Elite'),
            $fromAddress ?? config('mail.from.address', 'noreply@smmelite.com'),
        );

        try {
            $html = View::make($view, $data)->render();
        } catch (\Throwable $e) {
            Log::error('ResendMailService: failed to render view', [
                'view'  => $view,
                'error' => $e->getMessage(),
            ]);
            return false;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->post(self::API_URL, [
                    'from'    => $from,
                    'to'      => [$to],
                    'subject' => $subject,
                    'html'    => $html,
                ]);

            if ($response->successful()) {
                Log::info('ResendMailService: email sent', [
                    'to'      => $to,
                    'subject' => $subject,
                    'id'      => $response->json('id'),
                ]);
                return true;
            }

            Log::error('ResendMailService: API error', [
                'to'      => $to,
                'subject' => $subject,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);
            return false;

        } catch (\Throwable $e) {
            Log::error('ResendMailService: HTTP exception', [
                'to'      => $to,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }
}
