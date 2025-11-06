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
    | Products
    |--------------------------------------------------------------------------
    |
    | Plaid products to enable for Link initialization
    |
    */

    'products' => [
        'auth',           // Account authentication
        'transactions',   // Transaction history
        'identity',       // Account holder identity
        'balance',        // Real-time balance
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


