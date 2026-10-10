const InvoiceCreate = {
    currentShipment: null,
    shipmentCharges: [],
    adhocCharges: [],
    initialized: false,

    init: function () {
        if (this.initialized) {
            // Re-bind events and re-populate dynamic fields because DOM was replaced
            this.bindEvents();
            this.loadShipments();
            this.setTodayDate();

            const params = window._pageParams || {};
            if (params.id) {
                this.loadInvoiceForEdit(params.id);
            } else {
                this.editInvoiceId = null;
                const titleEl = document.getElementById('pageTitle');
                if (titleEl) titleEl.textContent = 'Create New Invoice';
            }
            return;
        }

        this.initialized = true;

        this.bindEvents();
        this.loadShipments();
        this.setTodayDate();

        // Check for edit mode (page params set by App.loadPageWithParams)
        const params = window._pageParams || {};
        if (params.id) {
            // load invoice details for editing
            this.loadInvoiceForEdit(params.id);
        } else {
            this.editInvoiceId = null;
        }
    },

    getApiUrl: function (endpoint, params) {
        const baseUrl = (typeof window !== 'undefined' && window.App && typeof window.App.getApiBaseUrl === 'function')
            ? window.App.getApiBaseUrl()
            : '../api';

        const url = new URL(`${baseUrl}/${endpoint}`.replace(/\/+/g, '/'), window.location.href);
        Object.entries(params || {}).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                url.searchParams.set(key, value);
            }
        });
        return url.toString();
    },

    bindEvents: function () {
        // Replace elements with clones to remove old event listeners (safe rebind on AJAX reload)
        const replaceAndBind = (id, type, handler) => {
            const el = document.getElementById(id);
            if (!el) return;
            const newEl = el.cloneNode(true);
            el.parentNode.replaceChild(newEl, el);
            newEl.addEventListener(type, handler);
        };

        replaceAndBind('globalShipmentSelect', 'change', () => this.onShipmentChange());
        replaceAndBind('addAdhocBtn', 'click', () => this.addAdhocCharge());
        replaceAndBind('applyAdjustmentsBtn', 'click', () => this.applyAdjustments());
        replaceAndBind('saveInvoiceBtn', 'click', () => this.saveInvoice());
        replaceAndBind('taxRateInput', 'input', () => this.recalculateTotals());
        replaceAndBind('globalDiscountInput', 'input', () => this.recalculateTotals());
    },

    setTodayDate: function () {
        const today = new Date().toLocaleDateString('en-CA');
        document.getElementById('invoiceDate').textContent = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    },

    loadShipments: function () {
        fetch(this.getApiUrl('invoices.php', { action: 'shipments', invoice_id: window._pageParams?.id || '' }))
            .then(r => r.ok ? r.json() : r.text().then(text => { throw new Error(text || 'Failed to load shipments'); }))
            .then(res => {
                const select = document.getElementById('globalShipmentSelect');
                if (!select) return;

                const shipments = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
                if (!shipments.length) {
                    select.innerHTML = '<option value="">-- Select Shipment --</option>';
                    return;
                }

                // clear existing options
                select.innerHTML = '<option value="">-- Select Shipment --</option>';

                shipments.forEach(ship => {
                    const option = document.createElement('option');
                    const tracking = ship.tracking_number ?? ship.trackingNo ?? ship.tracking ?? '';
                    const consignee = ship.consignee_name ?? ship.consignee ?? '';
                    const consigneeEmail = ship.consignee_email ?? ship.email ?? '';
                    const arrival = ship.arrival_date ?? ship.arrivalDate ?? '';
                    const contact = ship.consignee_contact ?? ship.contact ?? '';
                    const ntn = ship.consignee_ntn ?? ship.ntn ?? '';
                    const address = ship.consignee_address ?? ship.address ?? '';
                    const freeDays = ship.free_days_allowed ?? ship.freeDays ?? '';
                    const freeExpiry = ship.free_days_expiry ?? ship.freeDaysExpiry ?? '';
                    const container = ship.container_number ?? ship.containerNo ?? ship.container ?? '';
                    const vessel = ship.vessel_name ?? ship.vessel ?? '';
                    const voyage = ship.voyage_no ?? ship.voyage ?? '';
                    const destinationAgent = ship.destination_agent ?? ship.destinationAgent ?? '';
                    const destinationAddress = ship.destination_agent_address ?? ship.destinationAddress ?? '';
                    const destinationPhone = ship.destination_agent_phone ?? ship.destinationPhone ?? '';
                    const destinationEmail = ship.destination_agent_email ?? ship.destinationEmail ?? '';

                    option.value = ship.id;
                    option.dataset.tracking = tracking;
                    option.dataset.consignee = consignee;
                    option.dataset.consigneeEmail = consigneeEmail;
                    option.dataset.arrival = arrival;
                    option.dataset.contact = contact;
                    option.dataset.ntn = ntn;
                    option.dataset.address = address;
                    option.dataset.freeDays = freeDays;
                    option.dataset.freeExpiry = freeExpiry;
                    option.dataset.container = container;
                    option.dataset.vessel = vessel;
                    option.dataset.voyage = voyage;
                    option.dataset.destinationAgent = destinationAgent;
                    option.dataset.destinationAddress = destinationAddress;
                    option.dataset.destinationPhone = destinationPhone;
                    option.dataset.destinationEmail = destinationEmail;
                    option.textContent = `${tracking || ship.id} - ${consignee || 'Unknown consignee'}`;
                    select.appendChild(option);
                });

                const currentInvoiceId = window._pageParams?.id;
                if (currentInvoiceId) {
                    const selectedOption = Array.from(select.options).find(option => String(option.value) === String(this.currentShipment?.id || ''));
                    if (selectedOption) {
                        select.value = selectedOption.value;
                    }
                }
            })
            .catch(err => {
                console.error('Error loading shipments:', err);
                alert('Failed to load shipments');
            });
    },

    onShipmentChange: function () {
        const select = document.getElementById('globalShipmentSelect');
        const option = select.options[select.selectedIndex];

        if (!option.value) {
            this.clearInvoiceDisplay();
            return;
        }

        this.currentShipment = {
            id: option.value,
            tracking: option.dataset.tracking,
            consignee: option.dataset.consignee,
            consignee_email: option.dataset.consigneeEmail,
            arrival: option.dataset.arrival,
            contact: option.dataset.contact,
            ntn: option.dataset.ntn,
            address: option.dataset.address,
            freeDays: option.dataset.freeDays,
            freeExpiry: option.dataset.freeExpiry,
            containerNumber: option.dataset.container,
            vesselName: option.dataset.vessel,
            voyageNo: option.dataset.voyage,
            destinationAgent: option.dataset.destinationAgent,
            destinationAgentAddress: option.dataset.destinationAddress,
            destinationAgentPhone: option.dataset.destinationPhone,
            destinationAgentEmail: option.dataset.destinationEmail
        };

        this.adhocCharges = [];
        document.getElementById('displayConsignee').value = this.currentShipment.consignee;
        document.getElementById('displayArrival').value = this.currentShipment.arrival;

        this.updateInvoiceDisplay();
        this.loadShipmentCharges();
    },

    loadShipmentCharges: function () {
        if (!this.currentShipment) return;

        fetch(this.getApiUrl('invoices.php', { action: 'charges', shipment_id: this.currentShipment.id }))
            .then(r => r.ok ? r.json() : r.text().then(text => { throw new Error(text || 'Failed to load charges'); }))
            .then(res => {
                const tbody = document.getElementById('chargeTableBody');
                tbody.innerHTML = '';

                if (!res.success || !Array.isArray(res.data)) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #999;">No charges found for this shipment</td></tr>';
                    return;
                }

                this.shipmentCharges = res.data;

                // Add free days info
                if (this.currentShipment.freeDays) {
                    const freeRow = document.createElement('tr');
                    freeRow.className = 'section-row';
                    freeRow.innerHTML = `<td colspan="6">Free Days Allowed: ${this.currentShipment.freeDays} Days (expiry ${this.currentShipment.freeExpiry})</td>`;
                    tbody.appendChild(freeRow);
                }

                // Group charges by type
                const groups = {
                    'detention': 'Detention & Demurrage',
                    'demurrage': 'Detention & Demurrage',
                    'local': 'Local Charges (Destination)',
                    'other': 'Other Charges'
                };

                let lastGroup = null;
                res.data.forEach(charge => {
                    const group = groups[charge.charge_type] || 'Other';

                    // Add group header
                    if (group !== lastGroup) {
                        const groupRow = document.createElement('tr');
                        groupRow.className = 'section-row';
                        groupRow.innerHTML = `<td colspan="6">${group}</td>`;
                        tbody.appendChild(groupRow);
                        lastGroup = group;
                    }

                    // Add charge row
                    const row = document.createElement('tr');
                    row.dataset.type = charge.charge_type;
                    row.dataset.amount = charge.amount;
                    row.innerHTML = `
                        <td>${charge.description}</td>
                        <td>${charge.category || '-'}</td>
                        <td>1</td>
                        <td>${parseFloat(charge.amount).toLocaleString(undefined, { minimumFractionDigits: 0 })}</td>
                        <td class="line-amount">AED ${parseFloat(charge.amount).toLocaleString(undefined, { minimumFractionDigits: 0 })}</td>
                        <td class="delete-cell"></td>
                    `;
                    tbody.appendChild(row);
                });

                this.recalculateTotals();

                // If we have pending edit line items (editing invoice linked to this shipment), replace with those
                if (this.pendingEditLineItems) {
                    this.populateLineItemsForEdit(this.pendingEditLineItems);
                    this.pendingEditLineItems = null;
                }
            })
            .catch(err => {
                console.error('Error loading charges:', err);
                document.getElementById('chargeTableBody').innerHTML = '<tr><td colspan="6" style="text-align: center; color: #d32f2f;">Failed to load charges</td></tr>';
            });
    },

    setInvoiceShipmentContext: function (invoice) {
        const select = document.getElementById('globalShipmentSelect');
        const shipmentId = invoice?.shipment_id ? String(invoice.shipment_id) : '';
        const matchedOption = shipmentId && select
            ? Array.from(select.options).find(option => String(option.value) === shipmentId)
            : null;

        const shipmentContext = {
            id: shipmentId || '',
            tracking: invoice?.shipment_tracking_number || matchedOption?.dataset.tracking || '',
            consignee: invoice?.consignee_name || matchedOption?.dataset.consignee || '',
            consignee_email: invoice?.consignee_email || matchedOption?.dataset.consigneeEmail || '',
            arrival: invoice?.arrival_date || matchedOption?.dataset.arrival || '',
            contact: invoice?.consignee_contact || matchedOption?.dataset.contact || '',
            ntn: invoice?.consignee_ntn || matchedOption?.dataset.ntn || '',
            address: invoice?.consignee_address || matchedOption?.dataset.address || '',
            freeDays: invoice?.free_days_allowed ?? matchedOption?.dataset.freeDays ?? '',
            freeExpiry: invoice?.free_days_expiry || matchedOption?.dataset.freeExpiry || '',
            containerNumber: invoice?.container_number || matchedOption?.dataset.container || '',
            vesselName: invoice?.vessel_voyage || matchedOption?.dataset.vessel || '',
            voyageNo: '',
            destinationAgent: matchedOption?.dataset.destinationAgent || '',
            destinationAgentAddress: matchedOption?.dataset.destinationAddress || '',
            destinationAgentPhone: matchedOption?.dataset.destinationPhone || '',
            destinationAgentEmail: matchedOption?.dataset.destinationEmail || ''
        };

        this.currentShipment = shipmentContext;

        const displayConsignee = document.getElementById('displayConsignee');
        if (displayConsignee) displayConsignee.value = shipmentContext.consignee || '';

        const displayArrival = document.getElementById('displayArrival');
        if (displayArrival) displayArrival.value = shipmentContext.arrival || '';

        this.updateInvoiceDisplay();

        if (matchedOption && select) {
            select.value = matchedOption.value;
            return true;
        }

        return false;
    },

    loadInvoiceForEdit: function (invoiceId) {
        fetch(this.getApiUrl('invoices.php', { action: 'detail', id: invoiceId }))
            .then(r => r.ok ? r.json() : r.text().then(text => { throw new Error(text || 'Failed to load invoice'); }))
            .then(res => {
                if (!res.success || !res.data) {
                    alert('Failed to load invoice for editing.');
                    return;
                }

                const inv = res.data;
                this.editInvoiceId = inv.id;
                const titleEl = document.getElementById('pageTitle');
                if (titleEl) titleEl.textContent = 'Edit Invoice - ' + (inv.invoice_number || '') + (inv.shipment_tracking_number ? ' · Shipment ' + inv.shipment_tracking_number : '');

                document.getElementById('invoiceNumber').textContent = '# ' + (inv.invoice_number || '') + ' · Date: ' + (new Date(inv.created_at).toLocaleDateString());
                document.getElementById('invoiceNotes').value = inv.invoice_notes || '';

                // set totals/discounts/tax inputs
                document.getElementById('taxRateInput').value = inv.tax_rate ?? 0;
                document.getElementById('globalDiscountInput').value = inv.global_discount ?? 0;

                const hasShipmentOption = this.setInvoiceShipmentContext(inv);
                this.pendingEditLineItems = inv.line_items || [];

                if (hasShipmentOption) {
                    this.onShipmentChange();
                } else {
                    this.populateLineItemsForEdit(inv.line_items || []);
                }
            })
            .catch(err => {
                console.error('Error loading invoice detail:', err);
                alert('Failed to load invoice.');
            });
    },

    populateLineItemsForEdit: function (items) {
        const tbody = document.getElementById('chargeTableBody');
        tbody.innerHTML = '';
        if (!Array.isArray(items) || items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #999;">No line items found</td></tr>';
            this.recalculateTotals();
            return;
        }

        items.forEach(item => {
            const row = document.createElement('tr');
            const category = (item.category || '').toLowerCase();
            const isDiscount = category.includes('discount') || (item.item_type || '').toLowerCase() === 'discount';
            const isTax = category.includes('tax') || (item.item_type || '').toLowerCase() === 'tax';

            row.dataset.itemType = item.item_type || '';
            if (isDiscount) {
                row.dataset.type = 'discount';
                row.dataset.amount = -(Math.abs(item.amount || 0));
            } else if (isTax) {
                row.dataset.type = 'tax';
                row.dataset.amount = item.amount || 0;
            } else {
                row.dataset.type = 'charge';
                row.dataset.amount = item.amount || 0;
            }

            if (item.is_adhoc) row.className = 'adhoc-row';

            const displayAmount = (row.dataset.type === 'discount' ? '-' : '') + 'AED ' + Math.abs(item.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 0 });

            row.innerHTML = `
                <td>${item.description || ''}</td>
                <td>${item.category || ''}</td>
                <td>${item.qty_or_days || 1}</td>
                <td>${parseFloat(item.rate || 0).toLocaleString(undefined, { minimumFractionDigits: 0 })}</td>
                <td class="line-amount">${displayAmount}</td>
                <td class="delete-cell"><button class="btn btn-delete" onclick="InvoiceCreate.removeRow(this)">Del</button></td>
            `;
            tbody.appendChild(row);
        });

        this.recalculateTotals();
    },

    removeRow: function (btn) {
        const row = btn.closest('tr');
        if (row) row.remove();
        this.recalculateTotals();
    },

    updateInvoiceDisplay: function () {
        document.getElementById('partyConsignee').textContent = this.currentShipment.consignee;
        document.getElementById('partyDetails').innerHTML = `
            ${this.currentShipment.address || 'N/A'}<br>
            NTN: ${this.currentShipment.ntn || 'N/A'}<br>
            Contact: ${this.currentShipment.contact || 'N/A'}
        `;

        document.getElementById('partyDestinationAgent').textContent = this.currentShipment.destinationAgent || 'N/A';
        document.getElementById('partyDestinationDetails').innerHTML = `
            ${this.currentShipment.destinationAgentAddress || 'N/A'}<br>
            ${this.currentShipment.destinationAgentEmail || 'N/A'}<br>
            ${this.currentShipment.destinationAgentPhone || 'N/A'}
        `;

        document.getElementById('metaTracking').textContent = this.currentShipment.tracking;
        document.getElementById('metaContainer').textContent = this.currentShipment.containerNumber || '-';
        document.getElementById('metaVessel').textContent = `${this.currentShipment.vesselName || '-'} ${this.currentShipment.voyageNo ? '/ ' + this.currentShipment.voyageNo : ''}`;
        document.getElementById('metaArrival').textContent = this.formatDate(this.currentShipment.arrival);
        document.getElementById('metaFreeDaysExpiry').textContent = this.formatDate(this.currentShipment.freeExpiry);
    },

    addAdhocCharge: function () {
        const desc = document.getElementById('adhocDesc').value.trim();
        const category = document.getElementById('adhocCategory').value;
        const amount = parseFloat(document.getElementById('adhocAmount').value);

        if (!desc) {
            alert('Please enter a description.');
            return;
        }

        if (isNaN(amount) || amount <= 0) {
            alert('Please enter a valid positive amount.');
            return;
        }

        const tbody = document.getElementById('chargeTableBody');

        // Add heading if first adhoc charge
        if (this.adhocCharges.length === 0) {
            const heading = document.createElement('tr');
            heading.className = 'section-row';
            heading.id = 'adhoc-heading';
            heading.innerHTML = '<td colspan="6">Additional Charges (Ad-Hoc)</td>';
            tbody.appendChild(heading);
        }

        const row = document.createElement('tr');
        const displayType = category === 'discount' ? 'discount' : (category === 'tax' ? 'tax' : 'charge');
        row.dataset.type = displayType;
        row.dataset.itemType = category;
        row.dataset.amount = amount;
        row.className = 'adhoc-row';

        const categoryLabel = document.getElementById('adhocCategory').selectedOptions[0].textContent;
        const displayAmount = (displayType === 'discount' ? '-' : '') + 'AED ' + amount.toLocaleString(undefined, { minimumFractionDigits: 0 });

        row.innerHTML = `
            <td>${desc}</td>
            <td>${categoryLabel}</td>
            <td>1</td>
            <td>${amount.toLocaleString(undefined, { minimumFractionDigits: 0 })}</td>
            <td class="line-amount">${displayAmount}</td>
            <td class="delete-cell"><button class="btn btn-delete" onclick="InvoiceCreate.deleteAdhocRow(this)">Del</button></td>
        `;

        tbody.appendChild(row);

        this.adhocCharges.push({ desc, category, amount });

        document.getElementById('adhocDesc').value = '';
        document.getElementById('adhocAmount').value = '';
        document.getElementById('adhocCategory').value = 'charge';

        this.recalculateTotals();
    },

    deleteAdhocRow: function (btn) {
        const row = btn.closest('tr');
        const amount = parseFloat(row.dataset.amount);
        const type = row.dataset.type;

        // Find and remove from adhocCharges
        this.adhocCharges = this.adhocCharges.filter(c => !(c.amount === amount && c.category === type));

        row.remove();

        // Remove heading if no more adhoc rows
        if (this.adhocCharges.length === 0) {
            const heading = document.getElementById('adhoc-heading');
            if (heading) heading.remove();
        }

        this.recalculateTotals();
    },

    recalculateTotals: function () {
        let subtotal = 0;
        let totalAdhocDiscount = 0;
        let totalAdhocTax = 0;

        const rows = document.querySelectorAll('#chargeTableBody tr[data-amount]');
        rows.forEach(row => {
            const amt = parseFloat(row.dataset.amount) || 0;
            const type = row.dataset.type || '';

            if (type === 'discount') {
                totalAdhocDiscount += Math.abs(amt);
            } else if (type === 'tax') {
                totalAdhocTax += amt;
            } else {
                subtotal += amt;
            }
        });

        const globalDiscount = parseFloat(document.getElementById('globalDiscountInput').value) || 0;
        const taxRate = parseFloat(document.getElementById('taxRateInput').value) || 0;

        const percentageTaxAmount = (subtotal * taxRate) / 100;
        const totalTax = totalAdhocTax + percentageTaxAmount;
        const amountBeforeDiscount = subtotal + totalTax;
        const totalDiscount = totalAdhocDiscount + globalDiscount;
        const grandTotal = Math.max(0, amountBeforeDiscount - totalDiscount);

        document.getElementById('summarySubtotal').textContent = 'AED ' + subtotal.toLocaleString(undefined, { minimumFractionDigits: 0 });

        if (totalDiscount > 0) {
            document.getElementById('discountRow').style.display = 'flex';
            document.getElementById('summaryDiscount').textContent = '- AED ' + totalDiscount.toLocaleString(undefined, { minimumFractionDigits: 0 });
        } else {
            document.getElementById('discountRow').style.display = 'none';
        }

        document.getElementById('summaryTax').textContent = 'AED ' + totalTax.toLocaleString(undefined, { minimumFractionDigits: 2 });
        document.getElementById('summaryTotal').textContent = 'AED ' + grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2 });

        // Store totals for saving
        this.invoiceTotals = {
            subtotal,
            globalDiscount,
            totalAdhocDiscount,
            taxRate,
            taxAmount: percentageTaxAmount,
            totalTax,
            grandTotal
        };
    },

    applyAdjustments: function () {
        const notes = document.getElementById('invoiceNotes').value.trim();
        if (notes) {
            document.getElementById('invoiceNotesDisplay').innerHTML = `<strong>Notes:</strong> ${notes}`;
        }
        this.recalculateTotals();
        alert('Adjustments applied!');
    },

    saveInvoice: function () {
        if (!this.currentShipment) {
            alert('Please select a shipment first.');
            return;
        }

        const rows = document.querySelectorAll('#chargeTableBody tr[data-amount]');
        if (rows.length === 0) {
            alert('Please add at least one charge.');
            return;
        }

        const btn = document.getElementById('saveInvoiceBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

        const lineItems = [];
        rows.forEach(row => {
            if (row.dataset.amount) {
                lineItems.push({
                    description: row.children[0].textContent,
                    category: row.children[1].textContent,
                    qty_or_days: row.children[2].textContent,
                    rate: parseFloat(row.children[3].textContent.replace(/[^0-9.]/g, '')),
                    amount: parseFloat(row.dataset.amount),
                    item_type: row.dataset.itemType || row.dataset.type,
                    is_adhoc: row.classList.contains('adhoc-row')
                });
            }
        });

        const payload = {
            action: this.editInvoiceId ? 'update' : 'create',
            id: this.editInvoiceId || undefined,
            shipment_id: this.currentShipment.id,
            shipment_tracking_number: this.currentShipment.tracking,
            consignee_name: this.currentShipment.consignee,
            consignee_email: this.currentShipment.consignee_email || null,
            consignee_ntn: this.currentShipment.ntn,
            consignee_address: this.currentShipment.address,
            consignee_contact: this.currentShipment.contact,
            arrival_date: this.currentShipment.arrival,
            free_days_allowed: this.currentShipment.freeDays,
            free_days_expiry: this.currentShipment.freeExpiry,
            container_number: this.currentShipment.containerNumber,
            vessel_voyage: `${this.currentShipment.vesselName || ''}${this.currentShipment.voyageNo ? ' / ' + this.currentShipment.voyageNo : ''}`.trim(),
            subtotal: this.invoiceTotals.subtotal,
            global_discount: this.invoiceTotals.globalDiscount,
            tax_rate: this.invoiceTotals.taxRate,
            tax_amount: this.invoiceTotals.taxAmount,
            total_amount: this.invoiceTotals.grandTotal,
            invoice_notes: document.getElementById('invoiceNotes').value,
            line_items: lineItems
        };

        fetch(this.getApiUrl('invoices.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(response => response.text().then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error(text || response.statusText || 'Invalid JSON response');
                }
                if (!response.ok) {
                    throw new Error(data.message || `Server error ${response.status}`);
                }
                return data;
            }))
            .then(res => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Save Invoice';

                if (res.success) {
                    alert(this.editInvoiceId ? 'Invoice updated successfully!' : 'Invoice created successfully!');
                    if (typeof App !== 'undefined' && typeof App.loadPage === 'function') {
                        App.loadPage('invoice-list');
                    }
                } else {
                    alert('Error: ' + (res.message || 'Unknown error'));
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Save Invoice';
                console.error('Save error:', err);
                alert('Failed to save invoice: ' + err.message);
            });
    },

    clearInvoiceDisplay: function () {
        document.getElementById('chargeTableBody').innerHTML = '<tr><td colspan="6" style="text-align: center; color: #999;">Select a shipment to view charges</td></tr>';
        document.getElementById('partyConsignee').textContent = '-';
        document.getElementById('partyDetails').innerHTML = '-';
        document.getElementById('partyDestinationAgent').textContent = '-';
        document.getElementById('partyDestinationDetails').textContent = '-';
        document.getElementById('metaTracking').textContent = '-';
        document.getElementById('metaContainer').textContent = '-';
        document.getElementById('metaVessel').textContent = '-';
        document.getElementById('metaArrival').textContent = '-';
        document.getElementById('metaFreeDaysExpiry').textContent = '-';
        this.recalculateTotals();
    },

    formatDate: function (dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr + 'T00:00:00');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => InvoiceCreate.init());
} else {
    InvoiceCreate.init();
}
