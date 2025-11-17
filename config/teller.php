<?php

declare(strict_types=1);

/**
 * Teller API Configuration
 *
 * Configuration for Teller API integration
 * Uses mTLS (mutual TLS) authentication with certificate files
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Teller Application ID
    |--------------------------------------------------------------------------
    |
    | Your unique Application ID from the Teller Dashboard.
    | Required for all API requests.
    |
    */
    'app_id' => env('TELLER_APP_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Teller Token Signing Key
    |--------------------------------------------------------------------------
    |
    | Used for signing access tokens. Available in Teller Dashboard > Applications.
    |
    */
    'token_signing_key' => env('TELLER_TOKEN_SIGNING_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | The Teller environment to use.
    | Options: 'sandbox' or 'production'
    |
    | Sandbox: Use for testing with test bank credentials
    | Production: Use for real bank connections
    |
    */
    'environment' => env('TELLER_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Certificate Path
    |--------------------------------------------------------------------------
    |
    | Path to the Teller certificate file (certificate.pem).
    | Used for mTLS authentication with Teller API.
    | Extract from teller.zip provided during account setup.
    |
    */
    'certificate_path' => base_path('.teller/certificate.pem'),

    /*
    |--------------------------------------------------------------------------
    | Private Key Path
    |--------------------------------------------------------------------------
    |
    | Path to the Teller private key file (private_key.pem).
    | Used for mTLS authentication with Teller API.
    | Extract from teller.zip provided during account setup.
    |
    */
    'private_key_path' => base_path('.teller/private_key.pem'),

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | Teller API endpoint URLs
    |
    */
    'api_url' => 'https://api.teller.io',

    /*
    |--------------------------------------------------------------------------
    | Teller Connect Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Teller Connect widget
    |
    */
    'connect' => [
        'url' => 'https://connect.teller.io/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Products/Features to Enable
    |--------------------------------------------------------------------------
    |
    | Which Teller products to enable for this application.
    | Options: 'accounts', 'balances', 'transactions', 'identity', 'payments'
    |
    */
    'products' => explode(',', env('TELLER_PRODUCTS', 'accounts,balances,transactions,identity')),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Webhook URL for real-time updates from Teller
    |
    */
    'webhook_url' => env('TELLER_WEBHOOK_URL', null),
];

