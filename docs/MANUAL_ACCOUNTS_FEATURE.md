# Manual Account Management with AI-Powered Statement Parsing

## Overview

The Manual Account Management feature enables users to upload bank statements, credit card statements, and loan payment histories via PDF or image files. Using Google Gemini Vision API (free tier), the system automatically extracts account details and transaction data, enabling comprehensive tracking of accounts that aren't supported by automated bank connections like Teller or Plaid.

---

## Key Features

### 🤖 AI-Powered Document Parsing
- **Google Gemini 2.5 Flash Vision API** (free - 1,500 requests/day)
- Supports **PDF and image formats** (JPG, PNG, GIF, WebP)
- Extracts account details and transaction history automatically
- Handles both monthly statements and payment histories

### 📊 Account Types Supported
- **Checking & Savings Accounts**
- **Credit Cards** (with utilization tracking)
- **Personal Loans**
- **Auto Loans** 🚗
- **Mortgages** 🏠
- **Student Loans** 🎓
- **Investment Accounts**

### 🎯 Smart Features

#### 1. Year-Accurate Date Parsing
- **Year selector** (current year through 5 years back)
- Smart detection: Uses explicit years from documents when shown
- Fallback: Applies selected year when year not visible (screenshots)
- Perfect for uploading historical statements

#### 2. Duplicate Detection
- Automatically detects duplicate transactions when updating existing accounts
- Visual indicators: Yellow background, strikethrough text, ⚠️ icons
- Shows count: "⚠️ Duplicates Found (X)"
- Duplicates automatically skipped on save
- Matching logic: Date + Amount

#### 3. Smart Loan Payment Cross-Referencing
- **Problem**: Loan statements show DUE dates, not actual payment dates
- **Solution**: Cross-references with all user accounts to find actual payments
- Searches within ±7 days for matching payment amounts
- Visual indicators:
  - Blue left border on suggested transactions
  - "💡 Actual: Oct 9, 2025" - Shows actual payment date
  - "Found in: Varo Bank - Varo Checking" - Shows source account
  - **"Use Actual Date" button** - One-click to accept suggestion
- Works for: Personal loans, auto loans, mortgages, student loans

#### 4. Comprehensive Loan Tracking
Track complete loan details via account card dropdown menu:
- **Initial Loan Amount** * (required) - Original principal
- **Interest Rate** (optional) - Annual APR percentage
- **Loan Term** (optional) - Term in months
- **First Payment Date** (optional) - When payments started
- **Origination Fees** (optional) - Upfront costs/closing fees

#### 5. Visual Progress Bars

**Credit Cards - Utilization Tracking:**
- Shows: Usage vs Credit Limit
- Color coding:
  - 🟢 Green: < 30% (excellent for credit score)
  - 🟡 Yellow: 30-70% (ok)
  - 🔴 Red: > 70% (high utilization, risky)
- Display: "$1,292.93 of $5,000.00 limit"

**Loans - Paydown Progress:**
- Shows: Amount paid vs Initial loan amount
- Color: 🔵 Blue (consistent, positive progress)
- Display: "$10,379.73 paid of $23,060.46 original"
- Percentage: "45% Paid Off"

#### 6. Intelligent Type-Based Grouping
Default display groups accounts by financial category:
1. **Checking & Savings** - All cash accounts
2. **Loans** - All debt (personal, auto, mortgage, student)
3. **Credit Cards** - Revolving credit
4. **Investments** - Brokerage, retirement
5. **Other** - Everything else

Filter enhancement: "Loans" filter shows ALL loan types together

---

## User Workflow

### Adding a New Manual Account

1. **Click "Add Manual Account"** (secondary button on accounts page)
2. **Select Year** - Choose statement year (defaults to current year)
3. **Upload File** - Drag & drop or click to upload (PDF/image, max 10MB)
4. **AI Processing** - Gemini extracts account + transaction data
5. **Review & Edit** - Modal shows extracted data:
   - Edit account details (institution, name, type, last 4 digits)
   - Edit balances (current/ending, available, credit limit)
   - Review transactions table
   - Remove incorrect extractions (click ×)
   - Accept suggested dates for loan payments (click "Use Actual Date")
