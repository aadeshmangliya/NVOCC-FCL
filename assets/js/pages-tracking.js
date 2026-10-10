/* Tracking Page Script */
document.addEventListener('DOMContentLoaded', () => {
    if (typeof TrackingPage !== 'undefined') {
        TrackingPage.init();
    }

    // Auto-show documents when tracking number is entered
    const trackingInput = document.getElementById('tracking-number');
    if (trackingInput) {
        trackingInput.addEventListener('change', function() {
            const trackingNumber = this.value;
            if (trackingNumber && typeof DocumentDropdown !== 'undefined' && DocumentDropdown.selectedCompanyKey) {
                // Could auto-load documents for this tracking number
            }
        });
    }
});
