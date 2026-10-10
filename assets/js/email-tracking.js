/* Email-based Shipment Tracking with OTP Verification */

const EmailTracking = {
    apiBaseUrl: null,
    currentEmail: null,
    sessionTimeout: null,

    init: function () {
        this.apiBaseUrl = this.getApiBaseUrl();
        this.setupEventListeners();
        this.checkExistingSession();
    },

    getApiBaseUrl: function () {
        const pathSegments = window.location.pathname.split('/').filter(s => s);
        if (pathSegments.length > 0 && !pathSegments[0].includes('.')) {
            return '/' + pathSegments[0] + '/api';
        }
        return '/api';
    },

    setupEventListeners: function () {
        const emailForm = document.getElementById('email-tracking-form');
        const otpForm = document.getElementById('otp-verification-form');

        if (emailForm) {
            emailForm.addEventListener('submit', (e) => this.handleEmailSubmit(e));
        }

        if (otpForm) {
            otpForm.addEventListener('submit', (e) => this.handleOTPSubmit(e));
        }

        // OTP auto-focus next input
        this.setupOTPInputs();
    },

    setupOTPInputs: function () {
        const inputs = document.querySelectorAll('.otp-input');
        inputs.forEach((input, index) => {
            input.addEventListener('keyup', (e) => {
                if (e.target.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                if (e.key === 'Backspace' && e.target.value.length === 0 && index > 0) {
                    inputs[index - 1].focus();
                }
            });
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                if (!/^\d{6}$/.test(paste)) return;
                paste.split('').forEach((char, i) => {
                    if (inputs[i]) inputs[i].value = char;
                });
                inputs[5].focus();
            });
        });
    },

    handleEmailSubmit: function (e) {
        e.preventDefault();
        const email = document.getElementById('email-input').value.trim();

        if (!this.validateEmail(email)) {
            this.showError('Please enter a valid email address');
            return;
        }

        this.currentEmail = email;
        this.requestOTP(email);
    },

    validateEmail: function (email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    requestOTP: function (email) {
        const btn = document.querySelector('#email-tracking-form button');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending OTP...';

        fetch(`${this.apiBaseUrl}/track-email.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email })
        })
            .then(r => {
                return r.json();
            })
            .then(response => {
                btn.disabled = false;
                btn.innerHTML = originalText;

                if (response.success) {
                    this.showOTPForm();
                    this.showSuccess(`OTP sent to ${email}. Check your email!`);
                    this.startSessionTimer();
                } else {
                    this.showError(response.message || 'Failed to send OTP');
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                this.showError('Error requesting OTP: ' + error.message);
            });
    },

    handleOTPSubmit: function (e) {
        e.preventDefault();
        const inputs = document.querySelectorAll('.otp-input');
        const otp = Array.from(inputs).map(i => i.value).join('');

        if (otp.length !== 6) {
            this.showError('Please enter all 6 digits');
            return;
        }

        this.verifyOTP(this.currentEmail, otp);
    },

    verifyOTP: function (email, otp) {
        const btn = document.querySelector('#otp-verification-form button');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';

        fetch(`${this.apiBaseUrl}/track-email.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email: email,
                otp: otp
            })
        })
            .then(r => {
                return r.json();
            })
            .then(response => {
                btn.disabled = false;
                btn.innerHTML = originalText;

                if (response.success) {
                    this.showSuccess('Email verified successfully!');
                    this.hideOTPForm();
                    this.loadShipments(email);
                } else {
                    this.showError(response.message || 'Invalid OTP');
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                this.showError('Error verifying OTP: ' + error.message);
            });
    },

    loadShipments: function (email) {
        const resultsContainer = document.getElementById('tracking-results');
        resultsContainer.innerHTML = '<div class="loading"><div class="spinner"></div> Loading your shipments...</div>';

        fetch(`${this.apiBaseUrl}/track-email.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email: email,
                action: 'get_shipments'
            })
        })
            .then(r => {
                return r.text();
            })
            .then(responseText => {
                // Try to parse as JSON
                try {
                    const response = JSON.parse(responseText);

                    if (response.data && response.data.length > 0) {
                        this.displayShipments(response.data);
                    } else {
                        resultsContainer.innerHTML = `<div class="alert alert-warning">
                        <strong>No Shipments Found</strong><br>
                        Message: ${response.message || 'Unknown'}<br>
                        <small>Email: ${response.debug ? response.debug.email_cleaned : email}</small>
                    </div>`;
                    }
                } catch (parseError) {
                    resultsContainer.innerHTML = `<div class="alert alert-danger">
                    <strong>JSON Parse Error</strong><br>
                    Response: ${responseText.substring(0, 200)}...
                </div>`;
                }
            })
            .catch(error => {
                resultsContainer.innerHTML = `<div class="alert alert-danger">
                <strong>Error:</strong> ${error.message}
            </div>`;
            });
    },

    displayShipments: function (shipments) {
        const container = document.getElementById('tracking-results');

        if (!shipments || shipments.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No shipments found for this email address</div>';
            return;
        }

        let html = '<div class="shipments-list">';

        shipments.forEach(shipment => {
            const statusBadge = `<span class="badge badge-${shipment.status}">${shipment.status.replace(/_/g, ' ').toUpperCase()}</span>`;
            html += `
                <div class="shipment-card" data-tracking="${shipment.tracking_number}">
                    <div class="shipment-header">
                        <div class="shipment-number">${shipment.tracking_number}</div>
                        <div class="shipment-status">${statusBadge}</div>
                    </div>
                    <div class="shipment-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p><strong>Shipper:</strong> ${shipment.shipper_name || 'N/A'}</p>
                                <p><strong>Origin:</strong> ${shipment.origin || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>ETA:</strong> ${shipment.estimated_delivery ? new Date(shipment.estimated_delivery).toLocaleDateString() : 'N/A'}</p>
                                <p><strong>Destination:</strong> ${shipment.destination || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                    <div class="shipment-footer">
                        <button class="btn btn-sm btn-primary view-details-btn" data-tracking="${shipment.tracking_number}">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;

        // Add click handlers for view details
        container.querySelectorAll('.view-details-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const tracking = btn.getAttribute('data-tracking');
                this.viewShipmentDetails(tracking);
            });
        });
    },

    viewShipmentDetails: function (trackingNumber) {
        const mainCard = document.getElementById('mainTrackingCard');
        if (!mainCard) return;

        mainCard.style.display = 'block';
        mainCard.scrollIntoView({ behavior: 'smooth', block: 'start' });

        // Fetch shipment data
        fetch(`${this.apiBaseUrl}/track.php?tracking_number=${encodeURIComponent(trackingNumber)}`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(r => r.json())
        .then(response => {
            if (response.data) {
                const shipment = response.data;

                const shipmentNumberEl = mainCard.querySelector('.shipment-number');
                if (shipmentNumberEl) {
                    shipmentNumberEl.textContent = shipment.tracking_number;
                }

                const statusEl = mainCard.querySelector('.status-value');
                if (statusEl) {
                    statusEl.textContent = (shipment.status || 'N/A').replace(/_/g, ' ').toUpperCase();
                }

                const infoItems = mainCard.querySelectorAll('.info-item');
                const fields = [
                    { label: 'Origin:', value: shipment.origin || 'N/A' },
                    { label: 'Destination:', value: shipment.destination || 'N/A' },
                    { label: 'Packages:', value: shipment.num_packages || shipment.package_description || 'N/A' },
                    { label: 'Weight:', value: (shipment.gross_weight || 'N/A') + ' KGS' },
                    { label: 'Product:', value: shipment.goods_description || 'N/A' },
                    { label: 'Carrier:', value: shipment.carrier || '—' },
                    { label: 'Mode:', value: shipment.mode || '—' },
                    { label: 'Payment:', value: shipment.payment_mode || '—' },
                    { label: 'Total Freight:', value: shipment.total_freight || '—' },
                    { label: 'ETA:', value: shipment.estimated_delivery ? new Date(shipment.estimated_delivery).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'}) : '—' },
                    { label: 'Departure:', value: shipment.etd ? new Date(shipment.etd).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'}) : '—' },
                    { label: 'Comments:', value: shipment.comments || '—' }
                ];

                infoItems.forEach((item, index) => {
                    if (fields[index]) {
                        const label = item.querySelector('.info-label');
                        const value = item.querySelector('.info-value');
                        if (label) label.textContent = fields[index].label;
                        if (value) value.textContent = fields[index].value;
                    }
                });

                const shipperName = mainCard.querySelector('.col-half:first-child .address-details');
                if (shipperName) {
                    shipperName.innerHTML = shipment.shipper_name + '<br><small style="color:#5e7e8f;">Origin: ' + (shipment.origin || 'N/A') + '</small>';
                }

                const receiverName = mainCard.querySelector('.col-half:last-child .address-details');
                if (receiverName) {
                    receiverName.innerHTML = shipment.consignee_name + '<br><small style="color:#5e7e8f;">Destination: ' + (shipment.destination || 'N/A') + '</small>';
                }

                this.generateQRCodeEmail(trackingNumber);
            }
        })
        .catch(error => {
        });

        // Fetch tracking history
        this.loadTrackingHistoryEmail(trackingNumber);
    },

    generateQRCodeEmail: function(trackingNumber) {
        const canvas = document.getElementById('qr-canvas');
        const trackingLinkInput = document.getElementById('tracking-link');
        const basePath = this.apiBaseUrl ? this.apiBaseUrl.replace(/\/api$/i, '') : '';
        const trackLink = window.location.origin + basePath + '/?tracking_number=' + encodeURIComponent(trackingNumber);

        if (trackingLinkInput) {
            trackingLinkInput.value = trackLink;
        }

        if (!canvas) return;

        try {
            if (typeof QRious !== 'undefined') {
                var qr = new QRious({
                    element: canvas,
                    value: trackLink,
                    size: 200,
                    level: 'H'
                });

                const downloadBtn = document.getElementById('download-qr');
                if (downloadBtn) {
                    downloadBtn.onclick = function(e) {
                        e.preventDefault();
                        const dataUrl = canvas.toDataURL('image/png');
                        const a = document.createElement('a');
                        a.href = dataUrl;
                        a.download = 'shipment-qr-' + trackingNumber + '.png';
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                    };
                }
            }
        } catch (e) {
        }
    },

    loadTrackingHistoryEmail: function(trackingNumber) {
        fetch(`${this.apiBaseUrl}/track.php/history?number=${encodeURIComponent(trackingNumber)}`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(r => r.json())
        .then(response => {
            if (response.data || response.success) {
                const history = response.data || [];
                this.displayTrackingHistoryEmail(history);
            }
        })
        .catch(error => {
        });
    },

    displayTrackingHistoryEmail: function(history) {
        const historyPanel = document.querySelector('.history-panel');
        if (!historyPanel) return;

        if (!history || history.length === 0) {
            historyPanel.innerHTML = '<h6>Tracking History</h6><div class="history-item" style="text-align:center;padding:20px;">No tracking history available yet</div>';
            return;
        }

        let html = '<h6>Tracking History</h6>';
        history.forEach((item) => {
            const date = new Date(item.created_at);
            const formattedDate = date.toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true});
            html += `
                <div class="history-item">
                    <div class="history-date">${formattedDate}</div>
                    <div><span class="history-status">${(item.status || '').replace(/_/g, ' ').toUpperCase()}</span></div>
                    <div class="history-location"><strong>Location:</strong> ${item.location || 'N/A'}</div>
                    ${item.remarks ? `<div class="history-remarks"><strong>Remarks:</strong> ${item.remarks}</div>` : ''}
                </div>
            `;
        });

        historyPanel.innerHTML = html;
    },

    showOTPForm: function () {
        const emailSection = document.getElementById('email-tracking-section');
        const otpSection = document.getElementById('otp-verification-section');
        if (emailSection) emailSection.style.display = 'none';
        if (otpSection) otpSection.style.display = 'block';
        const input = document.querySelector('.otp-input');
        if (input) input.focus();
    },

    hideOTPForm: function () {
        const otpSection = document.getElementById('otp-verification-section');
        const results = document.getElementById('tracking-results');
        if (otpSection) otpSection.style.display = 'none';
        if (results) results.style.display = 'block';
    },

    resetTracking: function () {
        const emailSection = document.getElementById('email-tracking-section');
        const otpSection = document.getElementById('otp-verification-section');
        const results = document.getElementById('tracking-results');
        const emailInput = document.getElementById('email-input');

        if (emailSection) emailSection.style.display = 'block';
        if (otpSection) otpSection.style.display = 'none';
        if (results) results.innerHTML = '';
        if (emailInput) emailInput.value = '';
        document.querySelectorAll('.otp-input').forEach(i => i.value = '');
        this.currentEmail = null;
        clearTimeout(this.sessionTimeout);
        this.showInfo('Ready for new search');
    },

    startSessionTimer: function () {
        clearTimeout(this.sessionTimeout);
        const timer = document.getElementById('session-timer');
        let remaining = 20 * 60; // 20 minutes in seconds

        const updateTimer = () => {
            const mins = Math.floor(remaining / 60);
            const secs = remaining % 60;
            if (timer) {
                timer.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
            }

            remaining--;
            if (remaining < 0) {
                this.sessionExpired();
            } else {
                this.sessionTimeout = setTimeout(updateTimer, 1000);
            }
        };

        updateTimer();
    },

    sessionExpired: function () {
        this.showWarning('Your verification session has expired. Please verify again.');
        this.resetTracking();
    },

    checkExistingSession: function () {
        const params = new URLSearchParams(window.location.search);
        if (params.has('verified_email')) {
            this.currentEmail = params.get('verified_email');
            this.loadShipments(this.currentEmail);
            this.startSessionTimer();
        }
    },

    showError: function (message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.getElementById('tracking-alerts').appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    },

    showSuccess: function (message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show';
        alert.innerHTML = `<i class="fas fa-check-circle"></i> ${message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.getElementById('tracking-alerts').appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    },

    showInfo: function (message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-info alert-dismissible fade show';
        alert.innerHTML = `<i class="fas fa-info-circle"></i> ${message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.getElementById('tracking-alerts').appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    },

    showWarning: function (message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-warning alert-dismissible fade show';
        alert.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.getElementById('tracking-alerts').appendChild(alert);
        setTimeout(() => alert.remove(), 8000);
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    EmailTracking.init();
});