6. **Save** - Creates account and imports all transactions

### Updating an Existing Manual Account

1. **Click "📄 Upload Update"** button on manual account card
2. Account details **automatically pre-filled**
3. Upload new statement
4. Review with **duplicate detection**:
   - Existing transactions shown with yellow warning
   - Only new transactions imported
5. Success message: "Account updated with X new transactions!"

### Setting Loan Details

1. **Click ⋮** (menu) on any loan account card
2. **Click "Set Initial Loan Amount"**
3. Fill in loan details:
   - Initial Loan Amount (required)
   - Interest Rate (optional)
   - Loan Term in months (optional)
   - First Payment Date (optional)
   - Origination Fees (optional)
4. **Click Save**
5. Progress bar appears showing paydown percentage

---

## Technical Implementation

### Database Schema

**Accounts Table - New Columns:**
```sql
is_manual BOOLEAN DEFAULT FALSE
statement_file_path VARCHAR(255) NULLABLE
initial_loan_amount DECIMAL(12,2) NULLABLE
loan_interest_rate DECIMAL(5,2) NULLABLE
loan_term_months INTEGER NULLABLE
loan_first_payment_date DATE NULLABLE
loan_origination_fees DECIMAL(10,2) NULLABLE
```

**Account Types Enum Updated:**
```sql
account_type CHECK IN (
  'checking', 'savings', 'credit_card', 'investment', 
  'loan', 'auto_loan', 'mortgage', 'student_loan', 'other'
)
```

**Transactions Table:**
```sql
is_manual BOOLEAN DEFAULT FALSE
```

### Key Components

**Backend:**
- `app/Services/StatementParserService.php` - Google Gemini API integration
- `app/Livewire/Accounts/ManualAccountUpload.php` - Upload workflow component
- `app/Livewire/Accounts/AccountsList.php` - Account management with loan modals

**Frontend:**
- `resources/views/livewire/accounts/manual-account-upload.blade.php` - Upload modal UI
- `resources/views/livewire/accounts/accounts-list.blade.php` - Main accounts page
- `resources/views/livewire/accounts/partials/account-card.blade.php` - Account card with progress bars

### API Configuration

**Google Gemini Setup:**
```php
// config/services.php
'google' => [
    'gemini_api_key' => env('GOOGLE_GEMINI_API_KEY'),
],
```

**Environment Variable:**
```
GOOGLE_GEMINI_API_KEY=your_key_here
```

**Free Tier Limits:**
- Model: `gemini-2.0-flash-exp` or `gemini-1.5-flash`
- Requests: 1,500 per day (free)
- Max tokens: 16,384 output

### Error Handling

**Retry Logic:**
- 3 attempts with exponential backoff
- Handles: 503 (overloaded), 429 (rate limit), 500 (server error)
- Wait times: 2s, 4s, 8s

**Response Processing:**
- Strips markdown code blocks from JSON responses
- Validates JSON structure
- Handles malformed AI responses gracefully

---

## Use Cases

### 1. Accounts Not Supported by Teller/Plaid
Upload statements from banks and credit unions that don't support API connections.

**Example:** MercuryCards credit card uploaded via October statement
- ✅ Institution: MercuryCards
- ✅ Last 4: 8048
- ✅ Balance: $1,292.93
- ✅ Available: $3,707.07
- ✅ Credit Limit: $5,000.00
- ✅ 5 transactions imported

### 2. Historical Data Import
Upload old statements to build transaction history before automated connections.

**Example:** October 2025 Varo Bank statement
- ✅ 71 transactions imported from PDF
- ✅ November update: 43 duplicates detected and skipped
- ✅ Complete October-November history

### 3. Loan Account Management
Track loans with complete origination and paydown details.

**Example:** Best Egg Personal Loan
- ✅ Current balance tracked
- ✅ AI extracted payment due dates
- ✅ Cross-referenced with Varo checking for actual payment dates
- ✅ Oct 8 due date → Oct 9 actual payment found automatically

