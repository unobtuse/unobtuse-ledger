<div class="teller-connect-container" x-data="tellerConnectComponent()">
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

    <!-- Teller Connect Widget (hidden by default) -->
    <div id="teller-connect-widget" class="teller-widget-wrapper"></div>

    @script
        <script>
            function tellerConnectComponent() {
                return {
                    config: @json($connectConfig),
                    showWidget: @json($showConnect),
                    
                    init() {
                        // Re-render widget when showConnect changes
                        this.$watch('showWidget', (newValue) => {
                            if (newValue) {
                                this.$nextTick(() => this.initializeTellerConnect());
                            }
                        });
                    },
                    
                    initializeTellerConnect() {
                        // Ensure Teller SDK is loaded
                        if (typeof window.Teller === 'undefined') {
                            console.error('Teller Connect SDK not loaded');
                            @this.dispatch('tellerEnrollmentError', {
                                errorCode: 'sdk_not_loaded',
                                errorMessage: 'Teller SDK failed to load'
                            });
                            return;
                        }
                        
                        if (!this.config) {
                            console.error('Teller Connect config not available');
                            return;
                        }
                        
                        const configData = JSON.parse(this.config);
                        
                        // Initialize Teller Connect
                        window.Teller.init({
                            appId: configData.appId,
                            environment: configData.environment,
                            onSuccess: (enrollment) => {
                                console.log('Teller enrollment successful:', enrollment);
                                // Send enrollment token back to Livewire component
                                @this.call('handleEnrollmentSuccess', enrollment.token);
                            },
                            onError: (error) => {
                                console.error('Teller enrollment error:', error);
                                @this.call('handleEnrollmentError', 
                                    error.code || 'unknown_error',
                                    error.message || 'An error occurred during enrollment'
                                );
                            },
                            onExit: () => {
                                console.log('Teller Connect closed');
                            }
                        });
                        
                        // Open the widget
                        window.Teller.open();
                    }
                }
            }
        </script>
    @endscript
</div>

