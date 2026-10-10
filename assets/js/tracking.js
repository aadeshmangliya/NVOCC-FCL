/* ==================
   Tracking Page JS
   ================== */

const TrackingPage = {
    init: function() {
        this.setupTrackingForm();
    },

    getApiBaseUrl: function() {
        const path = window.location.pathname.replace(/\/[^\/]*$/, '');
        return (path || '') + '/api';
    },

    setupTrackingForm: function() {
        const form = document.getElementById('tracking-form');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const trackingNumber = document.getElementById('tracking-number').value;
            this.searchTracking(trackingNumber);
        });
    },

    searchTracking: function(trackingNumber) {
        const resultsContainer = document.getElementById('tracking-results');
        resultsContainer.innerHTML = '<div class="loading"><div class="spinner"></div> Searching...</div>';

        fetch(`${this.getApiBaseUrl()}/track.php?tracking_number=${encodeURIComponent(trackingNumber)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.displayTrackingInCard(data.data, trackingNumber);
                } else {
                    resultsContainer.innerHTML = '<div class="alert alert-warning">Shipment not found. Please check the tracking number.</div>';
                }
            })
            .catch(error => {
                resultsContainer.innerHTML = '<div class="alert alert-danger">Error searching shipment. Please try again.</div>';
            });
    },

    displayTrackingInCard: function(shipment, trackingNumber) {
        this.loadTrackingHistory(trackingNumber);

        const mainCard = document.getElementById('mainTrackingCard');
        if (!mainCard) return;

        mainCard.style.display = 'block';
        mainCard.scrollIntoView({ behavior: 'smooth', block: 'start' });

        const shipmentNumberEl = mainCard.querySelector('.shipment-number');
        if (shipmentNumberEl) {
            shipmentNumberEl.textContent = shipment.tracking_number;
        }

        const statusEl = mainCard.querySelector('.status-value');
        if (statusEl) {
            statusEl.textContent = (shipment.status || 'N/A').replace('_', ' ').toUpperCase();
        }

        this.updateDetailsSection(mainCard, shipment);
    },

    updateDetailsSection: function(mainCard, shipment) {
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
    },

    loadTrackingHistory: function(trackingNumber) {
        const historyPanel = document.querySelector('.history-panel');
        if (!historyPanel) return;

        historyPanel.innerHTML = '<h6>Tracking History</h6><div class="loading"><div class="spinner"></div> Loading...</div>';

        fetch(`${this.getApiBaseUrl()}/track.php/history?number=${encodeURIComponent(trackingNumber)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.displayTrackingHistory(data.data);
                }
            })
            .catch(error => {});
    },

    displayTrackingHistory: function(history) {
        const historyPanel = document.querySelector('.history-panel');
        if (!historyPanel) return;

        let html = '<h6>Tracking History</h6>';

        if (history && history.length > 0) {
            history.forEach((item) => {
                const date = new Date(item.created_at);
                const formattedDate = date.toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true});

                html += `
                    <div class="history-item">
                        <div class="history-date">${formattedDate}</div>
                        <div><span class="history-status">${(item.status || '').replace('_', ' ').toUpperCase()}</span></div>
                        <div class="history-location"><strong>Location:</strong> ${item.location || 'N/A'}</div>
                        ${item.remarks ? `<div class="history-remarks"><strong>Remarks:</strong> ${item.remarks}</div>` : ''}
                    </div>
                `;
            });
        } else {
            html += '<div class="history-item" style="text-align:center;padding:20px;">No tracking history available yet</div>';
        }

        historyPanel.innerHTML = html;
    }
};

