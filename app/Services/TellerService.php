<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Teller Service
 *
 * Handles all interactions with the Teller API for bank account linking,
 * transaction syncing, and balance updates.
 *
 * Teller uses mTLS (mutual TLS) for authentication, requiring:
 * - Certificate file (certificate.pem)
 * - Private key file (private_key.pem)
 * - Application ID
 * - Token Signing Key
 */
class TellerService
{
    protected Client $client;
    protected string $appId;
    protected string $tokenSigningKey;
    protected string $environment;
    protected string $apiUrl;
    protected string $certificatePath;
    protected string $privateKeyPath;

    public function __construct()
    {
        $this->appId = config('teller.app_id') ?? '';
        $this->tokenSigningKey = config('teller.token_signing_key') ?? '';
        $this->environment = config('teller.environment', 'sandbox');
        $this->certificatePath = config('teller.certificate_path') ?? '';
        $this->privateKeyPath = config('teller.private_key_path') ?? '';

        if (empty($this->appId)) {
            throw new \RuntimeException('Teller Application ID is not configured. Set TELLER_APP_ID in .env');
        }

        if (empty($this->tokenSigningKey)) {
            throw new \RuntimeException('Teller Token Signing Key is not configured. Set TELLER_TOKEN_SIGNING_KEY in .env');
        }

        if (empty($this->certificatePath) || !file_exists($this->certificatePath)) {
            throw new \RuntimeException('Teller certificate file not found at ' . $this->certificatePath);
        }

        if (empty($this->privateKeyPath) || !file_exists($this->privateKeyPath)) {
            throw new \RuntimeException('Teller private key file not found at ' . $this->privateKeyPath);
        }

        $this->apiUrl = 'https://api.teller.io';

        // Initialize Guzzle client with mTLS certificate authentication
        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'cert' => $this->certificatePath,      // Certificate file path
            'ssl_key' => $this->privateKeyPath,    // Private key file path
            'verify' => true,                       // Verify SSL certificates
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Teller-Version' => '2020-10-12',
            ],
        ]);
    }

    /**
     * Initialize Teller Connect by returning configuration for frontend
     * The frontend will use this to initialize the Teller Connect widget
     *
     * @param User $user
     * @return array
     */
    public function initializeTellerConnect(User $user): array
    {
        return [
            'appId' => $this->appId,
            'environment' => $this->environment,
            'userId' => (string) $user->id,
            'userName' => $user->name ?? 'User',
            'userEmail' => $user->email,
        ];
    }

    /**
     * Exchange enrollment token from Teller Connect for access token
     *
     * @param string $enrollmentToken
     * @return array
     * @throws \Exception
     */
    public function exchangeToken(string $enrollmentToken): array
    {
        try {
            Log::info('Exchanging Teller enrollment token', ['token' => substr($enrollmentToken, 0, 10)]);

            $response = $this->client->post('/accounts', [
                'form_params' => [
                    'enrollment_id' => $enrollmentToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            Log::info('Successfully exchanged Teller token', ['account_id' => $data['id'] ?? null]);

            return [
                'success' => true,
                'accessToken' => $data['access_token'] ?? null,
                'accountId' => $data['id'] ?? null,
                'enrollment' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('Failed to exchange Teller token', [
                'error' => $e->getMessage(),
                'response' => $e->getResponse()?->getBody()->getContents(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all accounts for a user
     *
     * @param string $accessToken
     * @return array
     * @throws \Exception
     */
    public function getAccounts(string $accessToken): array
    {
        try {
            Log::info('Calling Teller API /accounts', [
                'token_prefix' => substr($accessToken, 0, 15),
                'environment' => $this->environment,
                'certificate_exists' => file_exists($this->certificatePath),
                'key_exists' => file_exists($this->privateKeyPath),
            ]);

            // Teller requires BOTH mTLS certificates AND access token!
            // The certificate authenticates the APPLICATION
            // The access token (Basic Auth) authenticates the USER
            $response = $this->client->get('/accounts', [
                'auth' => [$accessToken, ''],
            ]);

            $body = $response->getBody()->getContents();
            Log::channel('teller')->info('TELLER API /accounts RESPONSE', [
                'status_code' => $response->getStatusCode(),
                'full_response' => $body,
            ]);

            $data = json_decode($body, true);
            Log::info('Teller API response decoded', [
                'is_array' => is_array($data),
                'keys' => is_array($data) ? array_keys($data) : 'not-array',
                'count' => is_array($data) ? count($data) : 0,
                'structure' => $this->describeStructure($data),
            ]);

            // Teller returns an ARRAY of accounts directly, not wrapped in 'accounts' key
            $accounts = is_array($data) && !isset($data['accounts']) ? $data : ($data['accounts'] ?? []);

            return [
                'success' => true,
                'accounts' => $accounts,
            ];
        } catch (GuzzleException $e) {
            Log::error('Failed to get Teller accounts', [
                'error' => $e->getMessage(),
                'status_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : 'no-response',
                'response_body' => $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'no-body',
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'accounts' => [],
            ];
        }
    }

    /**
     * Get account details including balance
     *
     * @param string $accessToken
     * @param string $accountId
     * @return array
     * @throws \Exception
     */
    public function getAccountDetails(string $accessToken, string $accountId): array
    {
        try {
            // Teller requires BOTH mTLS certificates AND access token
            $response = $this->client->get("/accounts/{$accountId}", [
                'auth' => [$accessToken, ''],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'account' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('Failed to get Teller account details', [
                'error' => $e->getMessage(),
                'account_id' => $accountId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get account balance
     *
     * @param string $accessToken
     * @param string $accountId
     * @return array
     * @throws \Exception
     */
    public function getBalance(string $accessToken, string $accountId): array
    {
        try {
            // Teller requires BOTH mTLS certificates AND access token
            $response = $this->client->get("/accounts/{$accountId}/balances", [
                'auth' => [$accessToken, ''],
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            // Log complete balance response for debugging
            Log::channel('teller')->info('TELLER API /balances RESPONSE', [
                'account_id' => $accountId,
                'full_response' => $body,
            ]);

            // Log balance structure for debugging credit card fields
            Log::info('Teller balance response', [
                'account_id' => $accountId,
                'balance_keys' => is_array($data) ? array_keys($data) : 'not-array',
                'structure' => $this->describeStructure($data),
            ]);

            return [
                'success' => true,
                'balance' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('Failed to get Teller balance', [
                'error' => $e->getMessage(),
                'account_id' => $accountId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get transactions for an account
     *
     * @param string $accessToken
     * @param string $accountId
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function getTransactions(string $accessToken, string $accountId, array $options = []): array
    {
        try {
            $queryParams = [];

            // Add optional filters
            if (isset($options['count'])) {
                $queryParams['count'] = $options['count'];
            }
            if (isset($options['from_id'])) {
                $queryParams['from_id'] = $options['from_id'];
            }

            // Teller requires BOTH mTLS certificates AND access token
            $response = $this->client->get("/accounts/{$accountId}/transactions", [
                'auth' => [$accessToken, ''],
                'query' => $queryParams,
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            // Log full transactions response for debugging
            Log::channel('teller')->info('TELLER API /transactions RESPONSE', [
                'account_id' => $accountId,
                'full_response' => $body,
            ]);

            // Teller returns transactions as a direct array, not nested under 'transactions' key
            $transactions = is_array($data) && isset($data[0]) ? $data : ($data['transactions'] ?? []);

            Log::info('Teller transactions response', [
                'account_id' => $accountId,
                'transaction_count' => count($transactions),
                'structure' => $this->describeStructure($data),
            ]);

            return [
                'success' => true,
                'transactions' => $transactions,
            ];
        } catch (GuzzleException $e) {
            Log::error('Failed to get Teller transactions', [
                'error' => $e->getMessage(),
                'account_id' => $accountId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'transactions' => [],
            ];
        }
    }

    /**
     * Get identity information for the user
     *
     * @param string $accessToken
     * @return array
     * @throws \Exception
     */
    public function getIdentity(string $accessToken): array
    {
        try {
            // Teller requires BOTH mTLS certificates AND access token
            $response = $this->client->get('/identity', [
                'auth' => [$accessToken, ''],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'identity' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('Failed to get Teller identity', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get institution details
     *
     * @param string $institutionId Teller institution ID (e.g., 'capital_one')
     * @return array
     */
    public function getInstitution(string $institutionId): array
    {
        try {
            // Note: Teller Institutions API may require access token or be public
            // For now, we'll try without auth first (if it fails, we'll add auth)
            $response = $this->client->get("/institutions/{$institutionId}", [
                'auth' => ['', ''], // Empty auth - institutions may be public
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            Log::info('Teller institution fetched', [
                'institution_id' => $institutionId,
                'has_data' => !empty($data),
            ]);

            return [
                'success' => true,
                'institution' => $data,
            ];
        } catch (GuzzleException $e) {
            // If unauthorized, try with mTLS only (no access token needed)
            try {
                $response = $this->client->get("/institutions/{$institutionId}");

                $data = json_decode($response->getBody()->getContents(), true);

                return [
                    'success' => true,
                    'institution' => $data,
                ];
            } catch (GuzzleException $e2) {
                Log::error('Failed to get Teller institution', [
                    'error' => $e2->getMessage(),
                    'institution_id' => $institutionId,
                ]);

                return [
                    'success' => false,
                    'error' => $e2->getMessage(),
                ];
            }
        }
    }

    /**
     * Disconnect/revoke an account access token
     *
     * @param string $accessToken
     * @return array
     */
    public function disconnect(string $accessToken): array
    {
        try {
            // Teller requires BOTH mTLS certificates AND access token
            $this->client->delete('/accounts', [
                'auth' => [$accessToken, ''],
            ]);

            Log::info('Successfully disconnected Teller account');

            return [
                'success' => true,
            ];
        } catch (GuzzleException $e) {
            Log::error('Failed to disconnect Teller account', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract balance data based on account type
     *
     * For DEBIT accounts (checking/savings):
     * - balance = available funds (most important)
     *
     * For CREDIT accounts (credit cards):
     * - balance = amount owed (current balance on the card)
     * - available_balance = available credit (unused credit limit)
     *
     * @param array $balanceData Raw balance data from Teller API
     * @param string $accountType Account type (checking, savings, credit_card, etc.)
     * @return array Extracted balance data for database storage
     */
    public function extractBalanceForAccountType(array $balanceData, string $accountType): array
    {
        $extracted = [];

        if ($accountType === 'credit_card' || $accountType === 'credit') {
            // For credit cards, the "balance" is the amount owed (current balance)
            // This is typically in the 'ledger' field or 'current' field
            $extracted['balance'] = $balanceData['ledger'] ?? $balanceData['current'] ?? 0;

            // The "available_balance" is the available credit (unused limit)
            // This is typically in the 'available' field
            $extracted['available_balance'] = $balanceData['available'] ?? null;

            // Extract credit card specific fields if present
            if (isset($balanceData['credit_limit'])) {
                $extracted['credit_limit'] = $balanceData['credit_limit'];
            }
            if (isset($balanceData['payment_due_date'])) {
                $extracted['payment_due_date'] = $balanceData['payment_due_date'];
            }
            if (isset($balanceData['minimum_payment_amount'])) {
                $extracted['minimum_payment_amount'] = $balanceData['minimum_payment_amount'];
            }
            if (isset($balanceData['interest_rate'])) {
                $extracted['interest_rate'] = $balanceData['interest_rate'];
            }
        } else {
            // For debit accounts (checking/savings), the "balance" is what's available
            // This is typically in the 'available' field, with 'ledger' as fallback
            $extracted['balance'] = $balanceData['available'] ?? $balanceData['ledger'] ?? 0;

            // No 'available_balance' concept for debit accounts
            $extracted['available_balance'] = null;
        }

        return $extracted;
    }

    /**
     * Helper method to describe data structure for logging
     * 
     * @param mixed $data
     * @return string
     */
    private function describeStructure($data): string
    {
        if (is_array($data)) {
            $type = 'array';
            $content = 'keys: ' . implode(', ', array_slice(array_keys($data), 0, 5));
            if (count($data) > 5) {
                $content .= ', ...';
            }
            return "$type($content)";
        } elseif (is_object($data)) {
            return 'object: ' . get_class($data);
        } elseif (is_string($data)) {
            return 'string: ' . substr($data, 0, 50);
        } elseif (is_numeric($data)) {
            return 'number: ' . $data;
        } elseif (is_bool($data)) {
            return 'bool: ' . ($data ? 'true' : 'false');
        } elseif (is_null($data)) {
            return 'null';
        } else {
            return 'unknown: ' . gettype($data);
        }
    }
}

