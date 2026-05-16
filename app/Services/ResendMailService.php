<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

/**
 * ResendMailService
 *
 * Sends email via Resend's HTTP API (api.resend.com:443).
 * This bypasses SMTP entirely — no port 465/587 needed.
 * Railway (and most cloud platforms) never block port 443.
 *
 * Required env vars:
 *   RESEND_API_KEY=re_xxxxxxxxxxxx
 *   MAIL_FROM_ADDRESS=noreply@yourdomain.com
 *   MAIL_FROM_NAME=SMM Elite
 *
 * Usage:
 *   app(ResendMailService::class)->send(
 *       to: 'user@example.com',
 *       subject: 'Hello',
 *       view: 'emails.verify-email',
 *       data: ['url' => '...', 'appName' => '...']
 *   );
 */
class ResendMailService
{
    private const API_URL = 'https://api.resend.com/emails';

    public function send(
        string $to,
        string $subject,
        string $view,
        array  $data = [],
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
