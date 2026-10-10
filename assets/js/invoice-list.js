window.InvoicePage = {
    eventsBound: false,
    modalEventsBound: false,
    dataTable: null,

    init: function() {
        if (!this.eventsBound) {
            this.bindEvents();
            this.eventsBound = true;
        }

        this.loadInvoices();
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

    bindEvents: function() {
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
    },

    loadInvoices: function() {
        fetch(this.getApiUrl('invoices.php', { action: 'list' }))
            .then(r => r.ok ? r.json() : r.text().then(text => { throw new Error(text || 'Failed to load invoices'); }))
            .then(res => {
                const tbody = document.getElementById('invoiceTableBody');
                if (!tbody) return;

                tbody.innerHTML = '';

                if (res.success && Array.isArray(res.data)) {
                    if (res.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="9" class="text-muted text-center invoice-list-empty">No invoices found.</td></tr>';
                        return;
                    }

                    res.data.forEach(invoice => {
                        const statusBadge = this.getStatusBadge(invoice.status);
                        const paymentBadge = this.getPaymentBadge(invoice.payment_status);
                        const createdDate = new Date(invoice.created_at).toLocaleDateString();
                        const creatorText = invoice.creator_email || invoice.creator_username || '—';

                        tbody.insertAdjacentHTML('beforeend', `
                            <tr class="invoice-row" data-id="${invoice.id}">
                                <td><strong>${invoice.invoice_number || ''}</strong></td>
                                <td>${invoice.shipment_tracking_number || ''}</td>
                                <td>${invoice.consignee_name || ''}</td>
                                <td>${creatorText}</td>
                                <td><strong>AED ${parseFloat(invoice.total_amount).toLocaleString()}</strong></td>
                                <td>${statusBadge}</td>
                                <td>${paymentBadge}</td>
                                <td>${createdDate}</td>
                                <td>
                                    <div class="invoice-list-action-group">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><button class="dropdown-item" type="button" data-action="view" data-id="${invoice.id}">View Payment History</button></li>
                                                <li><button class="dropdown-item" type="button" data-action="preview" data-id="${invoice.id}">Preview Email</button></li>
                                                <li><button class="dropdown-item" type="button" data-action="email" data-id="${invoice.id}">Send Email</button></li>
                                                <li><button class="dropdown-item" type="button" data-action="print" data-id="${invoice.id}">Print</button></li>
                                                <li><button class="dropdown-item" type="button" data-action="edit" data-id="${invoice.id}">Edit</button></li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        `);
                    });

                    tbody.querySelectorAll('[data-action="view"]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            this.openHistoryModal(btn.getAttribute('data-id'));
                        });
                    });

                    tbody.querySelectorAll('[data-action="preview"]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            this.previewInvoiceEmail(btn.getAttribute('data-id'));
                        });
                    });

                    tbody.querySelectorAll('[data-action="email"]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            this.sendInvoiceEmail(btn.getAttribute('data-id'));
                        });
                    });

                    tbody.querySelectorAll('[data-action="print"]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            this.printInvoice(btn.getAttribute('data-id'));
                        });
                    });

                    tbody.querySelectorAll('[data-action="edit"]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            App.loadPageWithParams('invoice-create', { id: btn.getAttribute('data-id') });
                        });
                    });

                    this.initializeDataTable();
                }
            })
            .catch(() => {
                const tbody = document.getElementById('invoiceTableBody');
                if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="text-danger text-center invoice-list-empty">Failed to load invoices.</td></tr>';
            });
    },

    initializeDataTable: function() {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.dataTable === 'undefined') return;

        const table = document.getElementById('invoiceTable');
        if (!table) return;

        const $table = jQuery(table);
        if (jQuery.fn.dataTable.isDataTable(table)) {
            $table.DataTable().destroy();
        }

        this.dataTable = $table.DataTable({
            responsive: true,
            paging: true,
            searching: true,
            ordering: true,
            lengthChange: false,
            autoWidth: false,
            language: {
                search: 'Search:',
                zeroRecords: 'No matching records found',
                paginate: {
                    previous: 'Prev',
                    next: 'Next'
                }
            }
        });
    },

    openHistoryModal: function(invoiceId) {
        if (!invoiceId) return;

        this.closeModal();
        const loadingHtml = `
            <div class="modal fade show payment-history-modal" tabindex="-1" style="display:block;background:rgba(0,0,0,.55);">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Payment History</h5>
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

        fetch(this.getApiUrl('payments.php', { action: 'detail', id: invoiceId }))
            .then(r => r.ok ? r.json() : r.text().then(text => { throw new Error(text || 'Unable to load payment history.'); }))
            .then(res => {
                if (!res.success) {
                    throw new Error(res.message || 'Unable to load payment history.');
                }

                const invoice = res.data?.invoice || {};
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
                alert(err.message || 'Unable to load payment history.');
            });
    },

    printPaymentEntry: function(payment, invoice) {
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

        if (!document.getElementById('invoice-payment-single-print-style')) {
            const style = document.createElement('style');
            style.id = 'invoice-payment-single-print-style';
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
            document.getElementById('invoice-payment-single-print-style')?.remove();
        }, { once: true });

        window.print();
    },

    printHistoryModal: function() {
        const modal = document.querySelector('.payment-history-modal');
        if (!modal || this.isPrintInProgress) return;

        this.isPrintInProgress = true;

        if (!document.getElementById('invoice-payment-print-style')) {
            const style = document.createElement('style');
            style.id = 'invoice-payment-print-style';
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
            document.getElementById('invoice-payment-print-style')?.remove();
        }, { once: true });

        window.print();
    },

    closeModal: function() {
        document.querySelector('.payment-history-modal')?.remove();
        document.querySelector('.invoice-preview-modal')?.remove();
        document.querySelector('.modal-backdrop')?.remove();
        document.body.style.overflow = ''; 
        document.getElementById('invoice-payment-print-style')?.remove();
    },

    openPreviewModal: function(htmlContent) {
        this.closeModal();
        document.body.style.overflow = '';
        const modalHtml = `
            <div class="modal fade show invoice-preview-modal" tabindex="-1" style="display:block;">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Invoice Email Preview</h5>
                            <button type="button" class="btn-close" data-action="close-modal"></button>
                        </div>
                        <div class="modal-body p-0" style="height:640px;">
                            <iframe id="invoicePreviewFrame" style="width:100%; height:100%; border:0; background:#fff;"></iframe>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show"></div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const iframe = document.getElementById('invoicePreviewFrame');
        if (iframe) {
            iframe.srcdoc = htmlContent;
        }
    },

    previewInvoiceEmail: function(invoiceId) {
        if (!invoiceId) return;

        this.closeModal();
        document.body.style.overflow = '';
        const loadingHtml = `
            <div class="modal fade show invoice-preview-modal" tabindex="-1" style="display:block;">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Loading Email Preview...</h5>
                            <button type="button" class="btn-close" data-action="close-modal"></button>
                        </div>
                        <div class="modal-body text-center py-5">
                            <div class="text-muted">Loading preview…</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show"></div>
        `;

        document.body.insertAdjacentHTML('beforeend', loadingHtml);
        document.querySelector('.invoice-preview-modal [data-action="close-modal"]')?.addEventListener('click', () => this.closeModal());

        fetch(this.getApiUrl('invoices.php', { action: 'preview', id: invoiceId }))
            .then(r => r.ok ? r.json() : r.text().then(text => { throw new Error(text || 'Unable to load preview'); }))
            .then(res => {
                if (!res.success || !res.data || typeof res.data.html !== 'string') {
                    throw new Error(res.message || 'Invoice preview not available.');
                }
                this.openPreviewModal(res.data.html);
            })
            .catch(err => {
                this.closeModal();
                alert(err.message || 'Unable to load invoice preview.');
            });
    },

    sendInvoiceEmail: function(invoiceId) {
        if (!invoiceId) return;

        if (!confirm('Send this invoice to the consignee by email?')) {
            return;
        }

        const button = document.querySelector(`[data-action="email"][data-id="${invoiceId}"]`);
        if (button) {
            button.disabled = true;
            button.textContent = 'Sending...';
        }

        fetch(this.getApiUrl('invoices.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'send-email', id: invoiceId })
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert('Invoice email sent successfully.');
                    this.loadInvoices();
                } else {
                    alert(res.message || 'Unable to send invoice email.');
                }
            })
            .catch(() => alert('Unable to send invoice email.'))
            .finally(() => {
                if (button) {
                    button.disabled = false;
                    button.textContent = 'Send Email';
                }
            });
    },

    printInvoice: function(invoiceId) {
        if (!invoiceId) return;
        window.open(this.getApiUrl('invoices.php', { action: 'print', id: invoiceId }), '_blank', 'width=1000,height=900,scrollbars=yes');
    },

    getStatusBadge: function(status) {
        const badges = {
            'draft': '<span class="badge bg-secondary" style="color: white;">Draft</span>',
            'finalized': '<span class="badge bg-info" style="color: white;">Finalized</span>',
            'sent': '<span class="badge bg-primary" style="color: white;">Sent</span>',
            'paid': '<span class="badge bg-success" style="color: white;">Paid</span>',
            'cancelled': '<span class="badge bg-danger" style="color: white;">Cancelled</span>'
        };
        return badges[status] || '<span class="badge bg-secondary" style="color: white;">Unknown</span>';
    },

    getPaymentBadge: function(payment) {
        const badges = {
            'paid': '<span class="badge bg-success" style="color: white;">Paid</span>',
            'unpaid': '<span class="badge bg-warning" style="color: black;">Unpaid</span>',
            'partially_paid': '<span class="badge bg-info" style="color: white;">Partial</span>'
        };
        return badges[payment] || '<span class="badge bg-secondary" style="color: white;">-</span>';
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.InvoicePage.init());
} else {
    window.InvoicePage.init();
}
