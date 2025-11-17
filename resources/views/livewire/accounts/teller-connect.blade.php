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
<script>
document.addEventListener('livewire:navigated', () => {
    const component = Livewire.find('@this.getId()');
    if (!component) return;
    
    // Listen for showConnect property changes
    Livewire.on('initiateTellerConnect', () => {
        setTimeout(() => initializeTellerConnect(), 100);
    });
    
    window.showTellerConnect = function() {
        initializeTellerConnect();
    };
});

function initializeTellerConnect() {
    // Ensure Teller SDK is loaded
    if (typeof window.Teller === 'undefined') {
        console.error('Teller Connect SDK not loaded');
        alert('Teller SDK failed to load. Please refresh the page.');
        return;
    }
    
    const config = @json($connectConfig);
    if (!config) {
        console.error('Teller Connect config not available');
        alert('Failed to load Teller configuration. Please try again.');
        return;
    }
    
    try {
        const configData = typeof config === 'string' ? JSON.parse(config) : config;
        
        // Initialize Teller Connect
        window.Teller.init({
            appId: configData.appId,
            environment: configData.environment,
            onSuccess: (enrollment) => {
                console.log('Teller enrollment successful:', enrollment);
                // Send enrollment token back to Livewire component
                Livewire.dispatch('handleEnrollmentSuccess', { enrollmentToken: enrollment.token });
            },
            onError: (error) => {
                console.error('Teller enrollment error:', error);
                Livewire.dispatch('handleEnrollmentError', { 
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
}
</script>
@endscript

