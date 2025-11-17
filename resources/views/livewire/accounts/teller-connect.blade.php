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

    <!-- Teller Connect Widget (hidden by default) -->
    <div id="teller-connect-widget" class="teller-widget-wrapper"></div>
</div>

@script
// Store config globally
window.tellerConnectConfig = @json($connectConfig);

// Listen for Livewire events
Livewire.on('initiateTellerConnect', () => {
    setTimeout(() => window.initializeTellerConnect(), 100);
});

window.initializeTellerConnect = function() {
    // Ensure Teller SDK is loaded
    if (typeof window.Teller === 'undefined') {
        console.error('Teller Connect SDK not loaded');
        alert('Teller SDK failed to load. Please refresh the page.');
        return;
    }
    
    if (!window.tellerConnectConfig) {
        console.error('Teller Connect config not available');
        alert('Failed to load Teller configuration. Please try again.');
        return;
    }
    
    try {
        let configData = window.tellerConnectConfig;
        if (typeof configData === 'string') {
            configData = JSON.parse(configData);
        }
        
        console.log('Initializing Teller Connect with config:', configData);
        
        // Initialize Teller Connect
        window.Teller.init({
            appId: configData.appId,
            environment: configData.environment,
            onSuccess: (enrollment) => {
                console.log('Teller enrollment successful:', enrollment);
                Livewire.dispatch('tellerEnrollmentSuccess', { enrollmentToken: enrollment.token });
            },
            onError: (error) => {
                console.error('Teller enrollment error:', error);
                Livewire.dispatch('tellerEnrollmentError', { 
                    errorCode: error.code || 'unknown_error',
                    errorMessage: error.message || 'An error occurred during enrollment'
                });
            },
            onExit: () => {
                console.log('Teller Connect closed');
            }
        });
        
        // Open the widget
        window.Teller.open();
    } catch (err) {
        console.error('Failed to initialize Teller Connect:', err);
        alert('Failed to initialize bank linking. Please try again.');
    }
};
@endscript

