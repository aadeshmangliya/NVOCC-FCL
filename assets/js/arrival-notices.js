const ArrivalNoticePage = {
    modalInstance: null, // Cache for modal instance

    init: function () {
        this.bindEvents();
        this.loadShipmentOptions();
        this.loadNotices();
    },

    bindEvents: function () {
        const form = document.getElementById('arrival-notice-form');
        if (form) {
            form.addEventListener('submit', (e) => e.preventDefault());
        }

        this.ensureModalInstance();

        const shipmentSelect = document.getElementById('arrival-shipment');
        if (shipmentSelect) {
            shipmentSelect.addEventListener('change', () => this.populateShipmentFields());
        }

        const saveDraftBtn = document.getElementById('save-arrival-draft');
        if (saveDraftBtn) {
            saveDraftBtn.addEventListener('click', () => this.submitForm('draft'));
        }

        const sendBtn = document.getElementById('send-arrival-mail');
        if (sendBtn) {
            sendBtn.addEventListener('click', () => this.submitForm('send'));
        }

        const addNoticeBtn = document.getElementById('add-arrival-notice-btn');
        if (addNoticeBtn) {
            addNoticeBtn.addEventListener('click', () => this.openFormModal());
        }

        const modal = document.getElementById('arrivalNoticeModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', () => {
                this.ensureBackdrop();
            });
            modal.addEventListener('shown.bs.modal', () => {
                this.ensureBackdrop();
            });
            modal.addEventListener('hidden.bs.modal', () => {
                this.resetForm();
                setTimeout(() => this.cleanupModalBackdrop(), 250);
            });
        }
    },

    ensureModalInstance: function () {
        const modalEl = document.getElementById('arrivalNoticeModal');
        if (!modalEl) return;

        if (!this.modalInstance) {
            this.modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        }
    },

    ensureBackdrop: function () {
        const existingBackdrop = document.querySelector('.modal-backdrop');
        if (!existingBackdrop) {
            const backdrop = document.createElement('div');
            // backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }

        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
        document.body.style.paddingRight = '';
    },

    cleanupModalBackdrop: function () {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    },

    loadShipmentOptions: function () {
        fetch('../api/arrival_notices.php?action=shipments')
            .then(r => r.json())
            .then(res => {
                const select = document.getElementById('arrival-shipment');
                if (!select) return;
                select.innerHTML = '<option value="">-- Select Shipment --</option>';
                if (res.success && Array.isArray(res.data)) {
                    res.data.forEach(item => {
                        const label = `${item.tracking_number || 'N/A'} - ${item.consignee_name || 'N/A'}`;
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.dataset.trackingNumber = item.tracking_number || '';
                        option.dataset.consigneeName = item.consignee_name || '';
                        option.dataset.consigneeEmail = item.consignee_email || '';
                        option.dataset.arrivalDate = item.actual_delivery || item.eta || item.estimated_delivery || '';
                        option.textContent = label;
                        select.appendChild(option);
                    });
                }
            })
            .catch(() => {
                const select = document.getElementById('arrival-shipment');
                if (select) {
                    select.innerHTML = '<option value="">Failed to load shipments</option>';
                }
            });
    },

    populateShipmentFields: function () {
        const select = document.getElementById('arrival-shipment');
        const consigneeEmail = document.getElementById('arrival-consignee-email');
        const consigneeName = document.getElementById('arrival-consignee-name');
        const trackingInput = document.getElementById('arrival-tracking-number');
        const arrivalDateInput = document.getElementById('arrival-date');
        if (!select || !consigneeEmail || !consigneeName || !trackingInput || !arrivalDateInput) return;

        const selected = select.options[select.selectedIndex];
        if (!selected || !selected.value) {
            consigneeEmail.value = '';
            consigneeName.value = '';
            trackingInput.value = '';
            return;
        }

        consigneeEmail.value = selected.dataset.consigneeEmail || '';
        consigneeName.value = selected.dataset.consigneeName || '';
        trackingInput.value = selected.dataset.trackingNumber || '';
        if (selected.dataset.arrivalDate) {
            arrivalDateInput.value = selected.dataset.arrivalDate;
        }
    },

    submitForm: function (action) {
        const form = document.getElementById('arrival-notice-form');
        if (!form) return;

        const payload = {
            action,
            id: document.getElementById('arrival-notice-id').value || '',
            shipment_id: document.getElementById('arrival-shipment').value || '',
            tracking_number: document.getElementById('arrival-tracking-number').value || '',
            consignee_name: document.getElementById('arrival-consignee-name').value || '',
            consignee_email: document.getElementById('arrival-consignee-email').value || '',
            arrival_date: document.getElementById('arrival-date').value || '',
            free_days_allowed: document.getElementById('arrival-free-days').value || 0,
            notice_message: document.getElementById('arrival-notice-message').value || ''
        };

        if (!payload.shipment_id || !payload.consignee_email || !payload.arrival_date) {
            App.notify.error('Please select a shipment, enter the consignee email, and choose an arrival date.');
            return;
        }

        // Get the button that was clicked
        const buttons = document.querySelectorAll('#save-arrival-draft, #send-arrival-mail');
        let activeButton = null;
        buttons.forEach(btn => {
            if (btn.id === 'send-arrival-mail' && action === 'send') {
                activeButton = btn;
            } else if (btn.id === 'save-arrival-draft' && action === 'draft') {
                activeButton = btn;
            }
        });

        // Show loader on active button
        const originalHTML = activeButton ? activeButton.innerHTML : '';
        if (activeButton) {
            activeButton.disabled = true;
            activeButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
        }

        fetch('../api/arrival_notices.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(r => r.json())
            .then(res => {
                if (res.success || res.saved === true) {
                    const message = res.message || (action === 'send' ? 'Arrival notice sent.' : 'Arrival notice saved as draft.');
                    App.notify.success(message);
                    this.loadNotices();
                    this.resetForm();
                    this.closeModal();
                } else {
                    // Show error message with proper handling
                    const errorMsg = res.message || 'Could not complete the action.';
                    App.notify.error(errorMsg);
                    // Restore button on error
                    if (activeButton) {
                        activeButton.innerHTML = originalHTML;
                        activeButton.disabled = false;
                    }
                }
            })
            .catch(() => {
                App.notify.error('The request failed.');
                // Restore button on error
                if (activeButton) {
                    activeButton.innerHTML = originalHTML;
                    activeButton.disabled = false;
                }
            });
    },

    loadNotices: function () {
        fetch('../api/arrival_notices.php')
            .then(r => r.json())
            .then(res => {
                const tbody = document.getElementById('arrival-notices-table-body');
                if (!tbody) return;
                tbody.innerHTML = '';
                if (res.success && Array.isArray(res.data)) {
                    if (res.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="9" class="text-muted">No arrival notices yet.</td></tr>';
                        return;
                    }
                    res.data.forEach(item => {
                        const statusBadge = item.status === 'sent' ? 'bg-success' : 'bg-warning';
                        const statusText = item.status === 'sent' ? 'Sent' : 'Draft';
                        const sendCount = item.send_count || 0;
                        // Show resend button for all notices, with different styling for draft vs sent
                        const actionHtml = item.status === 'draft'
                            ? `<button class="btn btn-sm" style="background-color: #FF883A; color: white; border-color: #FF883A;" data-action="send" data-id="${item.id}"><i class="fas fa-paper-plane"></i> Send</button>`
                            : `<button class="btn btn-sm btn-outline-primary" data-action="resend" data-id="${item.id}"><i class="fas fa-redo"></i> Resend</button>`;
                        tbody.insertAdjacentHTML('beforeend', `
                            <tr>
                                <td>${item.tracking_number || ''}</td>
                                <td>${item.consignee_name || ''}</td>
                                <td>${item.consignee_email || ''}</td>
                                <td>${item.arrival_date || ''}</td>
                                <td>${item.free_days_allowed || 0}</td>
                                <td><span class="badge ${statusBadge}" style="color: white;">${statusText}</span></td>
                                <td><span class="badge bg-info" style="color: white;">${sendCount} time${sendCount !== 1 ? 's' : ''}</span></td>
                                <td>${actionHtml}</td>
                                <td>${item.sent_at || ''}</td>
                            </tr>
                        `);
                    });

                    tbody.querySelectorAll('[data-action="send"], [data-action="resend"]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.preventDefault();
                            this.resendNotice(btn.dataset.id, btn);
                        });
                    });

                    tbody.querySelectorAll('[data-action="view"]').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const row = btn.closest('tr');
                            const payload = row ? row.children : [];
                            const message = payload[2] ? `Sent to: ${payload[2].textContent}` : 'Notice has already been sent.';
                            App.notify.info(message);
                        });
                    });
                }
            })
            .catch(() => {
                const tbody = document.getElementById('arrival-notices-table-body');
                if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="text-danger">Failed to load notices.</td></tr>';
            });
    },

    openFormModal: function () {
        // Reload shipments to get the latest list (excluding already notified shipments)
        this.loadShipmentOptions();

        const modalEl = document.getElementById('arrivalNoticeModal');
        if (!modalEl) return;

        this.ensureModalInstance();
        this.modalInstance.show();
    },

    resendNotice: function (noticeId, button) {
        if (!button) return;
        
        // Store original button HTML
        const originalHTML = button.innerHTML;
        
        // Show loader
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Sending...';
        
        fetch('../api/arrival_notices.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: noticeId, action: 'send' })
        })
            .then(r => r.json())
            .then(res => {
                if (res.success || res.saved === true) {
                    App.notify.success(res.message || 'Arrival notice email sent.');
                    this.loadNotices();
                } else {
                    App.notify.error(res.message || 'Could not send the email.');
                    // Restore button on error
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                }
            })
            .catch(() => {
                App.notify.error('The email request failed.');
                // Restore button on error
                button.innerHTML = originalHTML;
                button.disabled = false;
            });
    },

    closeModal: function () {
        const modalEl = document.getElementById('arrivalNoticeModal');
        if (!modalEl) return;

        // Restore all buttons to original state
        const saveDraftBtn = document.getElementById('save-arrival-draft');
        const sendBtn = document.getElementById('send-arrival-mail');

        if (saveDraftBtn) {
            saveDraftBtn.disabled = false;
            saveDraftBtn.innerHTML = '<i class="fas fa-save"></i> Save as Draft';
        }

        if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Arrival Mail';
        }

        if (this.modalInstance) {
            this.modalInstance.hide();
        }

        setTimeout(() => {
            this.cleanupModalBackdrop();
        }, 300);
    },

    resetForm: function () {
        const form = document.getElementById('arrival-notice-form');
        if (!form) return;
        form.reset();
        document.getElementById('arrival-notice-id').value = '';
        document.getElementById('arrival-tracking-number').value = '';
        document.getElementById('arrival-consignee-name').value = '';
        document.getElementById('arrival-consignee-email').value = '';
    }
};

window.ArrivalNoticePage = ArrivalNoticePage;
