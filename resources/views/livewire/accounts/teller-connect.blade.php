<div class="teller-connect-container" 
     x-data 
     x-on:teller-enrollment-success.window="
        console.log('Caught teller-enrollment-success event:', $event.detail);
        $wire.handleEnrollmentSuccess(
            $event.detail.accessToken,
            $event.detail.enrollmentId,
            $event.detail.institutionName,
            $event.detail.enrollmentData
        )
     ">
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

<script>
// Initialize Teller Connect configuration and event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Store config globally
    window.tellerConnectConfig = @json($connectConfig);
    
    // Listen for Livewire events
    Livewire.on('initiateTellerConnect', function() {
        setTimeout(() => window.initializeTellerConnect(), 100);
    });
    
    window.initializeTellerConnect = function() {
        // Ensure Teller SDK is loaded
        if (typeof window.TellerConnect === 'undefined') {
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
            
            // Initialize Teller Connect using TellerConnect.setup()
            window.tellerConnectInstance = window.TellerConnect.setup({
                applicationId: configData.appId,
                environment: configData.environment,
                onSuccess: (enrollment) => {
                    console.log('Teller enrollment successful - Full object:', enrollment);
                    console.log('Enrollment keys:', Object.keys(enrollment));
                    
                    // Teller Connect returns the access token DIRECTLY in the callback
                    // Structure: { accessToken: "...", enrollment: { id: "...", institution: {...} } }
                    const accessToken = enrollment.accessToken;
                    const enrollmentId = enrollment.enrollment?.id;
                    const institutionName = enrollment.enrollment?.institution?.name;
                    
                    console.log('Access Token:', accessToken ? 'Present' : 'Missing');
                    console.log('Enrollment ID:', enrollmentId);
                    console.log('Institution:', institutionName);
                    
                    // Dispatch event to window for Livewire to catch
                    const eventData = {
                        accessToken: accessToken,
                        enrollmentId: enrollmentId,
                        institutionName: institutionName,
                        enrollmentData: enrollment
                    };
                    
                    console.log('Dispatching tellerEnrollmentSuccess with:', eventData);
                    
                    // Dispatch custom event on window
                    window.dispatchEvent(new CustomEvent('teller-enrollment-success', { 
                        detail: eventData 
                    }));
                },
                onExit: () => {
                    console.log('Teller Connect closed');
                },
                onFailure: (error) => {
                    console.error('Teller enrollment error:', error);
                    Livewire.dispatch('tellerEnrollmentError', { 
                        errorCode: error.code || 'unknown_error',
                        errorMessage: error.message || 'An error occurred during enrollment'
                    });
                }
            });
            
            // Open the widget
            window.tellerConnectInstance.open();
        } catch (err) {
            console.error('Failed to initialize Teller Connect:', err);
            alert('Failed to initialize bank linking. Please try again.');
        }
    };
});
</script>

