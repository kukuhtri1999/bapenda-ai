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
        // Default chat model to gpt-5-mini; override with OPENAI_MODEL in .env
        'model' => env('OPENAI_MODEL', 'gpt-5-mini'),
        // Analytics model can be overridden separately; defaults set in service code
        'analytics_model' => env('OPENAI_ANALYTICS_MODEL', 'gpt-5-mini'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 4000),
        'temperature' => env('OPENAI_TEMPERATURE', 0.7),
        'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 30), // seconds
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

];
