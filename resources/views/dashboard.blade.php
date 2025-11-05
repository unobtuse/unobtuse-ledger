<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Dashboard - {{ config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <h1 class="text-xl font-bold text-gray-900">{{ config('app.name') }}</h1>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="ml-3 relative">
                            <div class="flex items-center space-x-4">
                                <span class="text-sm text-gray-700">{{ auth()->user()->name }}</span>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" 
                                            class="text-sm text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md hover:bg-gray-100 transition-colors">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Welcome Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">
                            Welcome to Unobtuse Ledger! 🎉
                        </h2>
                        <p class="text-gray-600 mb-4">
                            You're logged in! This is your dashboard where you'll be able to manage your bills, track spending, and view AI-powered insights.
                        </p>
                        
                        @if (auth()->user()->isOAuthUser())
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                <p class="text-sm text-blue-800">
                                    ✓ You're signed in with Google OAuth
                                </p>
                            </div>
                        @endif

                        @if (auth()->user()->hasTwoFactorEnabled())
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                <p class="text-sm text-green-800">
                                    ✓ Two-factor authentication is enabled
                                </p>
                            </div>
                        @endif

                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Phase 1B Complete ✅</h3>
                            <ul class="space-y-2 text-sm text-gray-600">
                                <li>✓ Plaid integration configured</li>
                                <li>✓ Account linking ready</li>
                                <li>✓ Transaction sync background jobs</li>
                                <li>✓ Bill detection algorithm</li>
                                <li>✓ Database schema complete</li>
                                <li>✓ Webhook handler implemented</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Linked Accounts -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-gray-900">Linked Accounts</h3>
                            <a href="{{ route('accounts.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                + Link Bank Account
                            </a>
                        </div>

                        @if (auth()->user()->accounts->isEmpty())
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No accounts linked</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by linking your first bank account.</p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach (auth()->user()->accounts as $account)
                                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                        <div class="flex items-center justify-between mb-2">
                                            <h4 class="font-semibold text-gray-900">{{ $account->account_name }}</h4>
                                            @if ($account->sync_status === 'synced')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Synced
                                                </span>
                                            @elseif ($account->sync_status === 'syncing')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Syncing...
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Error
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-600 mb-1">{{ $account->institution_name }}</p>
                                        <p class="text-sm text-gray-500 mb-3">
                                            {{ ucfirst($account->account_type) }} •••• {{ $account->mask }}
                                        </p>
                                        <div class="text-2xl font-bold text-gray-900">
                                            {{ $account->getFormattedBalance() }}
                                        </div>
                                        @if ($account->last_synced_at)
                                            <p class="text-xs text-gray-500 mt-2">
                                                Last synced {{ $account->last_synced_at->diffForHumans() }}
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

