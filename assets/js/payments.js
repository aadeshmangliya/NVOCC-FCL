window.PaymentsPage = {
    invoices: [],
    currentInvoiceId: null,
    currentInvoiceContext: null,
    autoRefreshTimer: null,
    eventsBound: false,
    pendingHistoryAction: false,

    init: function () {
        if (this.eventsBound) {
            this.loadInvoices();
            return;
        }

        this.currentInvoiceId = parseInt(document.querySelector('[data-invoice-id]')?.getAttribute('data-invoice-id') || '0', 10);
        this.pendingPaymentAction = window._pageParams?.payment_action === 'record';
        this.pendingHistoryAction = window._pageParams?.modal_action === 'history';
        this.bindEvents();
        this.loadInvoices();
        this.eventsBound = true;
    },

    bindEvents: function () {
        if (this.modalEventsBound) return;

        document.addEventListener('click', (event) => {
            const closeButton = event.target.closest('[data-action="close-modal"]');
            if (closeButton) {
                event.preventDefault();
                event.stopPropagation();
                this.closeModal();
                return;
            }

            const printButton = event.target.closest('[data-action="print-history"]');
            if (printButton) {
                event.preventDefault();
                event.stopPropagation();
                this.printHistoryModal();
            }
        });
        this.modalEventsBound = true;

        document.getElementById('paymentsRefreshBtn')?.addEventListener('click', () => this.loadInvoices());
        document.getElementById('paymentsBackBtn')?.addEventListener('click', () => this.showInvoiceList());
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
        const alertBox = document.getElementById('paymentsAlert');
        if (!alertBox) return;

        alertBox.innerHTML = `<div class="alert alert-${type} py-2 mb-0">${message}</div>`;
    },

    clearAlert: function () {
        const alertBox = document.getElementById('paymentsAlert');
        if (alertBox) alertBox.innerHTML = '';
    },

    loadInvoices: function () {
        const container = document.getElementById('paymentsContent');
        if (!container) return;

        if (this.currentInvoiceId) {
            this.loadInvoiceDetail(this.currentInvoiceId);
            return;
        }

        this.clearAlert();
        container.innerHTML = '<div class="card"><div class="card-body text-center py-5 text-muted">Loading invoices...</div></div>';

        const params = new URLSearchParams();

        console.debug('[PaymentsPage] Loading invoices');
        fetch(`../api/payments.php?action=list&${params.toString()}`, { headers: this.getRequestHeaders() })
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    throw new Error(res.message || 'Unable to load payments.');
                }

                this.invoices = Array.isArray(res.data) ? res.data : [];
                this.renderInvoiceList();
            })
            .catch(err => {
                container.innerHTML = '<div class="card"><div class="card-body text-danger">Unable to load payments. ' + (err.message || '') + '</div></div>';
            });
    },

    renderInvoiceList: function () {
        const container = document.getElementById('paymentsContent');
        if (!container) return;

        if (this.invoices.length === 0) {
            container.innerHTML = '<div class="card"><div class="card-body text-center text-muted py-5">No invoices found.</div></div>';
            return;
        }

        const rows = this.invoices.map(invoice => {
            const total = parseFloat(invoice.total_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const paid = parseFloat(invoice.paid_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const balance = parseFloat(invoice.balance_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            return `
                <tr>
                    <td><strong>${invoice.invoice_number || ''}</strong></td>
                    <td>${invoice.shipment_tracking_number || ''}</td>
                    <td>${invoice.consignee_name || ''}</td>
                    <td>AED ${total}</td>
                    <td>AED ${paid}</td>
                    <td>AED ${balance}</td>
                    <td>${this.getPaymentBadge(invoice.payment_status)}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" data-action="view" data-id="${invoice.id}">View</button>
                            <button class="btn btn-sm btn-success" data-action="pay" data-id="${invoice.id}">Record Payment</button>
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
                                    <th>Invoice</th>
                                    <th>Tracking</th>
                                    <th>Consignee</th>
                                    <th>Total</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                </div>
            </div>`;

        container.querySelectorAll('[data-action="view"]').forEach(btn => btn.addEventListener('click', () => {
            this.openHistoryModal(btn.getAttribute('data-id'));
        }));
        container.querySelectorAll('[data-action="pay"]').forEach(btn => btn.addEventListener('click', () => this.openPaymentModal(btn.getAttribute('data-id'))));
    },

    showInvoiceList: function () {
        App.loadPage('payments');
    },

    loadInvoiceDetail: function (invoiceId) {
        this.currentInvoiceId = invoiceId;
        document.getElementById('paymentsBackBtn')?.classList.remove('d-none');
        const container = document.getElementById('paymentsContent');
        if (!container) return;

        container.innerHTML = '<div class="card"><div class="card-body text-center py-5 text-muted">Loading invoice details...</div></div>';

        console.debug('[PaymentsPage] Loading invoice detail', invoiceId);
        fetch(`../api/payments.php?action=detail&id=${invoiceId}`, { headers: this.getRequestHeaders() })
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    throw new Error(res.message || 'Unable to load invoice details.');
                }

                this.renderInvoiceDetail(res.data);
                if (this.pendingHistoryAction) {
                    this.pendingHistoryAction = false;
                    this.openHistoryModal(invoiceId);
                } else if (this.pendingPaymentAction) {
                    this.pendingPaymentAction = false;
                    this.openPaymentModal(invoiceId);
                }
            })
            .catch(err => {
                container.innerHTML = '<div class="card"><div class="card-body text-danger">Unable to load invoice details. ' + (err.message || '') + '</div></div>';
            });
    },

    renderInvoiceDetail: function (data) {
        const container = document.getElementById('paymentsContent');
        if (!container) return;

        const invoice = data.invoice || {};
        this.currentInvoiceContext = invoice;
        const payments = Array.isArray(data.payments) ? data.payments : [];
        const total = parseFloat(invoice.total_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const paid = parseFloat(invoice.paid_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const balance = parseFloat(invoice.balance_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        container.innerHTML = `
            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Invoice Overview</h5>
                            <p class="mb-2"><strong>Invoice:</strong> ${invoice.invoice_number || ''}</p>
                            <p class="mb-2"><strong>Tracking:</strong> ${invoice.shipment_tracking_number || ''}</p>
                            <p class="mb-2"><strong>Consignee:</strong> ${invoice.consignee_name || ''}</p>
                            <p class="mb-2"><strong>Total:</strong> AED ${total}</p>
                            <p class="mb-2"><strong>Paid:</strong> AED ${paid}</p>
                            <p class="mb-2"><strong>Balance:</strong> AED ${balance}</p>
                            <p class="mb-2"><strong>Status:</strong> ${this.getPaymentBadge(invoice.payment_status)}</p>
                            <p class="mb-0"><strong>Progress:</strong> ${invoice.payment_progress || 0}%</p>
                            <div class="progress mt-3">
                                <div class="progress-bar bg-success" role="progressbar" style="width: ${invoice.payment_progress || 0}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Payment History</h5>
                                <button class="btn btn-success btn-sm" data-action="pay" data-id="${invoice.id}">Record Payment</button>
                            </div>
                            ${payments.length ? `
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Amount</th>
                                                <th>Method</th>
                                                <th>Reference</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${payments.map(payment => `
                                                <tr>
                                                    <td>${payment.payment_date || ''}</td>
                                                    <td>AED ${parseFloat(payment.amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                    <td>${payment.payment_method || ''}</td>
                                                    <td>${payment.reference_number || '—'}</td>
                                                    <td>${payment.notes || '—'}</td>
                                                </tr>`).join('')}
                                        </tbody>
                                    </table>
                                </div>` : '<div class="text-muted py-3">No payment records yet.</div>'}
                        </div>
                    </div>
                </div>
            </div>`;

        container.querySelector('[data-action="pay"]').addEventListener('click', () => this.openPaymentModal(invoice.id));
    },

    openHistoryModal: function (invoiceId) {
        const invoice = this.currentInvoiceContext && String(this.currentInvoiceContext.id) === String(invoiceId)
            ? this.currentInvoiceContext
            : this.invoices.find(item => String(item.id) === String(invoiceId)) || null;

        if (!invoice) {
            this.showAlert('Invoice details are not available yet.', 'warning');
            return;
        }

        this.clearAlert();
        this.closeModal();

        const loadingHtml = `
            <div class="modal fade show payment-history-modal" tabindex="-1" style="display:block;background:rgba(0,0,0,.55);">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Payment History - ${invoice.invoice_number || invoiceId}</h5>
                            <button type="button" class="btn-close" data-action="close-modal"></button>
                        </div>
                        <div class="modal-body text-center py-5">
                            <div class="text-muted">Loading payment history...</div>
                        </div>
                    </div>
                </div>
            </div>`;

        document.body.insertAdjacentHTML('beforeend', loadingHtml);
        document.querySelector('.payment-history-modal [data-action="close-modal"]')?.addEventListener('click', () => this.closeModal());

        fetch(`../api/payments.php?action=detail&id=${invoiceId}`, { headers: this.getRequestHeaders() })
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    throw new Error(res.message || 'Unable to load payment history.');
                }

                const payments = Array.isArray(res.data?.payments) ? res.data.payments : [];
                const total = parseFloat(invoice.total_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const paid = parseFloat(invoice.paid_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const balance = parseFloat(invoice.balance_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const modalHtml = `
                    <div class="modal fade show payment-history-modal" tabindex="-1" style="display:block;background:rgba(0,0,0,.55);">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Payment History - ${invoice.invoice_number || invoiceId}</h5>
                                    <button type="button" class="btn-close" data-action="close-modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-3"><strong>Invoice:</strong><br>${invoice.invoice_number || '—'}</div>
                                        <div class="col-md-3"><strong>Consignee:</strong><br>${invoice.consignee_name || '—'}</div>
                                        <div class="col-md-2"><strong>Total:</strong><br>AED ${total}</div>
                                        <div class="col-md-2"><strong>Paid:</strong><br>AED ${paid}</div>
                                        <div class="col-md-2"><strong>Balance:</strong><br>AED ${balance}</div>
                                    </div>
                                    ${payments.length ? `
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Amount</th>
                                                        <th>Method</th>
                                                        <th>Reference</th>
                                                        <th>Notes</th>
                                                        <th>Print</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${payments.map((payment, index) => `
                                                        <tr>
                                                            <td>${payment.payment_date || '—'}</td>
                                                            <td>AED ${parseFloat(payment.amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                            <td>${payment.payment_method || '—'}</td>
                                                            <td>${payment.reference_number || '—'}</td>
                                                            <td>${payment.notes || '—'}</td>
                                                            <td><button class="btn btn-outline-primary btn-sm" type="button" data-action="print-payment" data-payment-index="${index}">Print</button></td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>` : '<div class="text-muted">No payment history available yet.</div>'}
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-secondary" data-action="close-modal">Close</button>
                                    <button class="btn btn-outline-primary" data-action="print-history">Print All</button>
                                </div>
                            </div>
                        </div>
                    </div>`;

                document.querySelector('.payment-history-modal')?.remove();
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                document.querySelector('.payment-history-modal [data-action="close-modal"]')?.addEventListener('click', () => this.closeModal());
                document.querySelector('.payment-history-modal [data-action="print-history"]')?.addEventListener('click', () => this.printHistoryModal());
                document.querySelectorAll('.payment-history-modal [data-action="print-payment"]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const paymentIndex = Number(button.getAttribute('data-payment-index'));
                        const payment = payments[paymentIndex];
                        if (payment) {
                            this.printPaymentEntry(payment, invoice);
                        }
                    });
                });
            })
            .catch(err => {
                document.querySelector('.payment-history-modal')?.remove();
                this.showAlert(err.message || 'Unable to load payment history.', 'danger');
            });
    },

    printPaymentEntry: function (payment, invoice) {
        if (!payment || this.isPrintInProgress) return;

        this.isPrintInProgress = true;
        const paymentDate = payment.payment_date || '—';
        const amount = parseFloat(payment.amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const method = payment.payment_method || '—';
        const reference = payment.reference_number || '—';
        const notes = payment.notes || '—';
        const invoiceNumber = invoice?.invoice_number || '—';
        const consignee = invoice?.consignee_name || '—';

        const printContent = document.createElement('div');
        printContent.className = 'payment-print-single';
        printContent.innerHTML = `
            <div style="font-family: Arial, sans-serif; max-width: 700px; margin: 0 auto; padding: 24px; color: #000;">
                <h3 style="margin-bottom: 16px;">Payment Receipt</h3>
                <p><strong>Invoice:</strong> ${invoiceNumber}</p>
                <p><strong>Consignee:</strong> ${consignee}</p>
                <p><strong>Payment Date:</strong> ${paymentDate}</p>
                <p><strong>Amount:</strong> AED ${amount}</p>
                <p><strong>Method:</strong> ${method}</p>
                <p><strong>Reference:</strong> ${reference}</p>
                <p><strong>Notes:</strong> ${notes}</p>
            </div>
        `;

        document.body.appendChild(printContent);

        if (!document.getElementById('payments-single-print-style')) {
            const style = document.createElement('style');
            style.id = 'payments-single-print-style';
            style.textContent = `
                .payment-print-single { display: none; }
                @media print {
                    body > *:not(.payment-print-single) { display: none !important; }
                    body { background: #fff !important; margin: 0; }
                    .payment-print-single {
                        display: block !important;
                        background: #fff !important;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        window.addEventListener('afterprint', () => {
            this.isPrintInProgress = false;
            printContent.remove();
            document.getElementById('payments-single-print-style')?.remove();
        }, { once: true });

        window.print();
    },

    printHistoryModal: function () {
        const modal = document.querySelector('.payment-history-modal');
        if (!modal || this.isPrintInProgress) return;

        this.isPrintInProgress = true;

        if (!document.getElementById('payments-print-style')) {
            const style = document.createElement('style');
            style.id = 'payments-print-style';
            style.textContent = `
                @media print {
                    body > *:not(.payment-history-modal) { display: none !important; }
                    body { background: #fff !important; }
                    .payment-history-modal {
                        position: static !important;
                        display: block !important;
                        background: #fff !important;
                        padding: 0 !important;
                    }
                    .payment-history-modal .modal-dialog {
                        max-width: 100% !important;
                        margin: 0 !important;
                    }
                    .payment-history-modal .modal-content {
                        border: 0 !important;
                        box-shadow: none !important;
                    }
                    .payment-history-modal .modal-footer { display: none !important; }
                    .payment-history-modal [data-action="print-payment"],
                    .payment-history-modal [data-action="print-history"] { display: none !important; }
                }
            `;
            document.head.appendChild(style);
        }

        window.addEventListener('afterprint', () => {
            this.isPrintInProgress = false;
            document.getElementById('payments-print-style')?.remove();
        }, { once: true });

        window.print();
    },

    openPaymentModal: function (invoiceId) {
        const invoice = this.currentInvoiceContext && String(this.currentInvoiceContext.id) === String(invoiceId)
            ? this.currentInvoiceContext
            : this.invoices.find(item => String(item.id) === String(invoiceId)) || null;
        const modalHtml = `
            <div class="modal fade show" tabindex="-1" style="display:block;background:rgba(0,0,0,.5);">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Record Payment</h5>
                            <button type="button" class="btn-close" data-action="close-modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="paymentForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Invoice</label>
                                        <input class="form-control" type="text" value="${invoice ? invoice.invoice_number : invoiceId}" readonly>
                                        <input type="hidden" name="invoice_id" value="${invoiceId}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Amount</label>
                                        <input class="form-control" type="number" name="amount" min="0.01" step="0.01" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Payment Method</label>
                                        <select class="form-select" name="payment_method" required>
                                            <option value="">Select method</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Cash">Cash</option>
                                            <option value="Card">Card</option>
                                            <option value="Online">Online</option>
                                            <option value="Cheque">Cheque</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Payment Date</label>
                                        <input class="form-control" type="date" name="payment_date" value="${new Date().toISOString().slice(0,10)}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Reference Number</label>
                                        <input class="form-control" type="text" name="reference_number" placeholder="Optional">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" name="notes" rows="3" placeholder="Optional notes"></textarea>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-action="close-modal">Cancel</button>
                            <button class="btn btn-success" id="savePaymentBtn">Save Payment</button>
                        </div>
                    </div>
                </div>
            </div>`;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        document.getElementById('savePaymentBtn').addEventListener('click', (e) => {
            e.preventDefault();
            this.savePayment();
        });
    },

    closeModal: function () {
        document.querySelector('.payment-history-modal')?.remove();
        document.querySelector('.modal.show:not(.payment-history-modal)')?.remove();
        document.getElementById('payments-print-style')?.remove();
    },

    savePayment: function () {
        const form = document.getElementById('paymentForm');
        if (!form) return;

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());
        payload.action = 'record';

        this.showAlert('Saving payment...', 'info');
        console.debug('[PaymentsPage] Saving payment', payload);
        fetch('../api/payments.php', {
            method: 'POST',
            headers: this.getRequestHeaders(),
            body: JSON.stringify(payload),
        })
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    throw new Error(res.message || 'Unable to save payment.');
                }
                this.closeModal();
                this.showAlert(res.message || 'Payment recorded successfully.', 'success');
                this.loadInvoices();
            })
            .catch(err => this.showAlert(err.message || 'Unable to save payment.', 'danger'));
    },

    getPaymentBadge: function (paymentStatus) {
        const map = {
            paid: '<span class="badge bg-success">Paid</span>',
            partially_paid: '<span class="badge bg-info">Partially Paid</span>',
            unpaid: '<span class="badge bg-warning text-dark">Unpaid</span>',
        };
        return map[paymentStatus] || '<span class="badge bg-secondary">Unknown</span>';
    },
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.PaymentsPage.init());
} else {
    window.PaymentsPage.init();
}