**Example:** TD Auto Finance
- ✅ Payment history spanning 2022-2025 imported
- ✅ Complete payment timeline preserved
- ✅ Multi-year historical data accurate
- ✅ NSF fees tracked in loan account only

### 4. Multi-Year Payment Histories
Upload payment history PDFs that span multiple years.

**Example:** TD Auto Finance payment history (2022-2025)
- ✅ Explicit years preserved from document
- ✅ Selected year only used when year not visible
- ✅ 53 transactions spanning 3+ years imported correctly

---

## Advantages Over Automated Connections

### ✅ Universal Coverage
- Works with ANY bank, credit union, or lender
- No API integration required
- No third-party data sharing concerns

### ✅ Historical Data
- Upload statements from any time period
- Build complete financial history
- Year-accurate date parsing

### ✅ Loan Tracking
- Complete loan lifecycle tracking
- Cross-reference actual payment dates
- Track fees, interest rates, paydown progress

### ✅ Free & Private
- Google Gemini free tier (1,500 requests/day)
- Documents processed temporarily
- No ongoing subscription costs
- No data sharing with aggregators

### ✅ Flexible
- Upload updates anytime
- Works with screenshots and PDFs
- Edit data before saving
- Remove incorrect extractions

---

## Best Practices

### For Accurate Parsing

1. **Use clear, high-quality images**
   - Screenshots work great for recent statements
   - PDFs preferred for official statements
   - Ensure text is legible

2. **Select correct year**
   - Defaults to current year
   - Change for historical statements
   - System will use explicit years from documents when shown

3. **Review before saving**
   - Check extracted account details
   - Verify transaction amounts and dates
   - Remove any incorrectly parsed data
   - Accept suggested dates for loan payments

4. **For large statements**
   - System limits screenshot parsing to recent 45 days (to avoid token limits)
   - Use PDFs for complete statement history

### For Loan Accounts

1. **Set initial loan amount immediately** after creating account
2. **Use cross-referencing** for accurate payment dates:
   - Upload loan statement
   - System finds actual payment in checking account
   - Click "Use Actual Date" to accept
3. **Add optional fields** for complete tracking:
   - Interest rate for APR calculations
   - Loan term for payoff timeline
   - First payment date for schedule tracking
   - Origination fees for true cost analysis

### For Regular Updates

1. **Use "Upload Update" button** on existing manual accounts
2. Duplicate detection keeps data clean
3. Only new transactions imported
4. Balances updated to latest values

---

## Limitations & Considerations

### AI Parsing Accuracy
- **Generally excellent** for standard bank statements
- May struggle with: Non-standard formats, handwritten notes, low-quality images
- **Always review** extracted data before saving
- Can edit any incorrect extractions in review modal

### Token Limits
- Large statements (100+ transactions) may hit token limits
- System limits screenshots to recent 45 days
- Use multiple smaller uploads if needed

### Due Dates vs Payment Dates
- Loan statements show **DUE dates** by default
- Use **cross-referencing** to get actual payment dates
- Manual review available in modal before saving

### Progress Bars
- **Credit Cards**: Require credit_limit (usually auto-populated)
- **Loans**: Require initial_loan_amount (set via menu option)
- Won't display until required data is set

---

## Future Enhancements

### Potential Features
- Automatic account matching based on last 4 digits
- Batch statement uploads
- Statement history storage and re-processing
- Transaction editing in review modal
- Multi-page PDF support
- Receipt/invoice parsing for expense tracking
- Tax document extraction (1099s, W-2s)
- Investment account statement parsing
- Paycheck stub parsing

### Calculation Features
- Monthly payment calculator
- Total interest paid
- Loan amortization schedules
- Early payoff savings calculator
- Debt snowball/avalanche recommendations
- Credit score impact projections

---

## Technical Details

### Google Gemini API

**Model:** `gemini-2.0-flash-exp` or `gemini-1.5-flash`

**Request Structure:**
```php
POST https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent
Content-Type: application/json

{
  "contents": [{
    "parts": [
      {"text": "AI parsing prompt..."},
      {
        "inline_data": {
          "mime_type": "image/jpeg|application/pdf",
          "data": "base64_encoded_file"
        }
      }
    ]
  }],
  "generationConfig": {
    "maxOutputTokens": 16384,
    "temperature": 0.1
  }
}
```

