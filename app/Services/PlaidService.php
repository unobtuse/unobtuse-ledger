<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Plaid Service
 * 
 * Handles all interactions with the Plaid API for bank account linking,
 * transaction syncing, and balance updates.
 */
class PlaidService
{
    protected Client $client;
    protected string $clientId;
    protected string $secret;
    protected string $environment;
    protected string $apiUrl;

    public function __construct()
    {
        $this->clientId = config('plaid.client_id');
        $this->secret = config('plaid.secret');
        $this->environment = config('plaid.environment', 'sandbox');
        $this->apiUrl = config('plaid.api_urls')[$this->environment];

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Create a Link token for initializing Plaid Link.
     *
     * @param User $user
     * @return array
     * @throws \Exception
     */
    public function createLinkToken(User $user): array
    {
        try {
            $response = $this->client->post('/link/token/create', [
                'json' => [
                    'client_id' => $this->clientId,
                    'secret' => $this->secret,
                    'user' => [
                        'client_user_id' => $user->id,
                    ],
                    'client_name' => config('app.name'),
                    'products' => config('plaid.products'),
                    'country_codes' => config('plaid.country_codes'),
                    'language' => 'en',
                    'webhook' => config('plaid.webhook_url'),
                    'redirect_uri' => config('plaid.redirect_uri'),
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'link_token' => $data['link_token'] ?? null,
                'expiration' => $data['expiration'] ?? null,
            ];
        } catch (GuzzleException $e) {
            Log::error('Plaid Link Token Creation Failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to create Plaid link token: ' . $e->getMessage());
        }
    }

    /**
     * Exchange public token for access token.
     *
     * @param string $publicToken
     * @return array
     * @throws \Exception
     */
    public function exchangePublicToken(string $publicToken): array
    {
        try {
            $response = $this->client->post('/item/public_token/exchange', [
                'json' => [
                    'client_id' => $this->clientId,
                    'secret' => $this->secret,
                    'public_token' => $publicToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'access_token' => $data['access_token'] ?? null,
                'item_id' => $data['item_id'] ?? null,
            ];
        } catch (GuzzleException $e) {
            Log::error('Plaid Token Exchange Failed', [
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to exchange public token: ' . $e->getMessage());
        }
    }

    /**
     * Get accounts for a given access token.
     *
     * @param string $accessToken
     * @return array
     * @throws \Exception
     */
    public function getAccounts(string $accessToken): array
    {
        try {
            $response = $this->client->post('/accounts/get', [
                'json' => [
                    'client_id' => $this->clientId,
                    'secret' => $this->secret,
                    'access_token' => $accessToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'accounts' => $data['accounts'] ?? [],
                'item' => $data['item'] ?? null,
            ];
        } catch (GuzzleException $e) {
            Log::error('Plaid Get Accounts Failed', [
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to get accounts: ' . $e->getMessage());
        }
    }

    /**
     * Get account balance.
     *
     * @param string $accessToken
     * @return array
     * @throws \Exception
     */
    public function getBalance(string $accessToken): array
    {
        try {
            $response = $this->client->post('/accounts/balance/get', [
                'json' => [
                    'client_id' => $this->clientId,
                    'secret' => $this->secret,
                    'access_token' => $accessToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['accounts'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('Plaid Get Balance Failed', [
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to get balance: ' . $e->getMessage());
        }
    }

    /**
     * Get transactions for a date range.
     *
     * @param string $accessToken
     * @param string $startDate Format: YYYY-MM-DD
     * @param string $endDate Format: YYYY-MM-DD
     * @return array
     * @throws \Exception
     */
    public function getTransactions(string $accessToken, string $startDate, string $endDate): array
    {
        try {
            $response = $this->client->post('/transactions/get', [
                'json' => [
                    'client_id' => $this->clientId,
                    'secret' => $this->secret,
                    'access_token' => $accessToken,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'options' => [
                        'count' => 500,
                        'offset' => 0,
                        'include_personal_finance_category' => true,
                    ],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'transactions' => $data['transactions'] ?? [],
                'accounts' => $data['accounts'] ?? [],
                'total_transactions' => $data['total_transactions'] ?? 0,
            ];
        } catch (GuzzleException $e) {
            Log::error('Plaid Get Transactions Failed', [
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to get transactions: ' . $e->getMessage());
        }
    }

    /**
     * Get institution details.
     *
     * @param string $institutionId
     * @return array|null
     * @throws \Exception
     */
    public function getInstitution(string $institutionId): ?array
    {
        try {
            $response = $this->client->post('/institutions/get_by_id', [
                'json' => [
                    'client_id' => $this->clientId,
                    'secret' => $this->secret,
                    'institution_id' => $institutionId,
                    'country_codes' => config('plaid.country_codes'),
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['institution'] ?? null;
        } catch (GuzzleException $e) {
            Log::error('Plaid Get Institution Failed', [
                'institution_id' => $institutionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Remove an item (disconnect account).
     *
     * @param string $accessToken
     * @return bool
     */
    public function removeItem(string $accessToken): bool
    {
        try {
            $this->client->post('/item/remove', [
                'json' => [
                    'client_id' => $this->clientId,
                    'secret' => $this->secret,
                    'access_token' => $accessToken,
                ],
            ]);

            return true;
        } catch (GuzzleException $e) {
            Log::error('Plaid Remove Item Failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get item status.
     *
     * @param string $accessToken
     * @return array|null
     */
    public function getItemStatus(string $accessToken): ?array
    {
        try {
            $response = $this->client->post('/item/get', [
                'json' => [
                    'client_id' => $this->clientId,
                    'secret' => $this->secret,
                    'access_token' => $accessToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['item'] ?? null;
        } catch (GuzzleException $e) {
            Log::error('Plaid Get Item Status Failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create a public token from an access token (for update mode).
     *
     * @param string $accessToken
     * @return string|null
     */
    public function createPublicToken(string $accessToken): ?string
    {
        try {
            $response = $this->client->post('/item/public_token/create', [
                'json' => [
                    'client_id' => $this->clientId,
                    'secret' => $this->secret,
                    'access_token' => $accessToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['public_token'] ?? null;
        } catch (GuzzleException $e) {
            Log::error('Plaid Create Public Token Failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}


