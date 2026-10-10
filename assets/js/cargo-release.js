window.CargoReleasePage = {
    eventsBound: false,

    init: function () {
        if (!this.eventsBound) {
            this.bindEvents();
            this.eventsBound = true;
        }

        this.loadShipments();
    },

    bindEvents: function () {
        document.getElementById('cargoReleaseRefreshBtn')?.addEventListener('click', () => this.loadShipments());
    },

    getRequestHeaders: function () {
        const headers = { 'Content-Type': 'application/json' };
        const bearerToken = document.querySelector('meta[name="api-bearer-token"]')?.getAttribute('content');
        if (bearerToken) {
            headers.Authorization = 'Bearer ' + bearerToken;
        }
        return headers;
    },

    showAlert: function (message, type = 'info') {
        const alertBox = document.getElementById('cargoReleaseAlert');
        if (!alertBox) return;
        alertBox.innerHTML = `<div class="alert alert-${type} py-2 mb-0">${message}</div>`;
    },

    clearAlert: function () {
        const alertBox = document.getElementById('cargoReleaseAlert');
        if (alertBox) alertBox.innerHTML = '';
    },

    loadShipments: function () {
        const container = document.getElementById('cargoReleaseContent');
        if (!container) return;

        this.clearAlert();
        container.innerHTML = '<div class="card"><div class="card-body text-center py-5 text-muted">Loading cargo release list...</div></div>';

        const params = new URLSearchParams();

        fetch(`../api/cargo_release.php?action=list&${params.toString()}`, { headers: this.getRequestHeaders() })
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    throw new Error(res.message || 'Unable to load cargo release list.');
                }

                this.renderShipments(Array.isArray(res.data) ? res.data : []);
            })
            .catch(err => {
                container.innerHTML = '<div class="card"><div class="card-body text-danger">Unable to load cargo release data. ' + (err.message || '') + '</div></div>';
            });
    },

    renderShipments: function (shipments) {
        const container = document.getElementById('cargoReleaseContent');
        if (!container) return;

        if (!shipments.length) {
            container.innerHTML = '<div class="card"><div class="card-body text-center text-muted py-5">No shipments found.</div></div>';
            return;
        }

        const rows = shipments.map(shipment => {
            const paymentSummary = shipment.payment_summary || {};
            const paymentStatusLabel = this.getPaymentStatusLabel(paymentSummary.payment_status || 'n/a');
            const releaseStatus = shipment.cargo_release_status || 'pending';
            const releaseBadge = this.getReleaseBadge(releaseStatus);
            const reasonText = shipment.cargo_release_reason || '';
            const releaseDate = shipment.cargo_release_date || '';
            const isReleased = releaseStatus === 'released';
            const canUnlock = releaseStatus === 'locked' || releaseStatus === 'ready' || releaseStatus === 'pending';
            const disabled = !canUnlock || paymentSummary.payment_status !== 'paid' && paymentSummary.payment_status !== 'n/a';
            const paymentLabel = paymentSummary.payment_status === 'paid' ? 'Paid' : (paymentSummary.payment_status === 'partially_paid' ? 'Partially Paid' : 'Unpaid');

            return `
                <tr>
                    <td><strong>${shipment.tracking_number || ''}</strong></td>
                    <td>${shipment.consignee_name || ''}</td>
                    <td>AED ${parseFloat(paymentSummary.total_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td>${this.getPaymentBadge(paymentSummary.payment_status || 'n/a')}</td>
                    <td>${releaseBadge}</td>
                    <td>${reasonText || releaseDate ? `<div class="small text-muted">${reasonText}${reasonText && releaseDate ? ' • ' : ''}${releaseDate}</div>` : ''}</td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-container="body" data-bs-offset="0,4">
                                Actions
                            </button>
                            <ul class="dropdown-menu">
                                <li><button class="dropdown-item" data-action="status" data-id="${shipment.id}" data-status="pending">Set Pending</button></li>
                                <li><button class="dropdown-item" data-action="status" data-id="${shipment.id}" data-status="ready">Set Ready</button></li>
                                <li><button class="dropdown-item" data-action="status" data-id="${shipment.id}" data-status="locked">Lock Release</button></li>
                                <li><button class="dropdown-item" data-action="status" data-id="${shipment.id}" data-status="released">Release Cargo</button></li>
                            </ul>
                        </div>
                    </td>
                    <td>
                        <div class="small text-muted">
                            ${paymentSummary.payment_status === 'paid' ? 'Payment cleared' : `Remaining: AED ${parseFloat(paymentSummary.remaining_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}
                        </div>
                    </td>
                </tr>`;
        }).join('');

        container.innerHTML = `
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Tracking</th>
                                    <th>Consignee</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Release Status</th>
                                    <th>Reason / Date</th>
                                    <th>Actions</th>
                                    <th>Payment Summary</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                </div>
            </div>`;

        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            container.querySelectorAll('.dropdown-toggle').forEach(button => {
                try {
                    const instance = bootstrap.Dropdown.getInstance(button);
                    if (instance) {
                        instance.dispose();
                    }
                    new bootstrap.Dropdown(button, {
                        boundary: 'viewport',
                        offset: [0, 4],
                        popperConfig: {
                            placement: 'bottom-start',
                            strategy: 'fixed',
                            modifiers: [
                                { name: 'flip', options: { fallbackPlacements: [] } },
                                { name: 'preventOverflow', options: { boundary: 'viewport' } }
                            ]
                        }
                    });
                } catch (err) {
                    console.warn('CargoRelease dropdown init failed:', err);
                }
            });
        }

        container.querySelectorAll('[data-action="status"]').forEach(button => {
            button.addEventListener('click', () => this.openStatusModal(button.getAttribute('data-id'), button.getAttribute('data-status')));
        });
    },

    openStatusModal: function (shipmentId, status) {
        const modalHtml = `
            <div class="modal fade show" tabindex="-1" style="display:block;background:rgba(0,0,0,.5);">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${status === 'locked' ? 'Lock Cargo Release' : status === 'released' ? 'Release Cargo' : 'Update Cargo Release Status'}</h5>
                            <button type="button" class="btn-close" data-action="close-modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="cargoReleaseForm">
                                <input type="hidden" name="id" value="${shipmentId}">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="cargo_release_status" value="${status}">
                                <div class="mb-3">
                                    <label class="form-label">Reason</label>
                                    <textarea class="form-control" name="cargo_release_reason" rows="3" placeholder="${status === 'locked' ? 'Why are you locking the release?' : 'Enter a short reason'}"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Release Date</label>
                                    <input class="form-control" type="date" name="release_date" value="${new Date().toISOString().slice(0,10)}">
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-action="close-modal">Cancel</button>
                            <button class="btn btn-success" id="saveCargoReleaseBtn">Save</button>
                        </div>
                    </div>
                </div>
            </div>`;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        document.querySelector('[data-action="close-modal"]')?.addEventListener('click', () => this.closeModal());
        document.getElementById('saveCargoReleaseBtn')?.addEventListener('click', (event) => {
            event.preventDefault();
            this.saveStatus();
        });
    },

    saveStatus: function () {
        const form = document.getElementById('cargoReleaseForm');
        if (!form) return;

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());
        payload.action = 'update';

        this.showAlert('Updating cargo release status...', 'info');
        fetch('../api/cargo_release.php', {
            method: 'POST',
            headers: this.getRequestHeaders(),
            body: JSON.stringify(payload),
        })
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    throw new Error(res.message || 'Unable to update status.');
                }
                this.closeModal();
                this.showAlert(res.message || 'Status updated successfully.', 'success');
                this.loadShipments();
            })
            .catch(err => this.showAlert(err.message || 'Unable to update status.', 'danger'));
    },

    closeModal: function () {
        document.querySelector('.modal.show')?.remove();
    },

    getPaymentBadge: function (paymentStatus) {
        const map = {
            paid: '<span class="badge bg-success">Paid</span>',
            partially_paid: '<span class="badge bg-info">Partially Paid</span>',
            unpaid: '<span class="badge bg-warning text-dark">Unpaid</span>',
            'n/a': '<span class="badge bg-secondary">N/A</span>',
        };
        return map[paymentStatus] || '<span class="badge bg-secondary">Unknown</span>';
    },

    getReleaseBadge: function (status) {
        const map = {
            pending: '<span class="badge bg-secondary">Pending</span>',
            ready: '<span class="badge bg-success">Ready</span>',
            locked: '<span class="badge bg-warning text-dark">Locked</span>',
            released: '<span class="badge bg-primary">Released</span>',
        };
        return map[status] || '<span class="badge bg-secondary">Pending</span>';
    },

    getPaymentStatusLabel: function (paymentStatus) {
        return paymentStatus === 'paid' ? 'Paid' : (paymentStatus === 'partially_paid' ? 'Partially Paid' : 'Unpaid');
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.CargoReleasePage.init());
} else {
    window.CargoReleasePage.init();
}
