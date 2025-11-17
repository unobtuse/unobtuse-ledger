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
 * FREE TIER: 1,500 requests per day with Gemini 1.5 Flash (fast and reliable)
 */
class StatementParserService
{
    protected string $apiKey;
    protected string $model = 'gemini-2.5-flash-lite-preview-06-17'; // Lite version - less traffic
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
     * @param int|null $statementYear Optional year to use for date parsing
     * @return array Parsed data including account info and transactions
     */
    public function parseStatement(string $filePath, string $fileType, ?int $statementYear = null): array
    {
        try {
            // Convert file to base64 for API
            $fileContent = Storage::get($filePath);
            $base64Content = base64_encode($fileContent);
            
            // Determine MIME type
            $mimeType = $this->getMimeType($fileType);
            
            // Create the prompt for AI (with user-provided year if available)
            $prompt = $this->buildParsingPrompt($statementYear);
            
            // Call Google Gemini API with retry logic
            $url = "{$this->apiUrl}/{$this->model}:generateContent?key={$this->apiKey}";
            
            $maxRetries = 3;
            $retryDelay = 2; // seconds
            $response = null;
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
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
                        'maxOutputTokens' => 16384, // Max for large statements with many transactions
                    ]
                ]);
                
                // If successful or non-retryable error, break
                if ($response->successful() || !in_array($response->status(), [503, 429, 500])) {
                    break;
                }
                
                // Log retry attempt
                if ($attempt < $maxRetries) {
                    Log::warning("Gemini API retry attempt {$attempt}/{$maxRetries}", [
                        'status' => $response->status(),
                        'attempt' => $attempt
                    ]);
                    sleep($retryDelay);
                    $retryDelay *= 2; // Exponential backoff
                }
            }
            
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
                Log::error('Failed to parse AI response', [
                    'content_length' => strlen($content),
                    'content_preview' => substr($content, 0, 500)
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Failed to parse AI response. Please try again or try a different file.'
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
    protected function buildParsingPrompt(?int $statementYear = null): string
    {
        $year = $statementYear ?? (int) date('Y');
        $currentMonth = date('n');
        
        return <<<PROMPT
Analyze this bank statement image and extract the following information in JSON format.

IMPORTANT DATE CONTEXT: The user selected year {$year}.
HOWEVER: If the document EXPLICITLY shows full years (e.g., '2022', '2023', '2024', '2025'), USE THE ACTUAL YEARS FROM THE DOCUMENT.
Only assume year {$year} for dates that don't include the year (e.g., 'Oct 15' or '10/15' without year).
For payment histories or documents spanning multiple years with explicit years shown, preserve those actual years.

{
  "account": {
    "institution_name": "Bank name (e.g., 'Chase', 'Bank of America')",
    "account_name": "Account name/type (e.g., 'Freedom Unlimited', 'Checking')",
    "account_number_last4": "Last 4 digits of account number",
    "account_type": "credit_card, checking, savings, loan, auto_loan, mortgage, student_loan, investment",
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
3. For screenshots: Extract ONLY transactions from the MOST RECENT 45 DAYS to avoid token limits
4. For PDF statements: Extract ALL transactions visible in the statement
5. Use the EXACT transaction descriptions as shown
6. Parse dates carefully in YYYY-MM-DD format
7. If a field is not visible or unclear, use null
8. Ensure all amounts are decimal numbers
9. Return ONLY valid JSON, no additional text

Return the JSON data now:
PROMPT;
    }
    
    /**
     * Parse AI response and extract structured data
     */
    protected function parseAIResponse(string $content): ?array
    {
        // Remove markdown code blocks if present (```json ... ```)
        $content = preg_replace('/```json\s*/s', '', $content);
        $content = preg_replace('/```\s*$/s', '', $content);
        
        // Try to find JSON in the response
        $jsonStart = strpos($content, '{');
        $jsonEnd = strrpos($content, '}');
        
        if ($jsonStart === false || $jsonEnd === false) {
            Log::error('No JSON found in AI response', [
                'content_preview' => substr($content, 0, 200)
            ]);
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
