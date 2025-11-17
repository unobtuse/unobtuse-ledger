<div class="teller-connect-container">
    <!-- Success Message -->
    @if ($success)
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            <p class="font-semibold">✓ {{ $success }}</p>
        </div>
    @endif

    <!-- Error Message -->
    @if ($error)
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <p class="font-semibold">✗ {{ $error }}</p>
        </div>
    @endif

    <!-- Link Bank Account Button -->
    <div class="flex items-center justify-center py-8">
        <button
            wire:click="initiateTellerConnect"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-50 cursor-not-allowed"
            class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors duration-200 disabled:opacity-50"
        >
            <span wire:loading.remove>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Link Bank Account
            </span>

            <span wire:loading>
                <svg class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Connecting...
            </span>
        </button>
    </div>

    <!-- Teller Connect Widget (hidden by default) -->
    <div id="teller-connect-widget" class="teller-widget-wrapper" style="display: none;">
        <!-- Will be rendered by Teller Connect SDK -->
    </div>

    @script
        <script>
            // Initialize Teller Connect when component loads
            document.addEventListener('livewire:navigated', function() {
                window.TellerConnect = window.TellerConnect || {};
            });

            // Listen for when component is ready
            Livewire.on('component-ready', function() {
                console.log('Teller Connect component ready');
            });

            // Handle initiation of Teller Connect
            @this.on('$set', (property, value) => {
                if (property === 'showConnect' && value === true) {
                    setTimeout(() => {
                        initializeTellerConnect();
                    }, 100);
                }
            });

            function initializeTellerConnect() {
                const config = @json($connectConfig);

                if (!config || Object.keys(config).length === 0) {
                    console.error('Teller Connect config not available');
                    return;
                }

                const configData = JSON.parse(config);
                const widgetElement = document.getElementById('teller-connect-widget');

                if (!widgetElement) {
                    console.error('Widget element not found');
                    return;
                }

                // Show the widget
                widgetElement.style.display = 'block';

                // Initialize Teller Connect
                const options = {
                    applicationId: configData.appId,
                    environment: configData.environment,
                    userId: configData.userId,
                    products: ['accounts', 'balances', 'transactions', 'identity'],
                    selectAccount: true,
                    onSuccess: (enrollment) => {
                        console.log('Teller enrollment successful:', enrollment);
                        // Send enrollment token back to Livewire component
                        @this.dispatch('tellerEnrollmentSuccess', {
                            enrollmentToken: enrollment.token || enrollment.id
                        });
                        widgetElement.style.display = 'none';
                    },
                    onError: (error) => {
                        console.error('Teller enrollment error:', error);
                        @this.dispatch('tellerEnrollmentError', {
                            errorCode: error.code || 'unknown',
                            errorMessage: error.message || 'An error occurred'
                        });
                        widgetElement.style.display = 'none';
                    },
                };

                // Initialize Teller Connect with configuration
                if (window.TellerConnect && window.TellerConnect.initialize) {
                    window.TellerConnect.initialize(widgetElement, options);
                } else {
                    console.error('Teller Connect SDK not loaded');
                }
            }
        </script>
    @endscript
</div>

