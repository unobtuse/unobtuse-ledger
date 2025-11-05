<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unobtuse Ledger - Never Miss Another Bill Payment</title>
    <meta name="description" content="AI-powered personal finance management that aligns bills with your paycheck schedule. Save $500+/year. Never pay another late fee. Free to start.">

    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://ledger.unobtuse.com">
    <meta property="og:title" content="Unobtuse Ledger - Never Miss Another Bill Payment">
    <meta property="og:description" content="AI-powered personal finance management. Save $500+/year. Never pay another late fee.">
    <meta property="og:image" content="{{ asset('logos/unobtuse-ledger-icon.svg') }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://ledger.unobtuse.com">
    <meta property="twitter:title" content="Unobtuse Ledger - Never Miss Another Bill Payment">
    <meta property="twitter:description" content="AI-powered personal finance management. Save $500+/year.">

    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('logos/unobtuse-ledger-icon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('logos/unobtuse-ledger-icon.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=geist:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
            @vite(['resources/css/app.css', 'resources/js/app.js'])

            <style>
        body {
            font-family: 'Geist', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--color-background);
            color: var(--color-foreground);
        }
            </style>
    </head>
<body class="antialiased">

    <!-- Navigation -->
    <nav class="fixed top-0 w-full backdrop-blur-md z-50" style="background-color: var(--color-background); opacity: 0.95; border-bottom: 1px solid var(--color-border);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <img src="{{ asset('logos/unobtuse-ledger-black-logo.svg') }}" alt="Unobtuse Ledger" class="h-8 dark:hidden">
                    <img src="{{ asset('logos/unobtuse-ledger-white-logo.svg') }}" alt="Unobtuse Ledger" class="h-8 hidden dark:block">
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="transition" style="color: var(--color-muted-foreground);">Features</a>
                    <a href="#pricing" class="transition" style="color: var(--color-muted-foreground);">Pricing</a>
                    <a href="#faq" class="transition" style="color: var(--color-muted-foreground);">FAQ</a>
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn-primary">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="transition" style="color: var(--color-muted-foreground);">Log in</a>
                        <a href="{{ route('register') }}" class="btn-primary">Get Started Free</a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button type="button" style="color: var(--color-muted-foreground);">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-4 sm:px-6 lg:px-8" style="background: linear-gradient(135deg, var(--color-background) 0%, var(--color-muted) 100%);">
        <div class="max-w-7xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left: Hero Copy -->
                <div class="text-center lg:text-left">
                    <!-- Badge -->
                    <div class="inline-flex items-center space-x-2 px-4 py-2 text-sm font-medium mb-6" style="background-color: var(--color-muted); color: var(--color-primary); border-radius: var(--radius-full);">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <span>Join 10,000+ users who never miss a payment</span>
                    </div>

                    <!-- Headline -->
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight mb-6" style="color: var(--color-foreground);">
                        Never Pay Another 
                        <span class="text-chart-3">Late Fee</span>
                    </h1>

                    <!-- Subheadline -->
                    <p class="text-xl mb-8 leading-relaxed" style="color: var(--color-muted-foreground);">
                        AI-powered personal finance that aligns bills with <span class="font-semibold" style="color: var(--color-foreground);">YOUR paycheck schedule</span>. Save $500+/year automatically.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start mb-8">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-8 py-4 font-semibold text-lg transition transform hover:-translate-y-0.5" style="background-color: var(--color-primary); color: var(--color-primary-foreground); border-radius: var(--radius-default); box-shadow: var(--shadow-elevated);">
                                Go to Dashboard →
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="px-8 py-4 font-semibold text-lg transition transform hover:-translate-y-0.5" style="background-color: var(--color-primary); color: var(--color-primary-foreground); border-radius: var(--radius-default); box-shadow: var(--shadow-elevated);">
                                Start Free Trial →
                            </a>
                            <a href="#features" class="px-8 py-4 font-semibold text-lg transition" style="background-color: var(--color-card); color: var(--color-card-foreground); border: 1px solid var(--color-border); border-radius: var(--radius-default);">
                                See How It Works
                            </a>
                    @endauth
                    </div>

                    <!-- Trust Indicators -->
                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-6 text-sm" style="color: var(--color-muted-foreground);">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-chart-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Free to start</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-chart-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>No credit card required</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-chart-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Cancel anytime</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Hero Image / App Preview -->
                <div class="relative">
                    <div class="relative z-10 style="background-color: var(--color-card);" rounded-2xl shadow-2xl p-6 border style="border-color: var(--color-border);"">
                        <!-- Mock Dashboard Preview -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between pb-4 border-b style="border-color: var(--color-border);"">
                                <h3 class="text-lg font-semibold">Bills Due Before Next Paycheck</h3>
                                <span class="text-sm text-gray-500">3 days away</span>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-4 style="background-color: var(--color-muted); border-color: var(--color-chart-5);" rounded-lg border ">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center text-white font-bold">!</div>
                                        <div>
                                            <p class="font-medium">Credit Card Payment</p>
                                            <p class="text-sm style="color: var(--color-muted-foreground);"">Due Nov 8</p>
                                        </div>
                                    </div>
                                    <span class="font-bold text-lg">$350</span>
                                </div>

                                <div class="flex items-center justify-between p-4 style="background-color: var(--color-muted); border-color: var(--color-chart-4);" rounded-lg border ">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center text-white font-bold">⚡</div>
                                        <div>
                                            <p class="font-medium">Electric Bill</p>
                                            <p class="text-sm style="color: var(--color-muted-foreground);"">Due Nov 10</p>
                                        </div>
                                    </div>
                                    <span class="font-bold text-lg">$120</span>
                                </div>

                                <div class="flex items-center justify-between p-4 style="background-color: var(--color-muted); border-color: var(--color-chart-2);" rounded-lg border ">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-bold">✓</div>
                                        <div>
                                            <p class="font-medium">Internet</p>
                                            <p class="text-sm style="color: var(--color-muted-foreground);"">Paid</p>
                                        </div>
                                    </div>
                                    <span class="font-bold text-lg style="color: var(--color-muted-foreground);" line-through">$80</span>
                                </div>
                            </div>

                            <div class="pt-4 border-t style="border-color: var(--color-border);"">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="style="color: var(--color-muted-foreground);"">Remaining Budget:</span>
                                    <span class="text-2xl font-bold text-chart-2">$1,230</span>
                                </div>
                                <div class="text-sm text-gray-500 text-center">After bills, before next paycheck</div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Badges -->
                    <div class="absolute -left-4 top-20 style="background-color: var(--color-card);" rounded-lg shadow-lg p-4 border style="border-color: var(--color-border);" animate-bounce">
                        <div class="text-sm font-medium style="color: var(--color-muted-foreground);"">Saved this year</div>
                        <div class="text-2xl font-bold text-chart-2">$816</div>
                    </div>

                    <div class="absolute -right-4 bottom-20 style="background-color: var(--color-card);" rounded-lg shadow-lg p-4 border style="border-color: var(--color-border);"">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-chart-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="font-medium">No late fees in 6 months!</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Proof / Stats -->
    <section class="py-16" style="background-color: var(--color-muted);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl font-bold mb-2 text-chart-3">10,000+</div>
                    <div style="color: var(--color-muted-foreground);">Active Users</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-2 text-chart-3">$500+</div>
                    <div style="color: var(--color-muted-foreground);">Avg. Annual Savings</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-2 text-chart-3">99.9%</div>
                    <div style="color: var(--color-muted-foreground);">Uptime</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-2 text-chart-3">4.8★</div>
                    <div style="color: var(--color-muted-foreground);">User Rating</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Problem Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8" style="background-color: var(--color-background);">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold mb-4" style="color: var(--color-foreground);">
                    You're Not Bad With Money.<br />You Just Need Better Tools.
                </h2>
                <p class="text-xl max-w-3xl mx-auto" style="color: var(--color-muted-foreground);">
                    Late payments cost Americans $12 billion per year. Not because they can't afford bills—but because they forget.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Problem 1 -->
                <div class="style="background-color: var(--color-muted); border-color: var(--color-chart-5);" rounded-xl p-8 border ">
                    <div class="text-5xl mb-4">💸</div>
                    <h3 class="text-xl font-bold style="color: var(--color-foreground);" mb-3">Late Fees Add Up Fast</h3>
                    <p class="style="color: var(--color-muted-foreground);" mb-4">
                        Average late fee: $30-40 per bill. Miss 2 bills/month? That's $720-960 per year wasted.
                    </p>
                    <div class="text-2xl font-bold text-chart-5">-$740/year</div>
                </div>

                <!-- Problem 2 -->
                <div class="style="background-color: var(--color-muted); border-color: var(--color-chart-1);" rounded-xl p-8 border ">
                    <div class="text-5xl mb-4">📉</div>
                    <h3 class="text-xl font-bold style="color: var(--color-foreground);" mb-3">Credit Score Damage</h3>
                    <p class="style="color: var(--color-muted-foreground);" mb-4">
                        One missed payment drops your credit score 50-100 points. Recovery takes 6-12 months.
                    </p>
                    <div class="text-2xl font-bold text-orange-600">-80 points</div>
                </div>

                <!-- Problem 3 -->
                <div class="style="background-color: var(--color-muted); border-color: var(--color-chart-4);" rounded-xl p-8 border ">
                    <div class="text-5xl mb-4">🔄</div>
                    <h3 class="text-xl font-bold style="color: var(--color-foreground);" mb-3">Forgotten Subscriptions</h3>
                    <p class="style="color: var(--color-muted-foreground);" mb-4">
                        Average person wastes $214/month on subscriptions they don't use or forgot about.
                    </p>
                    <div class="text-2xl font-bold text-chart-4">-$2,568/year</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Solution / How It Works -->
    <section id="features" class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-900 dark:to-gray-800">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold style="color: var(--color-foreground);" mb-4">
                    How Unobtuse Ledger Works
                </h2>
                <p class="text-xl style="color: var(--color-muted-foreground);" max-w-3xl mx-auto">
                    Financial clarity in three simple steps. No manual entry. No spreadsheets. No stress.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-12">
                <!-- Step 1 -->
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-primary text-white rounded-full text-2xl font-bold mb-6">1</div>
                    <h3 class="text-2xl font-bold style="color: var(--color-foreground);" mb-4">Link Your Accounts</h3>
                    <p class="style="color: var(--color-muted-foreground);" mb-6">
                        Securely connect your bank accounts and credit cards via Plaid. Bank-level encryption protects your data.
                    </p>
                    <div class="style="background-color: var(--color-card);" rounded-lg p-4 border style="border-color: var(--color-border);"">
                        <div class="text-sm text-gray-500 mb-2">Supported banks:</div>
                        <div class="text-xs style="color: var(--color-muted-foreground);"">Chase • Bank of America • Wells Fargo • Capital One • 12,000+ more</div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-600 text-white rounded-full text-2xl font-bold mb-6">2</div>
                    <h3 class="text-2xl font-bold style="color: var(--color-foreground);" mb-4">Set Your Pay Schedule</h3>
                    <p class="style="color: var(--color-muted-foreground);" mb-6">
                        Tell us how often you get paid. We'll align your bills with YOUR paycheck dates—not calendar months.
                    </p>
                    <div class="style="background-color: var(--color-card);" rounded-lg p-4 border style="border-color: var(--color-border);" space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="style="color: var(--color-muted-foreground);"">Next Paycheck:</span>
                            <span class="font-bold">Nov 15</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="style="color: var(--color-muted-foreground);"">Bills Due Before:</span>
                            <span class="font-bold text-chart-5">3 bills ($470)</span>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-600 text-white rounded-full text-2xl font-bold mb-6">3</div>
                    <h3 class="text-2xl font-bold style="color: var(--color-foreground);" mb-4">Never Miss a Payment</h3>
                    <p class="style="color: var(--color-muted-foreground);" mb-6">
                        Get smart reminders 3 days before bills are due. AI finds forgotten subscriptions. Save money automatically.
                    </p>
                    <div class="style="background-color: var(--color-card);" rounded-lg p-4 border style="border-color: var(--color-border);"">
                        <div class="flex items-center justify-center space-x-2 text-chart-2 font-bold">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>0 Late Fees This Year</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold style="color: var(--color-foreground);" mb-4">
                    Everything You Need to Take Control
                </h2>
                <p class="text-xl style="color: var(--color-muted-foreground);"">
                    Powerful features that save you time, money, and stress.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="style="background-color: var(--color-card);" rounded-xl p-8 border style="border-color: var(--color-border);" hover:shadow-lg transition">
                    <div class="text-4xl mb-4">📅</div>
                    <h3 class="text-xl font-bold style="color: var(--color-foreground);" mb-3">Bill Calendar</h3>
                    <p class="style="color: var(--color-muted-foreground);"">
                        Visual calendar showing exactly which bills are due before your next paycheck. Never wonder if you have enough.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="style="background-color: var(--color-card);" rounded-xl p-8 border style="border-color: var(--color-border);" hover:shadow-lg transition">
                    <div class="text-4xl mb-4">🤖</div>
                    <h3 class="text-xl font-bold style="color: var(--color-foreground);" mb-3">AI Receipt Scanning</h3>
                    <p class="style="color: var(--color-muted-foreground);"">
                        Snap a photo of any receipt. AI extracts merchant, amount, and category with 97% accuracy. No manual entry.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="style="background-color: var(--color-card);" rounded-xl p-8 border style="border-color: var(--color-border);" hover:shadow-lg transition">
                    <div class="text-4xl mb-4">🔍</div>
                    <h3 class="text-xl font-bold style="color: var(--color-foreground);" mb-3">Subscription Detective</h3>
                    <p class="style="color: var(--color-muted-foreground);"">
                        AI automatically finds recurring charges you forgot about. Average user finds $214/month in wasted subscriptions.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="style="background-color: var(--color-card);" rounded-xl p-8 border style="border-color: var(--color-border);" hover:shadow-lg transition">
                    <div class="text-4xl mb-4">💰</div>
                    <h3 class="text-xl font-bold style="color: var(--color-foreground);" mb-3">Smart Budgeting</h3>
                    <p class="style="color: var(--color-muted-foreground);"">
                        Automatic rent allocation per paycheck. See your TRUE remaining budget after all obligations. No math required.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="style="background-color: var(--color-card);" rounded-xl p-8 border style="border-color: var(--color-border);" hover:shadow-lg transition">
                    <div class="text-4xl mb-4">⚠️</div>
                    <h3 class="text-xl font-bold style="color: var(--color-foreground);" mb-3">Anomaly Detection</h3>
                    <p class="style="color: var(--color-muted-foreground);"">
                        Get instant alerts for unusual spending patterns. Catch fraudulent charges before they become problems.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="style="background-color: var(--color-card);" rounded-xl p-8 border style="border-color: var(--color-border);" hover:shadow-lg transition">
                    <div class="text-4xl mb-4">🔐</div>
                    <h3 class="text-xl font-bold style="color: var(--color-foreground);" mb-3">Bank-Level Security</h3>
                    <p class="style="color: var(--color-muted-foreground);"">
                        AES-256 encryption. Two-factor authentication. We never store your bank login. Your data is 100% private.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 style="background-color: var(--color-muted);"">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold style="color: var(--color-foreground);" mb-4">
                    Real People, Real Results
                </h2>
                <p class="text-xl style="color: var(--color-muted-foreground);"">
                    See how Unobtuse Ledger is helping thousands save money and reduce stress.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Testimonial 1 -->
                <div class="style="background-color: var(--color-background);" rounded-xl p-8 border style="border-color: var(--color-border);"">
                    <div class="flex items-center mb-4">
                        <div class="flex text-yellow-400">
                            ★★★★★
                        </div>
                    </div>
                    <p class="style="color: var(--color-muted-foreground);" mb-6 italic">
                        "I found $280/month in subscriptions I forgot about. Unobtuse paid for itself 25x over in the first week."
                    </p>
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white font-bold">M</div>
                        <div>
                            <div class="font-bold style="color: var(--color-foreground);"">Michael T.</div>
                            <div class="text-sm text-gray-500">Software Engineer</div>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 2 -->
                <div class="style="background-color: var(--color-background);" rounded-xl p-8 border style="border-color: var(--color-border);"">
                    <div class="flex items-center mb-4">
                        <div class="flex text-yellow-400">
                            ★★★★★
                        </div>
                    </div>
                    <p class="style="color: var(--color-muted-foreground);" mb-6 italic">
                        "The bill calendar that shows what's due before my next paycheck is a game changer. Finally, an app that gets how real people budget."
                    </p>
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center text-white font-bold">S</div>
                        <div>
                            <div class="font-bold style="color: var(--color-foreground);"">Sarah K.</div>
                            <div class="text-sm text-gray-500">Marketing Manager</div>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 3 -->
                <div class="style="background-color: var(--color-background);" rounded-xl p-8 border style="border-color: var(--color-border);"">
                    <div class="flex items-center mb-4">
                        <div class="flex text-yellow-400">
                            ★★★★★
                        </div>
                    </div>
                    <p class="style="color: var(--color-muted-foreground);" mb-6 italic">
                        "Zero late fees in 8 months! My credit score went up 48 points. This app has genuinely changed my financial life."
                    </p>
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center text-white font-bold">D</div>
                        <div>
                            <div class="font-bold style="color: var(--color-foreground);"">David L.</div>
                            <div class="text-sm text-gray-500">Teacher</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold style="color: var(--color-foreground);" mb-4">
                    Simple, Transparent Pricing
                </h2>
                <p class="text-xl style="color: var(--color-muted-foreground);"">
                    Start free. Upgrade when you're ready. Cancel anytime.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <!-- Free Tier -->
                <div class="style="background-color: var(--color-card);" rounded-2xl p-8 border-2 style="border-color: var(--color-border);"">
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold style="color: var(--color-foreground);" mb-2">Free</h3>
                        <div class="text-4xl font-bold style="color: var(--color-foreground);" mb-2">$0</div>
                        <div class="style="color: var(--color-muted-foreground);"">Forever free</div>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-chart-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="style="color: var(--color-muted-foreground);"">1-2 linked accounts</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-chart-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="style="color: var(--color-muted-foreground);"">Basic bill reminders</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-chart-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                            <span class="style="color: var(--color-muted-foreground);"">Simple budgeting (3 categories)</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-chart-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                            <span class="style="color: var(--color-muted-foreground);"">Transaction tracking</span>
                        </li>
                    </ul>

                    <a href="{{ route('register') }}" class="block w-full bg-gray-900 dark:bg-white hover:style="background-color: var(--color-card);" dark:hover:bg-gray-100 text-white dark:text-gray-900 text-center px-6 py-3 rounded-lg font-semibold transition">
                        Get Started Free
                    </a>
                </div>

                <!-- Premium Tier (POPULAR) -->
                <div class="bg-gradient-to-br from-blue-600 to-purple-600 rounded-2xl p-8 border-2 border-blue-600 relative transform scale-105 shadow-2xl">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-yellow-400 text-gray-900 px-4 py-1 rounded-full text-sm font-bold">MOST POPULAR</span>
                    </div>

                    <div class="text-center mb-6 text-white">
                        <h3 class="text-2xl font-bold mb-2">Premium</h3>
                        <div class="text-4xl font-bold mb-2">$9.99</div>
                        <div class="opacity-90">per month</div>
                        <div class="text-sm mt-2 opacity-75">or $99/year (save $20)</div>
                    </div>
                    
                    <ul class="space-y-4 mb-8 text-white">
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span className="font-medium">Everything in Free, plus:</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span><strong>Unlimited</strong> account linking</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span><strong>AI receipt scanning</strong> (50/month)</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span><strong>Subscription detection</strong></span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Advanced budgeting & insights</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Priority support</span>
                        </li>
                    </ul>

                    <a href="{{ route('register') }}" class="block w-full bg-white hover:bg-gray-100 text-chart-3 text-center px-6 py-3 rounded-lg font-semibold transition">
                        Start 14-Day Free Trial
                    </a>
                    <div class="text-center mt-3 text-sm text-white opacity-75">No credit card required</div>
                </div>

                <!-- Pro Tier -->
                <div class="style="background-color: var(--color-card);" rounded-2xl p-8 border-2 style="border-color: var(--color-border);"">
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold style="color: var(--color-foreground);" mb-2">Pro</h3>
                        <div class="text-4xl font-bold style="color: var(--color-foreground);" mb-2">$19.99</div>
                        <div class="style="color: var(--color-muted-foreground);"">per month</div>
                        <div class="text-sm mt-2 text-gray-500">or $199/year (save $40)</div>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-chart-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="style="color: var(--color-foreground);" font-medium">Everything in Premium, plus:</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-chart-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="style="color: var(--color-muted-foreground);""><strong>Unlimited</strong> receipt scanning</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-chart-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="style="color: var(--color-muted-foreground);""><strong>Bill negotiation</strong> service</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-chart-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                            <span class="style="color: var(--color-muted-foreground);"">Family accounts (5 users)</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-chart-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                            <span class="style="color: var(--color-muted-foreground);"">Advanced reports & export</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-chart-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                            <span class="style="color: var(--color-muted-foreground);"">API access</span>
                        </li>
                    </ul>

                    <a href="{{ route('register') }}" class="block w-full bg-gray-900 dark:bg-white hover:style="background-color: var(--color-card);" dark:hover:bg-gray-100 text-white dark:text-gray-900 text-center px-6 py-3 rounded-lg font-semibold transition">
                        Start 14-Day Free Trial
                    </a>
                </div>
            </div>

            <div class="text-center mt-12 style="color: var(--color-muted-foreground);"">
                <p>All plans include bank-level security, 2FA, and we never sell your data.</p>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-20 px-4 sm:px-6 lg:px-8 style="background-color: var(--color-muted);"">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold style="color: var(--color-foreground);" mb-4">
                    Frequently Asked Questions
                </h2>
            </div>

            <div class="space-y-6">
                <!-- FAQ 1 -->
                <details class="style="background-color: var(--color-background);" rounded-lg p-6 border style="border-color: var(--color-border);"">
                    <summary class="font-bold text-lg style="color: var(--color-foreground);" cursor-pointer">
                        Is my financial data safe?
                    </summary>
                    <p class="mt-4 style="color: var(--color-muted-foreground);"">
                        Absolutely. We use bank-level AES-256 encryption and never store your bank login credentials. All connections go through Plaid, the same service used by Venmo, Robinhood, and thousands of financial apps. We're SOC 2 compliant and will never sell your data.
                    </p>
                </details>

                <!-- FAQ 2 -->
                <details class="style="background-color: var(--color-background);" rounded-lg p-6 border style="border-color: var(--color-border);"">
                    <summary class="font-bold text-lg style="color: var(--color-foreground);" cursor-pointer">
                        How does the free trial work?
                    </summary>
                    <p class="mt-4 style="color: var(--color-muted-foreground);"">
                        Start with our Free plan forever—no credit card required. When you're ready for Premium features, get a 14-day free trial. Cancel anytime during the trial and you won't be charged. After the trial, it's $9.99/month or $99/year (save $20).
                    </p>
                </details>

                <!-- FAQ 3 -->
                <details class="style="background-color: var(--color-background);" rounded-lg p-6 border style="border-color: var(--color-border);"">
                    <summary class="font-bold text-lg style="color: var(--color-foreground);" cursor-pointer">
                        Which banks do you support?
                    </summary>
                    <p class="mt-4 style="color: var(--color-muted-foreground);"">
                        We support 12,000+ financial institutions through Plaid, including all major banks: Chase, Bank of America, Wells Fargo, Capital One, Citibank, US Bank, and thousands more. If your bank offers online banking, we likely support it.
                    </p>
                </details>

                <!-- FAQ 4 -->
                <details class="style="background-color: var(--color-background);" rounded-lg p-6 border style="border-color: var(--color-border);"">
                    <summary class="font-bold text-lg style="color: var(--color-foreground);" cursor-pointer">
                        How is this different from Mint or YNAB?
                    </summary>
                    <p class="mt-4 style="color: var(--color-muted-foreground);"">
                        Unlike Mint (which shut down), we don't show ads or sell your data. Unlike YNAB ($15/month), we're easier to use and focus on YOUR paycheck schedule—not calendar months. We combine the best of both: automatic syncing (like Mint) with smart budgeting (like YNAB), plus AI features they don't have.
                    </p>
                </details>

                <!-- FAQ 5 -->
                <details class="style="background-color: var(--color-background);" rounded-lg p-6 border style="border-color: var(--color-border);"">
                    <summary class="font-bold text-lg style="color: var(--color-foreground);" cursor-pointer">
                        Can I really save $500+/year?
                    </summary>
                    <p class="mt-4 style="color: var(--color-muted-foreground);"">
                        Yes. Our users save money in three ways: (1) Avoiding late fees ($30-40 per missed bill), (2) Finding forgotten subscriptions (average $214/month wasted), and (3) Negotiating bills (average $200-600/year savings). That adds up to $500-1,000+ per year for most users.
                    </p>
                </details>

                <!-- FAQ 6 -->
                <details class="style="background-color: var(--color-background);" rounded-lg p-6 border style="border-color: var(--color-border);"">
                    <summary class="font-bold text-lg style="color: var(--color-foreground);" cursor-pointer">
                        Do you have a mobile app?
                    </summary>
                    <p class="mt-4 style="color: var(--color-muted-foreground);"">
                        Not yet, but it's coming in Q3 2025! For now, our web app is fully mobile-responsive and works great on your phone. You can add it to your home screen for an app-like experience. Native iOS and Android apps are in development.
                    </p>
                </details>

                <!-- FAQ 7 -->
                <details class="style="background-color: var(--color-background);" rounded-lg p-6 border style="border-color: var(--color-border);"">
                    <summary class="font-bold text-lg style="color: var(--color-foreground);" cursor-pointer">
                        Can I cancel anytime?
                    </summary>
                    <p class="mt-4 style="color: var(--color-muted-foreground);"">
                        Yes! Cancel with just 2 clicks from your account settings. No phone calls, no hassle. Your data is yours—export it anytime. If you cancel Premium, you'll drop to the Free plan and keep basic features forever.
                    </p>
                </details>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-blue-600 to-purple-600 text-white">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-4xl sm:text-5xl font-bold mb-6">
                Ready to Never Pay Another Late Fee?
            </h2>
            <p class="text-xl mb-8 opacity-90">
                Join 10,000+ users who save $500+/year with Unobtuse Ledger.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                @auth
                    <a href="{{ url('/dashboard') }}" class="bg-white hover:bg-gray-100 text-chart-3 px-10 py-4 rounded-xl font-bold text-lg shadow-lg transition transform hover:-translate-y-0.5">
                        Go to Dashboard →
                    </a>
                @else
                    <a href="{{ route('register') }}" class="bg-white hover:bg-gray-100 text-chart-3 px-10 py-4 rounded-xl font-bold text-lg shadow-lg transition transform hover:-translate-y-0.5">
                        Start Free Trial →
                    </a>
                    <a href="{{ route('login') }}" class="bg-transparent hover:bg-white/10 text-white px-10 py-4 rounded-xl font-bold text-lg border-2 border-white transition">
                        Log In
                    </a>
                @endauth
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-6 text-sm opacity-75">
                <span>✓ Free to start</span>
                <span>✓ No credit card required</span>
                <span>✓ Cancel anytime</span>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 style="color: var(--color-muted-foreground);" py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <!-- Brand -->
                <div>
                    <div class="mb-4">
                        <img src="{{ asset('logos/unobtuse-ledger-white-logo.svg') }}" alt="Unobtuse Ledger" class="h-8">
                    </div>
                    <p class="text-sm">
                        AI-powered personal finance management. Never miss a payment, never waste a dollar.
                    </p>
                </div>

                <!-- Product -->
                <div>
                    <h4 class="font-bold text-white mb-4">Product</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#features" class="hover:text-white transition">Features</a></li>
                        <li><a href="#pricing" class="hover:text-white transition">Pricing</a></li>
                        <li><a href="#" class="hover:text-white transition">Roadmap</a></li>
                        <li><a href="#" class="hover:text-white transition">Changelog</a></li>
                    </ul>
                </div>

                <!-- Company -->
                <div>
                    <h4 class="font-bold text-white mb-4">Company</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition">About Us</a></li>
                        <li><a href="#" class="hover:text-white transition">Blog</a></li>
                        <li><a href="#" class="hover:text-white transition">Careers</a></li>
                        <li><a href="#" class="hover:text-white transition">Contact</a></li>
                    </ul>
                </div>

                <!-- Legal -->
                <div>
                    <h4 class="font-bold text-white mb-4">Legal</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white transition">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-white transition">Security</a></li>
                        <li><a href="#" class="hover:text-white transition">GDPR</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center text-sm">
                <p>© 2025 Unobtuse Ledger. All rights reserved. Built with ❤️ by GabeMade.it</p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="https://twitter.com/UnobtuseLedger" class="hover:text-white transition" target="_blank" rel="noopener">Twitter</a>
                    <a href="#" class="hover:text-white transition">Discord</a>
                    <a href="#" class="hover:text-white transition">GitHub</a>
                </div>
            </div>
        </div>
    </footer>

    </body>
</html>