**Response Processing:**
1. Extract JSON from AI response
2. Strip markdown code blocks if present
3. Validate structure
4. Parse account and transaction data

### Duplicate Detection Algorithm

```php
// Match on: date + amount within same account
$duplicate = Transaction::where('account_id', $accountId)
    ->whereDate('transaction_date', $parsedDate)
    ->where('amount', $parsedAmount)
    ->exists();
```

### Cross-Reference Algorithm

```php
// Find matching payment within ±7 days
$matchingPayment = Transaction::whereIn('account_id', $otherAccounts)
    ->whereBetween('transaction_date', [
        $dueDate->subDays(7),
        $dueDate->addDays(7)
    ])
    ->where('amount', $matchingAmount)
    ->first();
```

### Progress Bar Calculations

**Credit Card Utilization:**
```php
$progressPercent = ($currentBalance / $creditLimit) * 100;

// Color coding:
// < 30% = green (excellent)
// 30-70% = yellow (ok)
// > 70% = red (risky)
```

**Loan Paydown Progress:**
```php
$paidOff = $initialLoanAmount - $currentBalance;
$progressPercent = ($paidOff / $initialLoanAmount) * 100;

// Always blue - positive progress indicator
```

---

## Commit History

Key commits implementing this feature:

1. **Phase 1**: Infrastructure
   - `feat: Phase 1 - Add manual account support infrastructure`
   - `refactor: Switch from OpenAI to Google Gemini for FREE vision API`
   - `refactor: Upgrade to Gemini 2.5 Pro for enhanced accuracy`

2. **Phase 2**: UI and Upload Workflow
   - `feat: Phase 2 - Complete Manual Account Upload UI`
   - `feat: Add 'Upload Update' button for manual accounts`
   - `feat: Pre-fill account data when updating existing manual account`

3. **Duplicate Detection**
   - `feat: Add transaction removal in review modal`
   - `feat: Show duplicate transaction warnings in review modal`
   - `fix: Improve duplicate detection with strikethrough styling`

4. **Year Detection**
   - `fix: Provide current year context to AI for date parsing`
   - `feat: Add year selector for statement uploads`
   - `fix: Respect explicit years in documents, only default to selected year when year not shown`

5. **Loan Support**
   - `feat: Add specific loan types for manual accounts`
   - `feat: Smart loan payment cross-referencing (Option 3)`
   - `fix: Handle null UUID comparison in cross-reference query`

6. **Loan Tracking Features**
   - `feat: Add initial loan amount tracking for paydown progress`
   - `feat: Add 'Set Initial Loan Amount' to loan account dropdown menu`
   - `feat: Add interest rate and loan term to loan tracking`
   - `feat: Add first payment date and origination fees to loan tracking`

7. **UI/UX Improvements**
   - `fix: Complete zero value handling`
   - `fix: Auto-fill account name with default when AI doesn't extract it`
   - `feat: Group accounts by type by default`
   - `feat: Add progress bars for credit cards and loans`

8. **API Reliability**
   - `feat: Add retry logic for Gemini API temporary failures`
   - `fix: Switch to Gemini 1.5 Flash to avoid overload`
   - `fix: Handle markdown-wrapped JSON from AI responses`
   - `fix: Increase max output tokens to 16384`
   - `fix: Limit screenshot parsing to recent 45 days to avoid token limits`

---

## Testing Results

### Successful Test Cases

**1. Varo Bank Checking Statement (PDF)**
- ✅ 71 transactions extracted from October statement
- ✅ November update: 43 duplicates detected correctly
- ✅ Only new transactions imported

**2. MercuryCards Credit Card Statement (PDF)**
- ✅ Institution, last 4 (8048), type (credit_card) detected
- ✅ Balance ($1,292.93), available ($3,707.07), limit ($5,000) extracted
- ✅ 5 transactions imported accurately
- ✅ Interest charges identified correctly

