<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-3xl font-bold text-card-foreground">Generate Synthetic Data</h1>
        <p class="text-muted-foreground mt-1">Upload and import synthetic financial data for demo purposes</p>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="bg-chart-2/20 border border-chart-2/50 text-chart-2 rounded-[var(--radius-default)] p-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-destructive/20 border border-destructive/50 text-destructive rounded-[var(--radius-default)] p-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- Main Form Card --}}
    <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
        <div class="space-y-6">
            {{-- Backup Database Section --}}
            <div class="pb-6 border-b border-border">
                <h2 class="text-lg font-semibold text-card-foreground mb-2">Database Backup</h2>
                <p class="text-sm text-muted-foreground mb-4">Download a full backup of your Supabase database</p>
                <a 
                    href="{{ route('dashboard.backup-database') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-chart-4 text-white rounded-[var(--radius-md)] hover:opacity-90 transition-all duration-150"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download Database Backup
                </a>
                <p class="text-xs text-muted-foreground mt-2">Backup includes all tables, data, and schema</p>
            </div>

            {{-- User Selection --}}
            <div>
                <label for="selectedUserId" class="block text-sm font-medium text-card-foreground mb-2">
                    Select User
                </label>
                <select 
                    wire:model="selectedUserId" 
                    id="selectedUserId"
                    class="w-full px-3 py-2 bg-input border border-border rounded-[var(--radius-sm)] text-card-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                >
                    <option value="">Current User ({{ Auth::user()->name }})</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                <p class="text-xs text-muted-foreground mt-1">Select the user to import data for</p>
            </div>

            {{-- File Upload --}}
            <div>
                <label class="block text-sm font-medium text-card-foreground mb-2">
                    JSON File
                </label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed border-border rounded-[var(--radius-default)] hover:border-primary/50 transition-colors duration-150">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-muted-foreground" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div class="flex text-sm text-muted-foreground">
                            <label for="jsonFile" class="relative cursor-pointer bg-card rounded-md font-medium text-primary hover:text-primary/80 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary">
                                <span>Upload a file</span>
                                <input wire:model="jsonFile" id="jsonFile" name="jsonFile" type="file" accept=".json" class="sr-only">
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-muted-foreground">JSON file up to 10MB</p>
                        @if($jsonFile)
                            <p class="text-sm text-card-foreground mt-2 font-medium">{{ $jsonFile->getClientOriginalName() }}</p>
                        @endif
                    </div>
                </div>
                @error('jsonFile') 
                    <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-3">
                <button 
                    wire:click="validateJson"
                    wire:loading.attr="disabled"
                    wire:target="validateJson"
                    class="px-4 py-2 bg-primary text-primary-foreground rounded-[var(--radius-md)] hover:opacity-90 transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    <svg wire:loading wire:target="validateJson" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="validateJson">Validate JSON</span>
                    <span wire:loading wire:target="validateJson">Validating...</span>
                </button>

                <button 
                    wire:click="importData"
                    wire:loading.attr="disabled"
                    wire:target="importData"
                    @if(!$validationResult || !($validationResult['valid'] ?? false)) disabled @endif
                    class="px-4 py-2 bg-chart-2 text-white rounded-[var(--radius-md)] hover:opacity-90 transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    <svg wire:loading wire:target="importData" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="importData">Import Data</span>
                    <span wire:loading wire:target="importData">Importing...</span>
                </button>

                <button 
                    wire:click="resetForm"
                    class="px-4 py-2 bg-muted text-muted-foreground rounded-[var(--radius-md)] hover:opacity-90 transition-all duration-150"
                >
                    Reset
                </button>
            </div>
        </div>
    </div>

    {{-- Validation Results --}}
    @if($validationResult)
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h2 class="text-xl font-semibold text-card-foreground mb-4">Validation Results</h2>

            {{-- Summary --}}
            @if(isset($validationResult['summary']))
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                    <div class="bg-muted/50 rounded-[var(--radius-sm)] p-3">
                        <div class="text-xs text-muted-foreground">Accounts</div>
                        <div class="text-lg font-semibold text-card-foreground">{{ $validationResult['summary']['accounts'] ?? 0 }}</div>
                    </div>
                    <div class="bg-muted/50 rounded-[var(--radius-sm)] p-3">
                        <div class="text-xs text-muted-foreground">Transactions</div>
                        <div class="text-lg font-semibold text-card-foreground">{{ $validationResult['summary']['transactions'] ?? 0 }}</div>
                    </div>
                    <div class="bg-muted/50 rounded-[var(--radius-sm)] p-3">
                        <div class="text-xs text-muted-foreground">Bills</div>
                        <div class="text-lg font-semibold text-card-foreground">{{ $validationResult['summary']['bills'] ?? 0 }}</div>
                    </div>
                    <div class="bg-muted/50 rounded-[var(--radius-sm)] p-3">
                        <div class="text-xs text-muted-foreground">Pay Schedules</div>
                        <div class="text-lg font-semibold text-card-foreground">{{ $validationResult['summary']['pay_schedules'] ?? 0 }}</div>
                    </div>
                    <div class="bg-muted/50 rounded-[var(--radius-sm)] p-3">
                        <div class="text-xs text-muted-foreground">Budgets</div>
                        <div class="text-lg font-semibold text-card-foreground">{{ $validationResult['summary']['budgets'] ?? 0 }}</div>
                    </div>
                </div>
            @endif

            {{-- Validation Status --}}
            <div class="mb-4">
                @if($validationResult['valid'])
                    <div class="flex items-center gap-2 text-chart-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-semibold">Validation Passed</span>
                    </div>
                @else
                    <div class="flex items-center gap-2 text-destructive">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-semibold">Validation Failed</span>
                    </div>
                @endif
            </div>

            {{-- Errors --}}
            @if(!empty($validationResult['errors']))
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-destructive mb-2">
                        Errors ({{ count($validationResult['errors']) }})
                    </h3>
                    <div class="bg-destructive/10 border border-destructive/20 rounded-[var(--radius-sm)] p-4 max-h-64 overflow-y-auto">
                        <ul class="space-y-1 text-sm text-destructive">
                            @foreach($validationResult['errors'] as $error)
                                <li class="flex items-start gap-2">
                                    <span class="text-destructive mt-0.5">•</span>
                                    <span>{{ $error }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Warnings --}}
            @if(!empty($validationResult['warnings']))
                <div>
                    <h3 class="text-sm font-semibold text-chart-4 mb-2">
                        Warnings ({{ count($validationResult['warnings']) }})
                    </h3>
                    <div class="bg-chart-4/10 border border-chart-4/20 rounded-[var(--radius-sm)] p-4 max-h-64 overflow-y-auto">
                        <ul class="space-y-1 text-sm text-chart-4">
                            @foreach($validationResult['warnings'] as $warning)
                                <li class="flex items-start gap-2">
                                    <span class="text-chart-4 mt-0.5">•</span>
                                    <span>{{ $warning }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Import Results --}}
    @if($importResult)
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h2 class="text-xl font-semibold text-card-foreground mb-4">Import Results</h2>

            @if($importResult['success'])
                <div class="flex items-center gap-2 text-chart-2 mb-4">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-semibold">Import Successful!</span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-chart-2/10 rounded-[var(--radius-sm)] p-3">
                        <div class="text-xs text-muted-foreground">Accounts</div>
                        <div class="text-lg font-semibold text-card-foreground">{{ $importResult['imported']['accounts'] ?? 0 }}</div>
                    </div>
                    <div class="bg-chart-2/10 rounded-[var(--radius-sm)] p-3">
                        <div class="text-xs text-muted-foreground">Transactions</div>
                        <div class="text-lg font-semibold text-card-foreground">{{ $importResult['imported']['transactions'] ?? 0 }}</div>
                    </div>
                    <div class="bg-chart-2/10 rounded-[var(--radius-sm)] p-3">
                        <div class="text-xs text-muted-foreground">Bills</div>
                        <div class="text-lg font-semibold text-card-foreground">{{ $importResult['imported']['bills'] ?? 0 }}</div>
                    </div>
                    <div class="bg-chart-2/10 rounded-[var(--radius-sm)] p-3">
                        <div class="text-xs text-muted-foreground">Pay Schedules</div>
                        <div class="text-lg font-semibold text-card-foreground">{{ $importResult['imported']['pay_schedules'] ?? 0 }}</div>
                    </div>
                    <div class="bg-chart-2/10 rounded-[var(--radius-sm)] p-3">
                        <div class="text-xs text-muted-foreground">Budgets</div>
                        <div class="text-lg font-semibold text-card-foreground">{{ $importResult['imported']['budgets'] ?? 0 }}</div>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-2 text-destructive mb-4">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-semibold">Import Failed</span>
                </div>

                @if(!empty($importResult['errors']))
                    <div class="bg-destructive/10 border border-destructive/20 rounded-[var(--radius-sm)] p-4">
                        <ul class="space-y-1 text-sm text-destructive">
                            @foreach($importResult['errors'] as $error)
                                <li class="flex items-start gap-2">
                                    <span class="text-destructive mt-0.5">•</span>
                                    <span>{{ $error }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endif
        </div>
    @endif

    {{-- Help Section --}}
    <div class="bg-muted/30 border border-border rounded-[var(--radius-default)] p-6">
        <h3 class="text-lg font-semibold text-card-foreground mb-2">How to Use</h3>
        <ol class="list-decimal list-inside space-y-2 text-sm text-muted-foreground">
            <li>Select the user you want to import data for (or use current user)</li>
            <li>Upload a JSON file following the schema defined in <code class="bg-muted px-1 py-0.5 rounded">docs/SYNTHETIC_DATA_SCHEMA.md</code></li>
            <li>Click "Validate JSON" to check for errors</li>
            <li>If validation passes, click "Import Data" to import into the database</li>
            <li>Review the import results to confirm success</li>
        </ol>
        <p class="mt-4 text-xs text-muted-foreground">
            <strong>Note:</strong> See <code class="bg-muted px-1 py-0.5 rounded">progress/SYNTHETIC_DATA_PROMPT.md</code> for instructions on generating synthetic data with AI.
        </p>
    </div>
</div>

