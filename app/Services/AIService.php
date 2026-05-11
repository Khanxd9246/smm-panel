<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * AIService — Groq-powered AI features (free tier).
 *
 * Get your free key: https://console.groq.com
 * Set in Railway Variables: AI_API_KEY, AI_BASE_URL, AI_MODEL
 *
 * Works WITHOUT a key — falls back to rule-based logic automatically.
 */
class AIService
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;
    private bool   $enabled;

    public function __construct()
    {
        // Cast to string explicitly — config() returns null when AI_API_KEY is not set,
        // and PHP 8.2 strict typing rejects null for typed string properties.
        $this->apiKey  = (string) (config('services.ai.key') ?? '');
        $this->baseUrl = rtrim((string) (config('services.ai.base_url') ?? 'https://api.groq.com/openai/v1'), '/');
        $this->model   = (string) (config('services.ai.model') ?? 'llama-3.3-70b-versatile');
        $this->enabled = (bool) config('services.ai.enabled', true) && !empty($this->apiKey);
    }

    public function analyzeService(array $serviceData): array
    {
        if (!$this->enabled) return $this->fallbackAnalysis($serviceData);

        return Cache::remember('ai_analyze_' . md5(json_encode($serviceData)), 3600, function () use ($serviceData) {
            $prompt = "Analyze this SMM service. Respond ONLY with valid JSON, no markdown.\n\nData: " . json_encode($serviceData) . "\n\nFormat: {\"quality_score\":1-10,\"issues\":[],\"strengths\":[],\"recommendation\":\"string\",\"suggested_tags\":[]}";
            $res = $this->callAI($prompt);
            return $res ? $this->parseJson($res, $this->fallbackAnalysis($serviceData)) : $this->fallbackAnalysis($serviceData);
        });
    }

    public function generateTitle(string $rawTitle, string $category = ''): string
    {
        if (!$this->enabled) return $rawTitle;

        return Cache::remember('ai_title_' . md5($rawTitle . $category), 86400, function () use ($rawTitle, $category) {
            $res = $this->callAI("Rewrite this SMM service title professionally. Max 60 chars, no emojis. Return ONLY the title.\nCategory: {$category}\nOriginal: {$rawTitle}");
            return $res ?: $rawTitle;
        });
    }

    public function generateDescription(string $serviceName, array $meta = []): string
    {
        if (!$this->enabled) return "High-quality {$serviceName} with fast delivery.";

        return Cache::remember('ai_desc_' . md5($serviceName . json_encode($meta)), 86400, function () use ($serviceName, $meta) {
            $extras = (!empty($meta['delivery_time']) ? " Delivery: {$meta['delivery_time']}." : '') .
                      (!empty($meta['refill']) ? ' Includes refill.' : '');
            $res = $this->callAI("Write a 2-sentence professional description for this SMM service. Return ONLY the description.\nService: {$serviceName}.{$extras}");
            return $res ?: "High-quality {$serviceName} with fast delivery and reliable results.";
        });
    }

    public function detectLowQuality(array $stats): array
    {
        if (!$this->enabled) return $this->ruleBasedCheck($stats);
        $prompt = "Evaluate this SMM service. Respond ONLY with JSON.\nStats: " . json_encode($stats) . "\nFormat: {\"is_low_quality\":bool,\"score\":1-10,\"reasons\":[],\"recommendation\":\"string\"}";
        $res = $this->callAI($prompt);
        return $res ? $this->parseJson($res, $this->ruleBasedCheck($stats)) : $this->ruleBasedCheck($stats);
    }

    public function generateTags(string $serviceName, array $stats = []): array
    {
        $tags = [];
        if (($stats['avg_start_minutes'] ?? 999) <= 5)    $tags[] = 'Instant';
        elseif (($stats['avg_start_minutes'] ?? 999) <= 30) $tags[] = 'Fast';
        if (($stats['success_rate'] ?? 0) >= 97)          $tags[] = 'Reliable';
        if (($stats['has_refill'] ?? false))               $tags[] = 'Refill';
        if (($stats['orders_count'] ?? 0) >= 200)         $tags[] = 'Best Seller';
        if (($stats['quality_score'] ?? 0) >= 8)          $tags[] = 'Premium';
        if (($stats['rate'] ?? 9999) <= 0.50)             $tags[] = 'Cheap';
        if (($stats['cancel_rate'] ?? 100) <= 2)          $tags[] = 'Stable';
        return array_unique($tags);
    }

    public function categorizeService(string $serviceName): array
    {
        $lower    = strtolower($serviceName);
        $platform = 'other';
        foreach (['instagram' => ['instagram','insta'], 'youtube' => ['youtube'], 'tiktok' => ['tiktok'], 'facebook' => ['facebook','fb'], 'twitter' => ['twitter'], 'telegram' => ['telegram']] as $p => $kws) {
            foreach ($kws as $kw) { if (str_contains($lower, $kw)) { $platform = $p; break 2; } }
        }
        $type = 'other';
        foreach (['followers' => ['follower'], 'likes' => ['like','heart'], 'views' => ['view','watch'], 'comments' => ['comment'], 'shares' => ['share','retweet','repost'], 'subscribers' => ['subscriber','sub']] as $t => $kws) {
            foreach ($kws as $kw) { if (str_contains($lower, $kw)) { $type = $t; break 2; } }
        }
        return ['platform' => $platform, 'type' => $type];
    }

    public function suggestPricing(float $supplierRate, string $serviceName, float $globalMargin = 40): float
    {
        $margin = $globalMargin / 100;
        $lower  = strtolower($serviceName);
        if (str_contains($lower, 'premium') || str_contains($lower, 'hq'))  $margin += 0.10;
        if (str_contains($lower, 'cheap')   || str_contains($lower, 'low')) $margin -= 0.05;
        return round($supplierRate * (1 + max(0.10, $margin)), 4);
    }

    private function callAI(string $prompt, int $maxTokens = 300): ?string
    {
        try {
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ];
            if (str_contains($this->baseUrl, 'openrouter')) {
                $headers['HTTP-Referer'] = config('app.url', 'https://localhost');
                $headers['X-Title']      = config('app.name', 'SMM Panel');
            }
            $res = Http::withHeaders($headers)->timeout(20)->post("{$this->baseUrl}/chat/completions", [
                'model'       => $this->model,
                'max_tokens'  => $maxTokens,
                'temperature' => 0.3,
                'messages'    => [['role' => 'user', 'content' => $prompt]],
            ]);
            if ($res->successful()) {
                return trim((string) ($res->json('choices.0.message.content') ?? '')) ?: null;
            }
            Log::warning('AIService error', ['status' => $res->status(), 'body' => substr($res->body(), 0, 300)]);
        } catch (\Exception $e) {
            Log::error('AIService exception', ['error' => $e->getMessage()]);
        }
        return null;
    }

    private function parseJson(string $response, array $fallback): array
    {
        $clean = preg_replace('/```(?:json)?\s*([\s\S]*?)```/', '$1', $response);
        if (preg_match('/\{[\s\S]+\}/', trim($clean), $m)) $clean = $m[0];
        $parsed = json_decode(trim($clean), true);
        return is_array($parsed) ? $parsed : $fallback;
    }

    private function ruleBasedCheck(array $stats): array
    {
        $score = 7; $reasons = [];
        if (($stats['cancel_rate'] ?? 0) > 15)     { $score -= 3; $reasons[] = 'High cancel rate'; }
        elseif (($stats['cancel_rate'] ?? 0) > 5)  { $score -= 1; $reasons[] = 'Elevated cancel rate'; }
        if (($stats['success_rate'] ?? 100) < 75)  { $score -= 2; $reasons[] = 'Low success rate'; }
        if (!($stats['has_refill'] ?? true))        { $score -= 1; $reasons[] = 'No refill'; }
        return [
            'is_low_quality' => $score <= 3,
            'score'          => max(1, $score),
            'reasons'        => $reasons,
            'recommendation' => empty($reasons) ? 'Looks acceptable' : 'Review performance',
        ];
    }

    private function fallbackAnalysis(array $data): array
    {
        $check = $this->ruleBasedCheck($data);
        return [
            'quality_score'  => $check['score'],
            'issues'         => $check['reasons'],
            'strengths'      => [],
            'recommendation' => $check['recommendation'],
            'suggested_tags' => [],
        ];
    }
}
