<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        // Default workhorse chat model (fast, ultra-low latency, highly cost-effective)
        'model' => env('OPENAI_MODEL', 'gpt-5.6-luna'),
        // High-reasoning balanced model for complex tax calculations, KB enhancer, and RAG evaluation
        'complex_model' => env('OPENAI_COMPLEX_MODEL', 'gpt-5.6-terra'),
        // Analytics model for batch processing, continuous learning, and gap clustering
        'analytics_model' => env('OPENAI_ANALYTICS_MODEL', 'gpt-5.6-luna'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 4000),
        'temperature' => env('OPENAI_TEMPERATURE', 0.7),
        'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 30), // seconds
        // Response caching configuration for zero-latency / zero-cost repeated queries
        'cache_enabled' => env('OPENAI_CACHE_ENABLED', true),
        'cache_ttl' => env('OPENAI_CACHE_TTL', 3600), // Cache TTL in seconds (default 1 hour)
        // Embedding model for vector database
        'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
    ],

    'pinecone' => [
        'api_key' => env('PINECONE_API_KEY', 'pcsk_2EMYYN_F4gENYWvBMTnbh1RbprHKiym3P4aqNh2fRvF1LtyAAo5qPCdPr8ywBv7xnwpWPx'),
        'environment' => env('PINECONE_ENVIRONMENT', 'us-east-1-aws'),
        'index_name' => env('PINECONE_INDEX_NAME', 'bapenda-kb'),
        'dimension' => env('PINECONE_DIMENSION', 1536), // For text-embedding-3-small
        'metric' => env('PINECONE_METRIC', 'cosine'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY', '6Ld4LYQtAAAAACEQjznEQrI0x5v34bAZ49OvQleG'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', '6Ld4LYQtAAAAAOBSlpev0KYqTN84X363kAGXhIHL'),
        'enabled' => env('RECAPTCHA_ENABLED', true),
        'min_score' => env('RECAPTCHA_MIN_SCORE', 0.5),
    ],

    'circuit_breaker' => [
        'enabled' => env('AI_CIRCUIT_BREAKER_ENABLED', true),
        'max_failures' => env('AI_CIRCUIT_BREAKER_MAX_FAILURES', 3),
        'reset_timeout' => env('AI_CIRCUIT_BREAKER_RESET_TIMEOUT', 60), // seconds
        'rate_limit_ip' => env('AI_RATE_LIMIT_IP_PER_MINUTE', 30),
        'rate_limit_session' => env('AI_RATE_LIMIT_SESSION_PER_MINUTE', 15),
    ],

];
