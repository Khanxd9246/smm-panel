<?php

return [
    'mailgun'  => ['domain' => env('MAILGUN_DOMAIN'), 'secret' => env('MAILGUN_SECRET'), 'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'), 'scheme' => 'https'],
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'ses'      => ['key' => env('AWS_ACCESS_KEY_ID'), 'secret' => env('AWS_SECRET_ACCESS_KEY'), 'region' => env('AWS_DEFAULT_REGION', 'us-east-1')],
    'stripe'   => ['model' => App\Models\User::class, 'key' => env('STRIPE_KEY'), 'secret' => env('STRIPE_SECRET'), 'webhook' => ['secret' => env('STRIPE_WEBHOOK_SECRET'), 'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300)]],

    /*
    |----------------------------------------------------------------------
    | AI — Groq (free). Get key: https://console.groq.com
    | Change provider: just update these 3 vars in Railway Variables tab.
    |----------------------------------------------------------------------
    | Groq:       AI_BASE_URL=https://api.groq.com/openai/v1   AI_MODEL=llama-3.3-70b-versatile
    | OpenRouter: AI_BASE_URL=https://openrouter.ai/api/v1      AI_MODEL=mistralai/mistral-7b-instruct
    | OpenAI:     AI_BASE_URL=https://api.openai.com/v1         AI_MODEL=gpt-4o-mini
    */
    'ai' => [
        'key'      => env('AI_API_KEY'),
        'base_url' => env('AI_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model'    => env('AI_MODEL',    'llama-3.3-70b-versatile'),
        'enabled'  => env('AI_ENABLED', true),
    ],
];