**3. Best Egg Personal Loan Statement**
- ✅ AI extracted due date (Oct 8)
- ✅ System found actual payment in Varo (Oct 9)
- ✅ Cross-reference suggestion shown: "💡 Actual: Oct 9, 2025"
- ✅ One-click date correction worked

**4. TD Auto Finance Payment History (Multi-Year)**
- ✅ 53 transactions from 2022-2025 extracted
- ✅ Explicit years preserved from document
- ✅ Payment amounts, dates accurate
- ✅ NSF fees tracked in loan account only
- ✅ Historical data spanning 3+ years imported correctly

**5. Screenshot Uploads**
- ✅ Year selector defaults to 2025
- ✅ Recent transactions extracted accurately
- ✅ Update workflow with duplicate detection
- ✅ 43 duplicate transactions detected and skipped

---

## Configuration

### Environment Variables
```env
# Google Gemini API Key (required)
GOOGLE_GEMINI_API_KEY=your_gemini_api_key_here
```

### Get Gemini API Key
1. Visit: https://aistudio.google.com/apikey
2. Create new API key
3. Add to `.env` file
4. Free tier: 1,500 requests per day

### Model Selection
Current: `gemini-2.0-flash-exp` or `gemini-1.5-flash`
- Free tier available
- Vision capabilities enabled
- 16,384 token output limit
- Fast response times

---

## Troubleshooting

### Progress Bars Not Showing

**Credit Cards:**
- Verify `credit_limit` is set
- Check account card secondary info section

**Loans:**
- Must set `initial_loan_amount` via menu
- Click ⋮ → "Set Initial Loan Amount"
- Enter original loan amount
- Save and refresh

### AI Parsing Issues

**Poor extraction quality:**
- Use higher quality images/PDFs
- Ensure text is clearly legible
- Try re-uploading with better source

**Wrong dates:**
- Check year selector is correct
- For multi-year documents, years should be detected automatically
- Review and edit dates in review modal if needed

**Missing data:**
- AI may not extract all fields
- Manual entry available in review modal
- All fields editable before saving

### Duplicate Detection Not Working

- Verify year is correct (2025 vs 2024)
- Check account is correct (updating existing account)
- Matching logic: date + amount must match exactly

### Cross-Referencing Not Finding Payments

- Verify payment amount matches loan statement
- Check payment was made within ±7 days of due date
- Ensure source account has transactions uploaded
- Historical data may not have matching records

---

## Cost Analysis

### Free Tier (Google Gemini)
- **Cost:** $0
- **Limit:** 1,500 requests/day
- **Usage:** ~1 request per statement upload
- **Sufficient for:** 1,500 statements per day (unlimited for personal use)

### Comparison to Alternatives

**OpenAI GPT-4 Vision:**
- Cost: $0.01-0.03 per request
- Quality: Excellent
- Total cost for 100 uploads: $1-3/month

**Manual Entry:**
- Cost: $0
- Time: 5-15 minutes per statement
- Error rate: High
- Scalability: Poor

**Manual Account + Gemini:**
- Cost: $0 (free tier)
- Time: 1-2 minutes per statement (review only)
- Error rate: Low
- Scalability: Excellent

---

## Security & Privacy

### Data Handling
- Uploaded files stored temporarily in Laravel storage
- Files deleted after processing (optional retention)
- No permanent storage of statement images
- Data never shared with third parties beyond API call

### API Privacy
- Google Gemini processes documents
- Data used for parsing only
- No training data retention (per Google's policy)
- Encrypted API communication

### Best Practices
1. Review extracted data before saving
2. Don't upload files containing SSN or full account numbers
3. Use secure connection (HTTPS)
4. Regularly review imported transactions

---

## Credits

**Developed by:** Droid AI + User Collaboration

**Technologies:**
- Laravel 12 + Livewire 3
- Google Gemini Vision API
- PostgreSQL
- Tailwind CSS

**Special Thanks:**
- Google for free Gemini API tier
- Teller for primary bank account connections
- Open source community

---

## License

Part of the Ledger application. All rights reserved.

---

**Last Updated:** November 17, 2025
**Version:** 1.0
**Status:** Production Ready ✅
