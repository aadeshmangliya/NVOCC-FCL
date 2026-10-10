/* Print Tracking Functionality */
document.addEventListener('DOMContentLoaded', function() {
    // Hide OTP verification section by default
    const otpSection = document.getElementById('otp-verification-section');
    if (otpSection) {
        otpSection.style.display = 'none';
    }

    // Print functionality
    var printBtn = document.getElementById('printTrackingBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            var originalTitle = document.title;
            var trackingNumber = document.querySelector('.shipment-number');
            if (trackingNumber) {
                document.title = 'Shipment_' + trackingNumber.textContent.trim();
            }
            window.print();
            document.title = originalTitle;
        });
    }
});
