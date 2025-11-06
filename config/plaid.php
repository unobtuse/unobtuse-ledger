<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Plaid API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Plaid API integration for bank account linking
    | and transaction synchronization.
    |
    */

    'client_id' => env('PLAID_CLIENT_ID'),

    'secret' => env('PLAID_SECRET'),

    'environment' => env('PLAID_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | API URLs
    |--------------------------------------------------------------------------
    */

    'api_urls' => [
        'sandbox' => 'https://sandbox.plaid.com',
        'development' => 'https://development.plaid.com',
        'production' => 'https://production.plaid.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Plaid Link Configuration
    |--------------------------------------------------------------------------
    */

    'webhook_url' => env('PLAID_WEBHOOK_URL'),

    'redirect_uri' => env('PLAID_REDIRECT_URI'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Verification
    |--------------------------------------------------------------------------
    |
    | Configuration for verifying Plaid webhook signatures.
    | CRITICAL: Never disable in production - this ensures webhooks are authentic.
    |
    */

    'webhook_verification_enabled' => env('PLAID_WEBHOOK_VERIFICATION_ENABLED', true),

    'webhook_verification_key_url' => env('PLAID_WEBHOOK_VERIFICATION_KEY_URL'),

    /*
    |--------------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------------
    |
    | Plaid products to enable for Link initialization
    |
    */

    'products' => [
        'transactions',   // Transaction history
        'liabilities',   // Liability data (due dates, interest rates, payment amounts)
    ],

    /*
    |--------------------------------------------------------------------------
    | Country Codes
    |--------------------------------------------------------------------------
    */

    'country_codes' => ['US', 'CA'],

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'transaction_days' => 730, // 2 years of historical data
        'sync_interval' => 6, // hours between syncs
    ],

];


