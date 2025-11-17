<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Statement Parser Service
 * 
 * Uses AI vision (Google Gemini 1.5 Flash) to parse bank statements from PDFs or images
 * and extract account information and transactions.
 * 
 * FREE TIER: 1,500 requests per day with Gemini 1.5 Flash
 */
class StatementParserService
{
    protected string $apiKey;
    protected string $model = 'gemini-1.5-flash'; // Free tier with vision
    protected string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
    
    public function __construct()
    {
        $this->apiKey = config('services.google.gemini_api_key');
    }
    
    /**
     * Parse a bank statement file and extract account and transaction data
     *
     * @param string $filePath Path to the uploaded file
     * @param string $fileType Type of file (pdf, jpg, png, etc.)
     * @return array Parsed data including account info and transactions
     */
    public function parseStatement(string $filePath, string $fileType): array
    {
        try {
            // Convert file to base64 for API
            $fileContent = Storage::get($filePath);
            $base64Content = base64_encode($fileContent);
            
            // Determine MIME type
            $mimeType = $this->getMimeType($fileType);
            
            // Create the prompt for AI
            $prompt = $this->buildParsingPrompt();
            
            // Call Google Gemini API
            $url = "{$this->apiUrl}/{$this->model}:generateContent?key={$this->apiKey}";
            
            $response = Http::timeout(120)->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt
                            ],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64Content
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'topK' => 1,
                    'topP' => 1,
                    'maxOutputTokens' => 4096,
                ]
            ]);
            
            if (!$response->successful()) {
                Log::error('Google Gemini API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Failed to process statement. Please try again.'
                ];
            }
            
            $result = $response->json();
            $content = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
            
            if (!$content) {
                return [
                    'success' => false,
                    'error' => 'No content returned from AI'
                ];
            }
            
            // Parse the JSON response from AI
            $parsedData = $this->parseAIResponse($content);
            
            if (!$parsedData) {
                return [
                    'success' => false,
                    'error' => 'Failed to parse AI response'
                ];
            }
            
            return [
                'success' => true,
                'data' => $parsedData
            ];
            
        } catch (\Exception $e) {
            Log::error('Statement parsing failed', [
                'error' => $e->getMessage(),
                'file' => $filePath
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Build the parsing prompt for AI
     */
    protected function buildParsingPrompt(): string
    {
        return <<<PROMPT
Analyze this bank statement image and extract the following information in JSON format:

{
  "account": {
    "institution_name": "Bank name (e.g., 'Chase', 'Bank of America')",
    "account_name": "Account name/type (e.g., 'Freedom Unlimited', 'Checking')",
    "account_number_last4": "Last 4 digits of account number",
    "account_type": "credit_card, checking, savings, etc.",
    "statement_period_start": "YYYY-MM-DD",
    "statement_period_end": "YYYY-MM-DD",
    "ending_balance": 0.00,
    "available_balance": 0.00,
    "credit_limit": 0.00,
    "currency": "USD"
  },
  "transactions": [
    {
      "date": "YYYY-MM-DD",
      "description": "Merchant/transaction description",
      "amount": 0.00,
      "type": "debit or credit"
    }
  ]
}

IMPORTANT RULES:
1. For credit cards: amount should be POSITIVE for purchases (debits) and NEGATIVE for payments/credits
2. For checking/savings: amount should be POSITIVE for withdrawals and NEGATIVE for deposits
3. Extract ALL transactions visible in the statement
4. Use the EXACT transaction descriptions as shown
5. Parse dates carefully in YYYY-MM-DD format
6. If a field is not visible or unclear, use null
7. Ensure all amounts are decimal numbers
8. Return ONLY valid JSON, no additional text

Return the JSON data now:
PROMPT;
    }
    
    /**
     * Parse AI response and extract structured data
     */
    protected function parseAIResponse(string $content): ?array
    {
        // Try to find JSON in the response
        $jsonStart = strpos($content, '{');
        $jsonEnd = strrpos($content, '}');
        
        if ($jsonStart === false || $jsonEnd === false) {
            return null;
        }
        
        $jsonString = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
        $data = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON parsing error', [
                'error' => json_last_error_msg(),
                'content' => $content
            ]);
            return null;
        }
        
        // Validate required fields
        if (!isset($data['account']) || !isset($data['transactions'])) {
            return null;
        }
        
        return $data;
    }
    
    /**
     * Get MIME type for file
     */
    protected function getMimeType(string $fileType): string
    {
        return match(strtolower($fileType)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream'
        };
    }
    
    /**
     * Validate parsed account data
     */
    public function validateAccountData(array $accountData): array
    {
        $errors = [];
        
        if (empty($accountData['institution_name'])) {
            $errors[] = 'Institution name is required';
        }
        
        if (empty($accountData['account_name'])) {
            $errors[] = 'Account name is required';
        }
        
        if (empty($accountData['account_type'])) {
            $errors[] = 'Account type is required';
        }
        
        if (!isset($accountData['ending_balance'])) {
            $errors[] = 'Ending balance is required';
        }
        
        return $errors;
    }
    
    /**
     * Validate parsed transactions
     */
    public function validateTransactions(array $transactions): array
    {
        $errors = [];
        
        foreach ($transactions as $index => $transaction) {
            if (empty($transaction['date'])) {
                $errors[] = "Transaction #{$index}: Date is required";
            }
            
            if (empty($transaction['description'])) {
                $errors[] = "Transaction #{$index}: Description is required";
            }
            
            if (!isset($transaction['amount'])) {
                $errors[] = "Transaction #{$index}: Amount is required";
            }
        }
        
        return $errors;
    }
}
