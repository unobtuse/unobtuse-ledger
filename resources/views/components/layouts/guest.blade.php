<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

    @php($favicon = asset('logos/unobtuse-ledger-icon.svg') . '?v=20251106')

    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" sizes="any" href="{{ $favicon }}">
    <link rel="shortcut icon" type="image/svg+xml" sizes="any" href="{{ $favicon }}">
    <link rel="apple-touch-icon" href="{{ $favicon }}">
    <link rel="mask-icon" href="{{ $favicon }}" color="oklch(0.205 0 0)">

    <!-- Geist Font (Design System) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-background text-foreground font-sans antialiased">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <!-- Logo -->
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <a href="/" class="flex justify-center">
                <img class="h-12 w-auto dark:hidden" src="{{ asset('logos/unobtuse-ledger-black-logo.svg') }}" alt="{{ config('app.name') }}">
                <img class="h-12 w-auto hidden dark:block" src="{{ asset('logos/unobtuse-ledger-white-logo.svg') }}" alt="{{ config('app.name') }}">
            </a>
            <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-foreground">
                {{ $title ?? config('app.name') }}
            </h2>
        </div>

        <!-- Content -->
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-card py-8 px-4 shadow-elevated rounded-lg sm:px-10 border border-border">
                {{ $slot }}
            </div>

            @if (isset($footer))
                <div class="mt-6 text-center text-sm text-muted-foreground">
                    {{ $footer }}
                </div>
            @endif
        </div>

        <!-- Dark Mode Toggle -->
        <div class="mt-6 flex justify-center">
            <button @click="darkMode = !darkMode" 
                    class="p-2 rounded-lg transition-colors hover:bg-muted text-muted-foreground hover:text-foreground"
                    title="Toggle dark mode">
                <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session('status'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition
             x-init="setTimeout(() => show = false, 3000)"
             class="fixed bottom-4 right-4 bg-chart-2/20 border border-chart-2 text-chart-2 px-4 py-3 rounded-lg shadow-elevated">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition
             x-init="setTimeout(() => show = false, 5000)"
             class="fixed bottom-4 right-4 bg-destructive/20 border border-destructive text-destructive px-4 py-3 rounded-lg shadow-elevated">
            {{ session('error') }}
        </div>
    @endif
</body>
</html>
