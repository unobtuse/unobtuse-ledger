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
            $response = $this->client->get('/accounts', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'accounts' => $data['accounts'] ?? [],
            ];
        } catch (GuzzleException $e) {
            Log::error('Failed to get Teller accounts', [
                'error' => $e->getMessage(),
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
            $response = $this->client->get("/accounts/{$accountId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
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
            $response = $this->client->get("/accounts/{$accountId}/balances", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

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

            $response = $this->client->get("/accounts/{$accountId}/transactions", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => $queryParams,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'transactions' => $data['transactions'] ?? [],
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
            $response = $this->client->get('/identity', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
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
     * Disconnect/revoke an account access token
     *
     * @param string $accessToken
     * @return array
     */
    public function disconnect(string $accessToken): array
    {
        try {
            $this->client->delete('/accounts', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
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
}

