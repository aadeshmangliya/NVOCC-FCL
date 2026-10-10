/**
 * Dynamic Company & Documents Dropdown
 * Automatically loads companies and their documents
 */

const DocumentDropdown = {
    config: {
        apiBaseUrl: '/api',
        containerId: 'company-documents-container'
    },

    // Initialize the dropdown system
    init: function (containerId = 'company-documents-container') {
        this.config.containerId = containerId;
        this.loadCompanies();
    },

    // Load all active companies
    loadCompanies: function () {
        const container = document.getElementById(this.config.containerId);
        if (!container) return;

        container.innerHTML = `
            <div class="text-center p-3">
                <div class="spinner-border spinner-border-sm text-primary"></div> Loading companies...
            </div>
        `;

        // Attach Bearer token header when available (meta tag set for logged-in users)
        const bearerToken = document.querySelector('meta[name="api-bearer-token"]')?.getAttribute('content');
        const headers = {};
        if (bearerToken) headers['Authorization'] = 'Bearer ' + bearerToken;

        fetch(`${this.config.apiBaseUrl}/get_companies.php`, { headers })
            .then(res => res.json())
            .then(response => {
                if (response.success && response.data.length > 0) {
                    this.renderCompanies(response.data);
                } else {
                    container.innerHTML = '<div class="alert alert-warning">No companies found. Please contact administrator.</div>';
                }
            })
            .catch(error => {
                container.innerHTML = '<div class="alert alert-danger">Failed to load companies.</div>';
            });
    },

    // Render companies as dropdown menu items
    renderCompanies: function (companies) {
        const container = document.getElementById(this.config.containerId);
        if (!container) return;

        let html = `
            <div class="companies-dropdown">
                <button class="btn btn-outline-primary dropdown-toggle w-100" type="button" id="companyDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-building me-2"></i> Select Company
                </button>
                <ul class="dropdown-menu w-100" id="companyDropdownMenu" aria-labelledby="companyDropdownBtn">
        `;

        companies.forEach(company => {
            html += `
                <li>
                    <a class="dropdown-item company-item" href="#" data-company-id="${company.id}" data-company-key="${company.company_key}" data-company-name="${company.display_name}" style="color: ${company.color_primary}">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-building me-2"></i>
                            <span>${company.display_name}</span>
                        </div>
                    </a>
                </li>
            `;
        });

        html += `
                </ul>
            </div>
            <div id="documents-submenu" class="mt-3" style="display: none;">
                <div class="card">
                    <div class="card-header bg-light" id="selected-company-name">
                        <i class="fas fa-file-alt me-2"></i> Documents
                    </div>
                    <div class="list-group list-group-flush" id="documents-list"></div>
                </div>
            </div>
        `;

        container.innerHTML = html;

        // Add event listeners to company items
        document.querySelectorAll('.company-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const companyId = item.dataset.companyId;
                const companyKey = item.dataset.companyKey;
                const companyName = item.dataset.companyName;

                // Update button text
                const btn = document.getElementById('companyDropdownBtn');
                btn.innerHTML = `<i class="fas fa-building me-2"></i> ${companyName}`;

                // Load documents for this company
                this.loadDocuments(companyId, companyKey, companyName);
            });
        });
    },

    // Load documents for selected company
    loadDocuments: function (companyId, companyKey, companyName) {
        const submenu = document.getElementById('documents-submenu');
        const documentsList = document.getElementById('documents-list');
        const companyNameSpan = document.getElementById('selected-company-name');

        if (!submenu || !documentsList) return;

        companyNameSpan.innerHTML = `<i class="fas fa-building me-2"></i> ${companyName} - Documents`;
        documentsList.innerHTML = '<div class="list-group-item text-center"><div class="spinner-border spinner-border-sm"></div> Loading documents...</div>';
        submenu.style.display = 'block';

        const bearerToken2 = document.querySelector('meta[name="api-bearer-token"]')?.getAttribute('content');
        const headers2 = {};
        if (bearerToken2) headers2['Authorization'] = 'Bearer ' + bearerToken2;

        fetch(`${this.config.apiBaseUrl}/get_company_documents.php?company_id=${companyId}`, { headers: headers2 })
            .then(res => res.json())
            .then(response => {
                if (response.success && response.data.length > 0) {
                    this.renderDocuments(response.data, companyKey);
                } else {
                    console.warn('❌ No documents found in response');
                    documentsList.innerHTML = '<div class="list-group-item text-muted">No documents available for this company.</div>';
                }
            })
            .catch(error => {
                console.error('❌ Error loading documents:', error);
                documentsList.innerHTML = '<div class="list-group-item text-danger">Failed to load documents.</div>';
            });
    },

    // Render documents with submenu
    renderDocuments: function (documents, companyKey) {
        const documentsList = document.getElementById('documents-list');
        if (!documentsList) return;

        if (!documents || documents.length === 0) {
            console.warn('%c❌ NO DOCUMENTS FOUND!', 'color: red; font-weight: bold;');
            documentsList.innerHTML = '<div class="alert alert-warning">No documents found</div>';
            return;
        }

        // Group documents by type
        const grouped = {};
        documents.forEach(doc => {
            const type = doc.document_type.replace(/_/g, ' ').toUpperCase();
            if (!grouped[type]) grouped[type] = [];
            grouped[type].push(doc);
        });


        let html = '';

        // Create nested dropdown structure
        for (const [type, docs] of Object.entries(grouped)) {
            if (docs.length === 1) {
                // Single document - direct link
                const doc = docs[0];
                html += `
                    <a href="#" class="list-group-item list-group-item-action document-link" 
                       data-company-key="${companyKey}" 
                       data-document-type="${doc.document_type}"
                       data-template-name="${doc.template_name}">
                        <i class="fas fa-file-pdf text-danger me-2"></i>
                        <span>${doc.template_name}</span>
                        <small class="text-muted ms-2">(${type})</small>
                        <i class="fas fa-download float-end text-muted"></i>
                    </a>
                `;
            } else {
                // Multiple documents - create sub-dropdown
                html += `
                    <div class="list-group-item p-0">
                        <div class="dropdown">
                            <button class="btn btn-light w-100 text-start dropdown-toggle" type="button" data-bs-toggle="dropdown" style="border-radius: 0; padding: 12px 20px;">
                                <i class="fas fa-folder me-2"></i> ${type} (${docs.length})
                            </button>
                            <ul class="dropdown-menu w-100">
                `;

                docs.forEach(doc => {
                    html += `
                        <li>
                            <a class="dropdown-item document-link" href="#" 
                               data-company-key="${companyKey}" 
                               data-document-type="${doc.document_type}"
                               data-template-name="${doc.template_name}">
                                <i class="fas fa-file-pdf text-danger me-2"></i>
                                ${doc.template_name}
                                ${doc.is_default ? '<span class="badge bg-primary ms-2">Default</span>' : ''}
                            </a>
                        </li>
                    `;
                });

                html += `
                            </ul>
                        </div>
                    </div>
                `;
            }
        }

        documentsList.innerHTML = html;

        // Add event listeners to document links
        document.querySelectorAll('.document-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const companyKey = link.dataset.companyKey;
                const documentType = link.dataset.documentType;
                const templateName = link.dataset.templateName;

                this.generateDocument(companyKey, documentType, templateName);
            });
        });
    },

    // Get base URL dynamically
    getBaseUrl: function () {
        let path = window.location.pathname || '/';
        const adminIndex = path.indexOf('/admin/');

        if (adminIndex !== -1) {
            return path.substring(0, adminIndex);
        }

        const normalized = path.replace(/\/+$/, '');
        const lastSlash = normalized.lastIndexOf('/');

        if (lastSlash > 0) {
            const lastSegment = normalized.substring(lastSlash + 1);
            if (lastSegment.includes('.')) {
                return normalized.substring(0, lastSlash);
            }
        }

        return normalized || '';
    },

    // Generate document for shipment
    generateDocument: function (companyKey, documentType, templateName) {
        const trackingNumber = document.getElementById('tracking-number')?.value ||
            prompt('Enter tracking number for this document:');

        if (!trackingNumber) {
            alert('Tracking number is required');
            return;
        }

        // Get dynamic base URL
        const baseUrl = this.getBaseUrl();
        const url = `${baseUrl}/api/manifest_v2.php?tracking_number=${encodeURIComponent(trackingNumber)}&company=${companyKey}&document_type=${documentType}&template_name=${encodeURIComponent(templateName)}`;

        // Show loading
        const btn = event?.target?.closest('.document-link');
        if (btn) {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Generating...';

            // Open in new tab
            window.open(url, '_blank');

            setTimeout(() => {
                btn.innerHTML = originalHtml;
            }, 1000);
        } else {
            window.open(url, '_blank');
        }
    },

    // Get document URL (for programmatic use)
    getDocumentUrl: function (trackingNumber, companyKey, documentType, templateName) {
        const baseUrl = this.getBaseUrl();
        return `${baseUrl}/api/manifest_v2.php?tracking_number=${encodeURIComponent(trackingNumber)}&company=${companyKey}&document_type=${documentType}&template_name=${encodeURIComponent(templateName)}`;
    },
};

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Check if container exists
    if (document.getElementById('company-documents-container')) {
        DocumentDropdown.init();
    }
});