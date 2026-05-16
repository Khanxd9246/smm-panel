<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class ResendMailService
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
        $apiKey = env('BREVO_API_KEY');

        if (empty($apiKey)) {
            Log::error('ResendMailService: BREVO_API_KEY is not set.');
            return false;
        }

        $finalFromAddress = $fromAddress ?? env('MAIL_FROM_ADDRESS');
        $finalFromName    = $fromName    ?? env('MAIL_FROM_NAME', 'SMM Elite');

        try {
            if ($view === 'raw_html_string') {
                $html = $data['raw_html'] ?? '';
            } else {
                $html = View::make($view, $data)->render();
            }
        } catch (\Throwable $e) {
            Log::error('ResendMailService: failed to render view', ['error' => $e->getMessage()]);
            return false;
        }

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

            if (!$response->successful()) {
                Log::error('ResendMailService: Brevo API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('ResendMailService: HTTP exception', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
