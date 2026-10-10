/**
 * Document-Company Assignment Management
 * Handles global documents with multi-company assignments
 */

const DocumentAssignmentManager = {
    documents: [],
    companies: [],
    currentCompanyId: null,

    init: function () {
        this.loadCompanies();
        this.bindDocumentEvents();
        this.bindCompanyEvents();
    },

    getApiUrl: function (endpoint) {
        let path = window.location.pathname || '/';
        const adminIndex = path.indexOf('/admin/');
        let basePath = '';

        if (adminIndex !== -1) {
            basePath = path.substring(0, adminIndex);
        } else {
            const normalized = path.replace(/\/+$/, '');
            const lastSlash = normalized.lastIndexOf('/');
            if (lastSlash > 0) {
                const lastSegment = normalized.substring(lastSlash + 1);
                if (lastSegment.includes('.')) {
                    basePath = normalized.substring(0, lastSlash);
                } else {
                    basePath = normalized;
                }
            }
        }

        return basePath + endpoint;
    },

    // ============================================
    // DOCUMENT MANAGEMENT (Documents.php page)
    // ============================================

    bindDocumentEvents: function () {
        // Load companies dropdown for document assignment
        $(document).on('show.bs.modal', '#documentModal', () => this.loadCompaniesDropdown());
    },

    loadCompanies: function () {
        const apiUrl = this.getApiUrl('/api/admin/companies.php');
        $.ajax({
            url: apiUrl,
            method: 'GET',
            dataType: 'json',
            xhrFields: { withCredentials: true },
            beforeSend: function (xhr) {
                const bearer = document.querySelector('meta[name="api-bearer-token"]')?.getAttribute('content');
                if (bearer) xhr.setRequestHeader('Authorization', 'Bearer ' + bearer);
            },
            success: (res) => {
                if (res.success) {
                    this.companies = res.data || [];
                }
            },
            error: (xhr, status, error) => {
            }
        });
    },

    loadCompaniesDropdown: function () {
        const dropdown = $('#doc_company_ids');
        dropdown.empty();

        if (this.companies.length === 0) {
            dropdown.html('<option value="">No companies available</option>').prop('disabled', true);
            return;
        }

        this.companies.forEach(company => {
            dropdown.append(`
                <option value="${company.id}">
                    ${company.display_name || company.name}
                </option>
            `);
        });

        // Update Select2 if it exists
        if (typeof $ !== 'undefined' && $.fn.select2) {
            dropdown.select2({ placeholder: 'Select one or more companies' });
        }
    },

    // Handle document form submission in documents.php
    saveDocumentWithCompanies: function () {
        const data = {
            id: $('#doc_id').val() || null,
            document_type: $('#document_type').val(),
            template_name: $('#template_name').val(),
            html_template: $('#html_template').val(),
            is_active: $('#is_active_doc').is(':checked') ? 1 : 0,
            company_ids: $('#doc_company_ids').val() ? $('#doc_company_ids').val().map(v => parseInt(v)) : []
        };

        if (!data.document_type || !data.template_name) {
            alert('Please fill in all required fields');
            return;
        }

        if (data.company_ids.length === 0) {
            alert('Please select at least one company');
            return;
        }

        const apiUrl = this.getApiUrl('/api/admin/documents.php');

        $.ajax({
            url: apiUrl,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            xhrFields: { withCredentials: true },
            success: (res) => {
                if (res.success) {
                    $('#documentModal').modal('hide');
                    if (typeof DocumentsPage !== 'undefined') {
                        DocumentsPage.loadDocuments();
                    }
                    if (typeof App !== 'undefined' && App.notify) {
                        App.notify.success('Document saved and assigned to companies!');
                    } else {
                        alert('Document saved successfully!');
                    }
                } else {
                    alert('Error: ' + (res.message || 'Unknown error'));
                }
            },
            error: (xhr, status, error) => {
                alert('Error saving document.');
            }
        });
    },

    // ============================================
    // COMPANY DOCUMENTS (Companies.php page)
    // ============================================

    bindCompanyEvents: function () {
        $(document).on('click', '.btn-company-documents', (e) => {
            const companyId = $(e.currentTarget).data('company-id');
            const companyName = $(e.currentTarget).data('company-name');
            this.loadCompanyDocuments(companyId, companyName);
        });

        $(document).on('click', '.btn-preview-document', (e) => {
            const documentId = $(e.currentTarget).data('document-id');
            const companyId = $(e.currentTarget).data('company-id');
            this.previewDocumentForCompany(documentId, companyId);
        });

        $(document).on('click', '#addDocumentToCompanyBtn', () => {
            if (this.currentCompanyId) {
                window._selectedCompanyId = this.currentCompanyId;
                window._selectedCompanyName = $('#documentsModalTitle').text();
                $('#documentsModal').modal('hide');
                if (typeof App !== 'undefined') {
                    App.loadPage('documents');
                }
            }
        });
    },

    loadCompanyDocuments: function (companyId, companyName) {
        this.currentCompanyId = companyId;
        $('#documentsModalTitle').text(`📄 ${companyName} - Documents`);

        const container = $('#company-documents-list');
        container.html(`
            <div class="text-center p-5">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                <p class="mt-3 text-muted">Loading documents...</p>
            </div>
        `);

        const apiUrl = this.getApiUrl(`/api/admin/document-assignments.php?company_id=${companyId}`);

        $.ajax({
            url: apiUrl,
            method: 'GET',
            dataType: 'json',
            xhrFields: { withCredentials: true },
            beforeSend: function (xhr) {
                const bearer = document.querySelector('meta[name="api-bearer-token"]')?.getAttribute('content');
                if (bearer) xhr.setRequestHeader('Authorization', 'Bearer ' + bearer);
            },
            success: (res) => {
                if (res.success) {
                    this.displayCompanyDocuments(res.data || [], companyId);
                } else {
                    container.html(`<div class="alert alert-warning">No documents assigned to this company yet.</div>`);
                }
            },
            error: (xhr, status, error) => {
                container.html(`<div class="alert alert-danger">Error loading documents. Please try again.</div>`);
            }
        });

        $('#documentsModal').modal('show');
    },

    displayCompanyDocuments: function (documents, companyId) {
        const container = $('#company-documents-list');
        container.empty();

        if (documents.length === 0) {
            container.html(`
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    No documents assigned to this company. 
                    Click "Manage Documents" button to assign documents.
                </div>
            `);
            return;
        }

        let html = '<div class="list-group">';
        documents.forEach(doc => {
            const docType = doc.document_type ? doc.document_type.replace(/_/g, ' ').toUpperCase() : 'CUSTOM';
            const statusBadge = doc.is_active ? 
                '<span class="badge bg-success">Active</span>' : 
                '<span class="badge bg-secondary">Inactive</span>';

            html += `
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <h6 class="mb-1">
                                <i class="fas fa-file me-2 text-primary"></i>
                                ${this.escapeHtml(doc.template_name || doc.document_type)}
                            </h6>
                            <p class="text-muted small mb-1">
                                <strong>Type:</strong> ${docType}
                            </p>
                            <p class="text-muted small mb-0">
                                ${statusBadge}
                            </p>
                        </div>
                        <button class="btn btn-sm btn-primary btn-preview-document" 
                                data-document-id="${doc.document_id}" 
                                data-company-id="${companyId}">
                            <i class="fas fa-eye me-1"></i> Preview
                        </button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.html(html);
    },

    previewDocumentForCompany: function (documentId, companyId) {
        // Fetch document HTML and company branding
        const apiUrl = this.getApiUrl('/api/get_company_documents.php?company_id=' + companyId);

        $.ajax({
            url: apiUrl,
            method: 'GET',
            dataType: 'json',
            xhrFields: { withCredentials: true },
            success: (res) => {
                if (res.success) {
                    // Find the document in response
                    const doc = res.data.find(d => d.id == documentId);
                    if (doc) {
                        // Create preview with company branding
                        const previewHtml = this.renderDocumentPreview(doc, companyId);
                        const iframe = document.getElementById('companyDocumentPreview');
                        iframe.srcdoc = previewHtml;
                        $('#documentPreviewModal').modal('show');
                    }
                }
            },
            error: (xhr, status, error) => {
                alert('Error loading document. Please try again.');
            }
        });
    },

    renderDocumentPreview: function (doc, companyId) {
        // This will render a preview with company-specific data
        // In a real scenario, you'd fetch the full template and render it with company data
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>${doc.template_name}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .preview-notice { background: #e3f2fd; padding: 10px; margin-bottom: 20px; border-radius: 4px; }
                </style>
            </head>
            <body>
                <div class="preview-notice">
                    <strong>Preview:</strong> Document will be rendered with company's branding, logo, and colors.
                </div>
                <div style="background: #f5f5f5; padding: 20px; border-radius: 4px;">
                    <h3>${doc.template_name || doc.document_type}</h3>
                    <p><strong>Type:</strong> ${doc.document_type}</p>
                    <p><strong>Status:</strong> ${doc.is_active ? 'Active' : 'Inactive'}</p>
                    <hr>
                    <p style="color: #999; font-size: 12px;">
                        The actual template HTML will be rendered here with dynamic company data, 
                        including logo, colors, and company information.
                    </p>
                </div>
            </body>
            </html>
        `;
    },

    escapeHtml: function (text) {
        if (!text) return '';
        return text.toString().replace(/[&<>]/g, function (m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
};

// Initialize when document is ready
$(document).ready(function () {
    // Check if we're on documents or companies page and initialize accordingly
    if (window.location.href.includes('documents')) {
        // Extend DocumentsPage to use company assignment functionality
        if (typeof DocumentsPage !== 'undefined') {
            const originalSave = DocumentsPage.saveDocument;
            DocumentsPage.saveDocument = function () {
                DocumentAssignmentManager.saveDocumentWithCompanies();
            };
        }
        DocumentAssignmentManager.loadCompanies();
    }

    if (window.location.href.includes('companies')) {
        DocumentAssignmentManager.init();
    }
});
