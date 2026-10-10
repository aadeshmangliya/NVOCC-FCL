const ShipmentsChargesPage = {
    state: {
        shipments: [],
        charges: [],
        selectedTracking: null,
    },

    init() {
        this.bindEvents();
        this.loadData();
    },

    bindEvents() {
        // Setup shipment selectors in modals
        document.querySelectorAll('.shipment-selector').forEach(select => {
            select.addEventListener('change', (e) => {
                this.selectShipment(e.target.value, e.target.id);
            });
        });

        document.getElementById('create-detention-btn')?.addEventListener('click', () => {
            this.createDetentionCharge();
        });

        document.getElementById('reset-detention-btn')?.addEventListener('click', () => {
            this.resetDetentionForm();
        });

        document.getElementById('create-local-btn')?.addEventListener('click', () => {
            this.createLocalCharge();
        });

        document.getElementById('reset-local-btn')?.addEventListener('click', () => {
            this.resetLocalForm();
        });

        document.getElementById('detention-rate')?.addEventListener('input', () => {
            this.updateDetentionAmount();
        });
    },

    async loadData() {
        try {
            const response = await App.api.get('shipments-charges');
            if (!response.success) {
                throw new Error(response.message || 'Unable to load shipments and charges.');
            }

            this.state.shipments = response.shipments || [];
            this.state.charges = response.charges || [];
            this.renderShipmentOptions();
        } catch (error) {
            this.showMessage('danger', error.message || 'Unable to load data.');
        }
    },

    renderShipmentOptions() {
        // Populate both selectors
        document.querySelectorAll('.shipment-selector').forEach(select => {
            select.innerHTML = '<option value="">Select a shipment</option>';
            if (!this.state.shipments.length) {
                select.innerHTML = '<option value="">No shipments found</option>';
            } else {
                this.state.shipments.forEach((shipment) => {
                    const option = document.createElement('option');
                    option.value = shipment.tracking_number;
                    option.textContent = `${shipment.tracking_number} - ${shipment.consignee_name || 'N/A'}`;
                    select.appendChild(option);
                });
            }
        });

        // We don't auto-select a shipment anymore since we want to see all charges by default
        this.clearSummary();
        this.renderTables();
    },

    selectShipment(tracking, sourceId = '') {
        const shipment = this.state.shipments.find((item) => item.tracking_number === tracking);
        if (!shipment) {
            this.state.selectedTracking = null;
            if (sourceId === 'detention-shipment-select') this.clearSummary();
            return;
        }

        this.state.selectedTracking = tracking;
        
        // Sync both selectors
        document.querySelectorAll('.shipment-selector').forEach(select => {
            if (select.value !== tracking) {
                select.value = tracking;
            }
        });

        if (sourceId === 'detention-shipment-select' || !sourceId) {
            document.getElementById('consignee-name').value = shipment.consignee_name || '';
        document.getElementById('consignee-email').value = shipment.consignee_email || '';

        const arrivalDate = shipment.arrival_date || shipment.eta || shipment.estimated_delivery || '';
        document.getElementById('arrival-date').value = arrivalDate;

        const freeDaysAllowed = parseInt(shipment.free_days_allowed || 0, 10);
        document.getElementById('free-days-allowed').value = freeDaysAllowed > 0 ? `${freeDaysAllowed} Days` : '0 Days';

        const expiryDate = this.calculateExpiryDate(arrivalDate, freeDaysAllowed);
        document.getElementById('expiry-date').value = expiryDate;

        const daysLeft = this.calculateDaysLeft(expiryDate);
            document.getElementById('days-left').value = daysLeft;
            document.getElementById('detention-delay-days').value = this.calculateDelayDays(expiryDate);
    
            this.updateDetentionAmount();
        }
    },

    calculateExpiryDate(arrivalDate, freeDaysAllowed) {
        if (!arrivalDate || !freeDaysAllowed) return '';

        const baseDate = new Date(`${arrivalDate}T00:00:00`);
        baseDate.setDate(baseDate.getDate() + freeDaysAllowed);
        return baseDate.toISOString().split('T')[0];
    },

    calculateDaysLeft(expiryDate) {
        if (!expiryDate) return '0 Days';

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const expiry = new Date(`${expiryDate}T00:00:00`);
        const diff = Math.ceil((expiry - today) / 86400000);

        if (diff > 0) return `${diff} Days`;
        if (diff === 0) return 'Last Day';
        return 'Expired';
    },

    calculateDelayDays(expiryDate) {
        if (!expiryDate) return '0';

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const expiry = new Date(`${expiryDate}T00:00:00`);
        const diff = Math.ceil((today - expiry) / 86400000);
        return diff > 0 ? String(diff) : '0';
    },

    updateDetentionAmount() {
        const rateInput = document.getElementById('detention-rate');
        const amountInput = document.getElementById('detention-amount');
        const delayInput = document.getElementById('detention-delay-days');

        if (!rateInput || !amountInput || !delayInput) return;

        const rate = parseFloat(rateInput.value || 0);
        const delayDays = parseInt(delayInput.value || 0, 10);
        const amount = rate * delayDays;
        amountInput.value = Number.isFinite(amount) && amount > 0 ? `AED ${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : 'AED 0.00';
    },

    async createDetentionCharge() {
        const shipment = this.getSelectedShipment();
        if (!shipment) {
            this.showMessage('danger', 'Select a shipment first.');
            return;
        }

        const chargeType = document.getElementById('detention-type').value;
        const delayDays = document.getElementById('detention-delay-days').value;
        const rate = document.getElementById('detention-rate').value;
        const remarks = document.getElementById('detention-remarks').value.trim();
        const expiryDate = document.getElementById('expiry-date').value;

        if (!rate || parseFloat(rate) <= 0) {
            this.showMessage('danger', 'Enter a valid rate before creating the charge.');
            return;
        }

        try {
            const response = await App.api.post('shipments-charges', {
                action: 'create-charge',
                shipment_id: shipment.id,
                tracking_number: shipment.tracking_number,
                charge_type: this.mapChargeType(chargeType),
                charge_category: 'Detention / Demurrage',
                description: `${chargeType} charge`,
                amount: parseFloat(rate) * parseInt(delayDays || 0, 10),
                status: 'unpaid',
                free_days_allowed: shipment.free_days_allowed || 0,
                expiry_date: expiryDate,
                delay_days: parseInt(delayDays || 0, 10),
                remarks,
            });

            if (!response.success) {
                throw new Error(response.message || 'Unable to create charge.');
            }

            this.showMessage('success', response.message || 'Charge created successfully.');
            this.resetDetentionForm();
            
            // Close the modal
            if (document.activeElement) document.activeElement.blur();
            const modalEl = document.getElementById('detentionModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
            
            this.loadData();
        } catch (error) {
            this.showMessage('danger', error.message || 'Unable to create charge.');
        }
    },

    async createLocalCharge() {
        const shipment = this.getSelectedShipment();
        if (!shipment) {
            this.showMessage('danger', 'Select a shipment first.');
            return;
        }

        const category = document.getElementById('local-category').value;
        const description = document.getElementById('local-description').value.trim();
        const amount = document.getElementById('local-amount').value;
        const status = document.getElementById('local-status').value;

        if (!description || !amount || parseFloat(amount) <= 0) {
            this.showMessage('danger', 'Enter a description and a valid amount.');
            return;
        }

        try {
            const response = await App.api.post('shipments-charges', {
                action: 'create-charge',
                shipment_id: shipment.id,
                tracking_number: shipment.tracking_number,
                charge_type: 'local_charge',
                charge_category: category,
                description,
                amount: parseFloat(amount),
                status,
                free_days_allowed: shipment.free_days_allowed || 0,
                expiry_date: document.getElementById('expiry-date').value || null,
                delay_days: 0,
                remarks: '',
            });

            if (!response.success) {
                throw new Error(response.message || 'Unable to create local charge.');
            }

            this.showMessage('success', response.message || 'Local charge created successfully.');
            this.resetLocalForm();
            
            // Close the modal
            if (document.activeElement) document.activeElement.blur();
            const modalEl = document.getElementById('localChargeModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
            
            this.loadData();
        } catch (error) {
            this.showMessage('danger', error.message || 'Unable to create local charge.');
        }
    },

    resetDetentionForm() {
        document.getElementById('detention-type').value = 'Detention';
        document.getElementById('detention-rate').value = '200';
        document.getElementById('detention-remarks').value = '';
        this.updateDetentionAmount();
    },

    resetLocalForm() {
        document.getElementById('local-category').value = 'Port Charges';
        document.getElementById('local-description').value = '';
        document.getElementById('local-amount').value = '';
        document.getElementById('local-status').value = 'Unpaid';
    },

    clearSummary() {
        const consigneeInput = document.getElementById('consignee-name');
        if (consigneeInput) consigneeInput.value = '';
        
        const arrivalInput = document.getElementById('arrival-date');
        if (arrivalInput) arrivalInput.value = '';
        
        const freeDaysInput = document.getElementById('free-days-allowed');
        if (freeDaysInput) freeDaysInput.value = '';
        
        const expiryInput = document.getElementById('expiry-date');
        if (expiryInput) expiryInput.value = '';
        
        const daysLeftInput = document.getElementById('days-left');
        if (daysLeftInput) daysLeftInput.value = '';
        
        const detentionDelayInput = document.getElementById('detention-delay-days');
        if (detentionDelayInput) detentionDelayInput.value = '0';
        
        const detentionAmountInput = document.getElementById('detention-amount');
        if (detentionAmountInput) detentionAmountInput.value = 'AED 0.00';
    },

    renderTables() {
        const detentionBody = document.getElementById('detention-body');
        const localBody = document.getElementById('local-charge-body');
        if (!detentionBody || !localBody) return;

        // Show all charges instead of filtering by selected tracking
        const filtered = this.state.charges;

        const detentionRows = filtered.filter((item) => item.charge_type === 'detention' || item.charge_type === 'demurrage');
        const localRows = filtered.filter((item) => item.charge_type === 'local_charge');

        detentionBody.innerHTML = detentionRows.length
            ? detentionRows.map((item) => `
                <tr>
                    <td><strong>${item.tracking_number}</strong></td>
                    <td>${item.description || item.charge_type}</td>
                    <td>${item.expiry_date || '-'}</td>
                    <td>${item.delay_days || 0}</td>
                    <td>AED ${parseFloat(item.amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td><span class="badge bg-${(item.status || 'unpaid') === 'paid' ? 'success' : 'warning'} text-dark">${(item.status || 'unpaid').toUpperCase()}</span></td>
                    <td>${item.remarks || '-'}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="6" class="text-muted text-center py-3">No detention or demurrage records found.</td></tr>';

        localBody.innerHTML = localRows.length
            ? localRows.map((item) => `
                <tr>
                    <td><strong>${item.tracking_number}</strong></td>
                    <td>${item.charge_category || 'Local Charge'}</td>
                    <td>${item.description || '-'}</td>
                    <td>AED ${parseFloat(item.amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td><span class="badge bg-${(item.status || 'unpaid') === 'paid' ? 'success' : 'warning'} text-dark">${(item.status || 'unpaid').toUpperCase()}</span></td>
                </tr>
            `).join('')
            : '<tr><td colspan="4" class="text-muted text-center py-3">No local charges found.</td></tr>';
    },

    getSelectedShipment() {
        if (!this.state.selectedTracking) return null;
        return this.state.shipments.find((item) => item.tracking_number === this.state.selectedTracking) || null;
    },

    mapChargeType(type) {
        if (type === 'Demurrage') return 'demurrage';
        if (type === 'Detention + Demurrage') return 'detention';
        return 'detention';
    },

    showMessage(type, message) {
        const alertArea = document.getElementById('charges-alert');
        if (!alertArea) return;
        alertArea.innerHTML = `<div class="alert alert-${type} py-2 mb-0">${message}</div>`;
        setTimeout(() => {
            if (alertArea.innerHTML.includes(message)) {
                alertArea.innerHTML = '';
            }
        }, 4000);
    },
};

window.ShipmentsChargesPage = ShipmentsChargesPage;
