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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
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
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Phase 1A Complete ✅</h3>
                            <ul class="space-y-2 text-sm text-gray-600">
                                <li>✓ Laravel 12 configured with UUID primary keys</li>
                                <li>✓ Google OAuth authentication</li>
                                <li>✓ Email/password registration</li>
                                <li>✓ Two-factor authentication (TOTP) support</li>
                                <li>✓ Email verification</li>
                                <li>✓ Password reset flows</li>
                                <li>✓ Modern, responsive authentication UI</li>
                            </ul>
                        </div>

                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Coming Next: Phase 1B</h3>
                            <ul class="space-y-2 text-sm text-gray-600">
                                <li>→ Plaid integration for bank account linking</li>
                                <li>→ Transaction synchronization</li>
                                <li>→ Bill detection and tracking</li>
                                <li>→ Budget calculations</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

