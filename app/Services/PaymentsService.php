<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Payment;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Payments Service
 *
 * Handles payment initiation and management through Teller Payments API (BETA).
 * Supports ACH payments, transfers, and scheduled/recurring payments.
 */
class PaymentsService
{
    protected TellerService $tellerService;
    protected Client $client;
    protected string $apiUrl;

    public function __construct(TellerService $tellerService)
    {
        $this->tellerService = $tellerService;
        $this->apiUrl = 'https://api.teller.io';
        
        // Initialize client with mTLS (same as TellerService)
        $certificatePath = config('teller.certificate_path') ?? '';
        $privateKeyPath = config('teller.private_key_path') ?? '';
        
        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'cert' => $certificatePath,
            'ssl_key' => $privateKeyPath,
            'verify' => true,
            'headers' => [
                'Content-Type' => 'application/json',
                'Teller-Version' => '2020-10-12',
            ],
        ]);
    }

    /**
     * Create a payment
     *
     * @param Account $account Account to pay from
     * @param array $paymentData Payment details
     * @return array
     */
    public function createPayment(Account $account, array $paymentData): array
    {
        try {
            $accessToken = $account->teller_token;
            $accountId = $account->teller_account_id;

            if (!$accessToken || !$accountId) {
                throw new \RuntimeException('Account missing Teller credentials');
            }

            Log::info('Creating Teller payment', [
                'account_id' => $account->id,
                'teller_account_id' => $accountId,
                'amount' => $paymentData['amount'] ?? null,
            ]);

            // Prepare payment payload
            $payload = [
                'amount' => (string) ($paymentData['amount'] ?? 0),
                'recipient' => [
                    'name' => $paymentData['recipient_name'] ?? '',
                    'account_number' => $paymentData['recipient_account_number'] ?? '',
                    'routing_number' => $paymentData['recipient_routing_number'] ?? '',
                ],
            ];

            // Add optional fields
            if (isset($paymentData['memo'])) {
                $payload['memo'] = $paymentData['memo'];
            }

            if (isset($paymentData['scheduled_date'])) {
                $payload['scheduled_date'] = $paymentData['scheduled_date'];
            }

            // Create payment via Teller API
            $response = $this->client->post("/accounts/{$accountId}/payments", [
                'auth' => [$accessToken, ''],
                'json' => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            Log::info('Teller payment created', [
                'teller_payment_id' => $data['id'] ?? null,
                'status' => $data['status'] ?? null,
            ]);

            return [
                'success' => true,
                'payment' => $data,
                'teller_payment_id' => $data['id'] ?? null,
            ];
        } catch (GuzzleException $e) {
            Log::error('Failed to create Teller payment', [
                'account_id' => $account->id,
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
     * Get payment status
     *
     * @param Account $account
     * @param string $paymentId Teller payment ID
     * @return array
     */
    public function getPaymentStatus(Account $account, string $paymentId): array
    {
        try {
            $accessToken = $account->teller_token;
            $accountId = $account->teller_account_id;

            $response = $this->client->get("/accounts/{$accountId}/payments/{$paymentId}", [
                'auth' => [$accessToken, ''],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'payment' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('Failed to get Teller payment status', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * List payments for an account
     *
     * @param Account $account
     * @param array $options Query options
     * @return array
     */
    public function listPayments(Account $account, array $options = []): array
    {
        try {
            $accessToken = $account->teller_token;
            $accountId = $account->teller_account_id;

            $queryParams = [];
            if (isset($options['status'])) {
                $queryParams['status'] = $options['status'];
            }
            if (isset($options['limit'])) {
                $queryParams['limit'] = $options['limit'];
            }

            $response = $this->client->get("/accounts/{$accountId}/payments", [
                'auth' => [$accessToken, ''],
                'query' => $queryParams,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'payments' => $data['payments'] ?? [],
            ];
        } catch (GuzzleException $e) {
            Log::error('Failed to list Teller payments', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'payments' => [],
            ];
        }
    }

    /**
     * Cancel a pending payment
     *
     * @param Account $account
     * @param string $paymentId Teller payment ID
     * @return array
     */
    public function cancelPayment(Account $account, string $paymentId): array
    {
        try {
            $accessToken = $account->teller_token;
            $accountId = $account->teller_account_id;

            $response = $this->client->delete("/accounts/{$accountId}/payments/{$paymentId}", [
                'auth' => [$accessToken, ''],
            ]);

            return [
                'success' => true,
            ];
        } catch (GuzzleException $e) {
            Log::error('Failed to cancel Teller payment', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}


