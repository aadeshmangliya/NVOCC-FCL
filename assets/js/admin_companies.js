/**
 * Companies Management Page JavaScript
 * This file is loaded separately when companies page is loaded
 */

const CompaniesPage = {
    companies: [],
    currentCompanyId: null,

    init: function () {
        this.loadCompanies();
        this.bindEvents();
    },

    bindEvents: function () {
        $('#addCompanyBtn').off('click').on('click', () => this.resetForm());
        $('#saveCompanyBtn').off('click').on('click', () => this.saveCompany());
        
        // Manage Documents button - opens assignment modal
        $('#addDocumentToCompanyBtn').off('click').on('click', () => this.manageCompanyDocuments());

        $('#color_primary').off('input').on('input', function () { $('#color_primary_text').val($(this).val()); });
        $('#color_primary_text').off('input').on('input', function () { $('#color_primary').val($(this).val()); });
        $('#color_secondary').off('input').on('input', function () { $('#color_secondary_text').val($(this).val()); });
        $('#color_secondary_text').off('input').on('input', function () { $('#color_secondary').val($(this).val()); });

        $('#company_logo').off('change').on('change', (e) => this.handleLogoPreview(e));
        $('#removeLogo').off('click').on('click', () => this.removeLogo());
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

    loadCompanies: function () {
        const container = $('#companies-list-container');
        container.html('<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-3">Loading companies...</p></div>');

        const apiUrl = this.getApiUrl('/api/admin/companies.php');

        $.ajax({
            url: apiUrl,
            method: 'GET',
            dataType: 'json',
            xhrFields: { withCredentials: true },
            success: function (res) {
                if (res.success) {
                    CompaniesPage.companies = res.data;
                    CompaniesPage.displayCompanies();
                } else {
                    container.html('<div class="alert alert-danger">Failed to load companies: ' + (res.message || 'Unknown error') + '</div>');
                }
            },
            error: function (xhr, status, error) {
                container.html('<div class="alert alert-danger">Error loading companies. Please try again.</div>');
            }
        });
    },

    displayCompanies: function () {
        const container = $('#companies-list-container');
        container.empty();

        if (this.companies.length === 0) {
            container.html('<div class="alert alert-info">No companies found. Click "Add New Company" to create one.</div>');
            return;
        }

        let html = '<div class="row">';
        this.companies.forEach(company => {
            let logoHtml = '';
            if (company.logo_path) {
                logoHtml = '<img src="' + company.logo_path + '" alt="' + this.escapeHtml(company.display_name) + '" style="max-height: 60px; max-width: 150px; object-fit: contain;" />';
            } else {
                logoHtml = '<i class="fas fa-building fa-2x me-2" style="color: ' + company.color_primary + '"></i>';
            }

            html += `
            <div class="col-md-4 mb-4">
                <div class="card company-card h-100">
                    <div class="card-body position-relative">
                        <div class="company-status-badge">
                            ${company.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}
                        </div>
                        <div class="d-flex align-items-center mb-3 mt-2">
                            ${logoHtml}
                            <h5 class="card-title mb-0">${this.escapeHtml(company.display_name)}</h5>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="color-preview" style="background: ${company.color_primary}"></div>
                            <div class="color-preview" style="background: ${company.color_secondary}"></div>
                            <span class="ms-2 text-muted small">Theme Colors</span>
                        </div>
                        <p class="text-muted small mb-1"><strong>Key:</strong> ${this.escapeHtml(company.company_key)}</p>
                        <p class="text-muted small mb-1"><strong>Carrier:</strong> ${this.escapeHtml(company.carrier_name || 'N/A')}</p>
                        ${company.website ? `<p class="text-muted small"><strong>Web:</strong> ${this.escapeHtml(company.website)}</p>` : ''}
                    </div>
                    <div class="card-footer bg-transparent">
                        <button class="btn btn-sm btn-primary" onclick="CompaniesPage.editCompany(${company.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-info" onclick="CompaniesPage.viewDocuments(${company.id}, '${this.escapeHtml(company.display_name)}')">
                            <i class="fas fa-file-alt"></i> Documents
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="CompaniesPage.deleteCompany(${company.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        `;
        });
        html += '</div>';
        container.html(html);
    },

    escapeHtml: function (text) {
        if (!text) return '';
        return text.toString().replace(/[&<>]/g, function (m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    },

    resetForm: function () {
        $('#companyForm')[0].reset();
        $('#company_id').val('');
        $('#color_primary').val('#006a6a');
        $('#color_secondary').val('#d45f00');
        $('#color_primary_text').val('#006a6a');
        $('#color_secondary_text').val('#d45f00');
        $('#is_active').prop('checked', true);
        $('#company_logo').val('');
        $('#logoPreview').hide();
        $('#companyModalTitle').text('Add New Company');

        try {
            localStorage.setItem('selected_company_id', '0');
        } catch (e) {
        }

        $('#companyModal').modal('show');
    },

    editCompany: function (id) {
        const company = this.companies.find(c => c.id === id);
        if (company) {
            $('#company_id').val(company.id);
            $('#company_key').val(company.company_key);
            $('#company_name').val(company.name);
            $('#display_name').val(company.display_name);
            $('#website').val(company.website);
            $('#carrier_name').val(company.carrier_name);
            $('#color_primary').val(company.color_primary);
            $('#color_primary_text').val(company.color_primary);
            $('#color_secondary').val(company.color_secondary);
            $('#color_secondary_text').val(company.color_secondary);
            $('#is_active').prop('checked', company.is_active === 1);

            if (company.logo_path) {
                $('#logoImg').attr('src', company.logo_path);
                $('#logoPreview').show();
            } else {
                $('#logoPreview').hide();
            }

            $('#companyModalTitle').text('Edit Company');
            $('#companyModal').modal('show');
        }
    },

    saveCompany: function () {
        const formData = new FormData();
        const companyId = $('#company_id').val();
        if (companyId) {
            formData.append('id', companyId);
        }
        formData.append('company_key', $('#company_key').val());
        formData.append('name', $('#company_name').val());
        formData.append('display_name', $('#display_name').val());
        formData.append('website', $('#website').val());
        formData.append('carrier_name', $('#carrier_name').val());
        formData.append('color_primary', $('#color_primary').val());
        formData.append('color_secondary', $('#color_secondary').val());
        formData.append('is_active', $('#is_active').is(':checked') ? 1 : 0);

        if ($('#company_logo')[0].files.length > 0) {
            formData.append('logo', $('#company_logo')[0].files[0]);
        }

        const apiUrl = this.getApiUrl('/api/admin/companies.php');

        $.ajax({
            url: apiUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhrFields: { withCredentials: true },
            success: function (res) {
                if (res.success) {
                    $('#companyModal').modal('hide');
                    CompaniesPage.loadCompanies();
                    if (typeof App !== 'undefined' && App.notify) {
                        App.notify.success('Company saved successfully!');
                    } else {
                        alert('Company saved successfully!');
                    }
                } else {
                    alert('Error: ' + (res.message || 'Unknown error'));
                }
            },
            error: function () {
                alert('Failed to save company');
            }
        });
    },

    handleLogoPreview: function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (event) {
                $('#logoImg').attr('src', event.target.result);
                $('#logoPreview').show();
            };
            reader.readAsDataURL(file);
        }
    },

    removeLogo: function () {
        $('#company_logo').val('');
        $('#logoPreview').hide();
        $('#logoImg').attr('src', '');
    },

    deleteCompany: function (id) {
        if (confirm('Are you sure? This will also delete all documents for this company.')) {
            const apiUrl = this.getApiUrl('/api/admin/companies.php');

            $.ajax({
                url: apiUrl,
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ id: id }),
                xhrFields: { withCredentials: true },
                success: function (res) {
                    if (res.success) {
                        CompaniesPage.loadCompanies();
                        alert('Company deleted');
                    } else {
                        alert('Error: ' + (res.message || 'Unknown error'));
                    }
                }
            });
        }
    },


    viewDocuments: function (companyId, companyName) {
        this.currentCompanyId = companyId;

        // Set modal title
        $('#documentsModalTitle').text(`📄 ${companyName} - Documents`);
        $('#documentsModal').modal('show');

        // Load documents assigned to this company
        const apiUrl = this.getApiUrl('/api/admin/document-assignments.php?company_id=' + companyId);
        const container = $('#company-documents-list');
        container.html(`
            <div class="text-center p-5">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                <p class="mt-3 text-muted">Loading documents...</p>
            </div>
        `);

        $.ajax({
            url: apiUrl,
            method: 'GET',
            dataType: 'json',
            xhrFields: { withCredentials: true },
            success: (res) => {
                if (res.success && res.data && res.data.length > 0) {
                    this.displayCompanyDocuments(res.data, companyId, companyName);
                } else {
                    container.html(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            No documents assigned to this company yet.
                            <br><br>
                            Click "Manage Documents" button to assign documents.
                        </div>
                    `);
                }
            },
            error: (xhr, status, error) => {
                container.html(`
                    <div class="alert alert-danger">
                        Error loading documents. Please try again.
                        <br><small>${error}</small>
                    </div>
                `);
            }
        });
    },

    manageCompanyDocuments: function () {
        if (!this.currentCompanyId) {
            alert('No company selected');
            return;
        }

        const companyName = $('#documentsModalTitle').text().replace('📄 ', '').replace(' - Documents', '');
        const companyId = this.currentCompanyId;

        // Load all available documents
        const apiUrl = this.getApiUrl('/api/admin/documents.php?all=1');
        
        const modal = $(`
            <div class="modal fade" id="assignmentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Assign Documents to ${companyName}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="assignment-container">
                            <div class="text-center p-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-3">Loading documents...</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="confirmAssignmentBtn">Assign Selected</button>
                        </div>
                    </div>
                </div>
            </div>
        `);

        $('body').append(modal);

        $.ajax({
            url: apiUrl,
            method: 'GET',
            dataType: 'json',
            xhrFields: { withCredentials: true },
            success: (res) => {
                if (res.success && res.data && res.data.length > 0) {
                    let html = '<div class="list-group">';
                    res.data.forEach(doc => {
                        html += `
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="d-flex align-items-start" style="flex: 1;">
                                        <input class="form-check-input me-2 mt-1" type="checkbox" data-doc-id="${doc.id}" data-doc-name="${this.escapeHtml(doc.template_name)}">
                                        <div>
                                            <strong>${this.escapeHtml(doc.template_name)}</strong>
                                            <br>
                                            <small class="text-muted">${doc.document_type.replace(/_/g, ' ').toUpperCase()}</small>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="CompaniesPage.previewDocumentInAssignment(${doc.id}, '${this.escapeHtml(doc.template_name)}')">
                                        <i class="fas fa-eye"></i> Preview
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    modal.find('#assignment-container').html(html);
                } else {
                    modal.find('#assignment-container').html('<div class="alert alert-info">No documents available</div>');
                }
            },
            error: (xhr, status, error) => {
                modal.find('#assignment-container').html('<div class="alert alert-danger">Error loading documents</div>');
            }
        });

        // Handle assignment button click
        $(document).off('click', '#confirmAssignmentBtn').on('click', '#confirmAssignmentBtn', () => {
            const selectedDocs = modal.find('input[data-doc-id]:checked');
            if (selectedDocs.length === 0) {
                alert('Please select at least one document');
                return;
            }

            let assignedCount = 0;
            let failedCount = 0;
            const totalDocs = selectedDocs.length;

            selectedDocs.each((index, checkbox) => {
                const documentId = parseInt($(checkbox).data('doc-id'));

                // Assign single document to company
                const assignUrl = this.getApiUrl('/api/admin/document-assignments.php');
                $.ajax({
                    url: assignUrl,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        document_id: documentId,
                        company_ids: [companyId]
                    }),
                    xhrFields: { withCredentials: true },
                    success: (res) => {
                        if (res.success) {
                            assignedCount++;
                        } else {
                            failedCount++;
                        }

                        // If all done, refresh and close
                        if (assignedCount + failedCount === totalDocs) {
                            if (failedCount === 0) {
                                alert('All documents assigned successfully!');
                            } else {
                                alert('Assigned: ' + assignedCount + ', Failed: ' + failedCount);
                            }
                            
                            modal.modal('hide');
                            setTimeout(() => modal.remove(), 500);
                            
                            // Reload the documents list
                            const companyNameFromTitle = $('#documentsModalTitle').text().replace('📄 ', '').replace(' - Documents', '');
                            this.viewDocuments(companyId, companyNameFromTitle);
                        }
                    },
                    error: (xhr, status, error) => {
                        failedCount++;
                        
                        if (assignedCount + failedCount === totalDocs) {
                            alert('Assigned: ' + assignedCount + ', Failed: ' + failedCount);
                            modal.modal('hide');
                            setTimeout(() => modal.remove(), 500);
                            const companyNameFromTitle = $('#documentsModalTitle').text().replace('📄 ', '').replace(' - Documents', '');
                            this.viewDocuments(companyId, companyNameFromTitle);
                        }
                    }
                });
            });
        });

        const bsModal = new bootstrap.Modal(modal[0]);
        bsModal.show();
    },

    previewDocumentInAssignment: function (documentId, documentName) {
        if (!documentId) {
            alert('Invalid document');
            return;
        }

        // Fetch the document template via API
        const apiUrl = this.getApiUrl('/api/admin/documents.php?id=' + documentId);

        $.ajax({
            url: apiUrl,
            method: 'GET',
            dataType: 'json',
            xhrFields: { withCredentials: true },
            success: (res) => {
                if (res.success && res.data && res.data.length > 0) {
                    const doc = res.data[0];
                    const htmlTemplate = doc.html_template || '';

                    if (!htmlTemplate) {
                        alert('No HTML template available for this document');
                        return;
                    }

                    // Create sample data for preview
                    const sampleData = {
                        tracking_number: 'SAMPLE123456',
                        shipper_name: 'Sample Shipper Ltd.',
                        consignee_name: 'Sample Consignee Inc.',
                        vessel_name: 'Sample Vessel',
                        pol: 'KARACHI PORT',
                        pod: 'DUBAI PORT',
                        gross_weight: '1000 KG',
                        goods_description: 'Sample Goods Description',
                        color_primary: '#006a6a',
                        color_secondary: '#d45f00',
                        carrier_name: 'Sample Carrier',
                        company_name: 'Sample Company',
                        company_display_name: 'Sample Company',
                        company_website: 'www.sample.com',
                        logo_path: 'https://via.placeholder.com/200x60'
                    };

                    // Render template with sample data
                    let preview = htmlTemplate;
                    Object.keys(sampleData).forEach(key => {
                        preview = preview.replace(new RegExp('{{' + key + '}}', 'g'), sampleData[key]);
                    });

                    // Remove any remaining template variables
                    preview = preview.replace(/{{[^}]+}}/g, '');

                    const previewModal = $(`
                        <div class="modal fade" id="docPreviewModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Preview: ${documentName}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-0">
                                        <iframe style="width:100%; height:600px; border:none;"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);

                    $('body').append(previewModal);
                    
                    // Set iframe content using srcdoc
                    previewModal.find('iframe').attr('srcdoc', preview);
                    
                    const bsModal = new bootstrap.Modal(previewModal[0]);
                    bsModal.show();

                    previewModal.on('hidden.bs.modal', function() {
                        previewModal.remove();
                    });
                } else {
                    alert('Document not found');
                }
            },
            error: (xhr, status, error) => {
                alert('Error loading document preview. Please try again.');
            }
        });
    },

    displayCompanyDocuments: function (documents, companyId, companyName) {
        const container = $('#company-documents-list');
        container.empty();

        if (!documents || documents.length === 0) {
            container.html(`
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    No documents assigned to this company yet.
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
                <div class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between align-items-center">
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
                        <div class="btn-group-vertical">
                            <button class="btn btn-sm btn-primary" 
                                    onclick="CompaniesPage.previewDocument(${doc.document_id}, ${companyId}, '${this.escapeHtml(companyName)}')">
                                <i class="fas fa-eye me-1"></i> Preview
                            </button>
                            <button class="btn btn-sm btn-danger" 
                                    onclick="CompaniesPage.removeDocumentFromCompany(${doc.document_id}, ${companyId}, '${this.escapeHtml(doc.template_name || doc.document_type)}')">
                                <i class="fas fa-trash me-1"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.html(html);
    },

    previewDocument: function (documentId, companyId, companyName) {
        // Preview document with company branding

        if (!documentId) {
            alert('Invalid document');
            return;
        }

        // Show loading
        const iframe = document.getElementById('companyDocumentPreview');
        iframe.srcdoc = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Loading...</title>
                <style>
                    body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f5f5f5; }
                    .spinner { text-align: center; }
                </style>
            </head>
            <body>
                <div class="spinner">
                    <div style="font-size: 24px;">⏳ Loading preview...</div>
                </div>
            </body>
            </html>
        `;
        $('#documentPreviewModal').modal('show');

        // Fetch the document template and company data in parallel
        Promise.all([
            $.ajax({
                url: this.getApiUrl('/api/admin/documents.php?id=' + documentId),
                method: 'GET',
                dataType: 'json',
                xhrFields: { withCredentials: true }
            }),
            $.ajax({
                url: this.getApiUrl('/api/admin/companies.php?id=' + companyId),
                method: 'GET',
                dataType: 'json',
                xhrFields: { withCredentials: true }
            })
        ]).then(([docRes, compRes]) => {
            // Check if document was found
            if (!docRes.success || !docRes.data || docRes.data.length === 0) {
                iframe.srcdoc = `
                    <!DOCTYPE html>
                    <html>
                    <head><meta charset="UTF-8"><title>Error</title></head>
                    <body style="padding: 20px; font-family: Arial; color: #c41c00;">
                        <h2>❌ Document Not Found</h2>
                        <p>The document template could not be loaded.</p>
                    </body>
                    </html>
                `;
                return;
            }

            const doc = docRes.data[0];
            const htmlTemplate = doc.html_template || '';

            if (!htmlTemplate) {
                iframe.srcdoc = `
                    <!DOCTYPE html>
                    <html>
                    <head><meta charset="UTF-8"><title>Error</title></head>
                    <body style="padding: 20px; font-family: Arial; color: #c41c00;">
                        <h2>❌ No Template HTML</h2>
                        <p>This document does not have an HTML template.</p>
                    </body>
                    </html>
                `;
                return;
            }

            // Get company data for branding
            let companyLogo = '';
            let colorPrimary = '#006a6a';
            let colorSecondary = '#d45f00';
            let companyWebsite = '';

            if (compRes.success && compRes.data && compRes.data.length > 0) {
                const company = compRes.data[0];
                companyLogo = company.logo_url || '';
                colorPrimary = company.color_primary || '#006a6a';
                colorSecondary = company.color_secondary || '#d45f00';
                companyWebsite = company.website || '';
            }

            // Create sample data for preview
            const sampleData = {
                tracking_number: 'SAMPLE123456',
                shipper_name: 'Sample Shipper Ltd.',
                consignee_name: 'Sample Consignee Inc.',
                vessel_name: 'Sample Vessel',
                voyage_no: 'VOY001',
                imo_number: 'IMO1234567',
                pol: 'KARACHI PORT',
                pod: 'DUBAI PORT',
                gross_weight: '1000 KG',
                net_weight: '950 KG',
                volume_cbm: '50 CBM',
                goods_description: 'Sample Goods Description',
                color_primary: colorPrimary,
                color_secondary: colorSecondary,
                carrier_name: 'Sample Carrier',
                company_name: companyName,
                company_display_name: companyName,
                company_website: companyWebsite,
                logo_path: companyLogo || 'https://via.placeholder.com/200x60',
                booking_no: 'BOOK001',
                export_reference: 'EXP001',
                place_receipt: 'Karachi',
                place_of_delivery: 'Dubai',
                place_of_issue: 'Karachi',
                date_of_issue: new Date().toISOString().split('T')[0],
                etd: '2024-01-15',
                eta: '2024-01-20',
                no_of_original_bl: '3',
                num_originals: '3',
                originals_release_at: 'RELEASE AT DESTINATION1',
                freight_payable_at: 'DESTINATION',
                mode_load_area: 'Karachi Port',
                mode_discharge_area: 'Dubai Port',
                carrier_reference_no: 'REF001',
                type_of_shipment: 'FCL',
                marks_numbers: 'SAMPLE MARKS',
                container_no: 'CONT123456',
                seal_no: 'SEAL123',
                container_type: '40FT HC',
                num_packages: '1',
                package_type: 'Container',
                hs_code: '8708.30.90',
                commodity_type: 'Auto Parts',
                freight_terms: 'Prepaid',
                destination_charges: 'Collect',
                shipper_address: 'Sample Address, Karachi',
                shipper_phone: '+92 300 1234567',
                shipper_email: 'shipper@sample.com',
                consignee_address: 'Sample Address, Dubai',
                consignee_tax_id: 'NTN 123456',
                consignee_phone: '+971 4 123 4567',
                notify_name: 'Sample Notify',
                notify_address: 'Sample Address',
                notify_phone: '+971 4 123 4567',
                destination_agent: 'Sample Agent',
                destination_agent_address: 'Dubai Address',
                destination_agent_phone: '+971 4 123 4567',
                pre_carriage: 'By Road',
                forwarding_agent: 'Sample Forwarder',
                signatory: 'John Doe',
                designation: 'Shipping Manager',
                special_instructions: 'Handle with care',
                total_packages: '1',
                total_weight: '1000 KG',
                total_cbm: '50 CBM',
                total_value: '10000',
                currency: 'USD'
            };

            // Render template with sample data
            let preview = htmlTemplate;
            Object.keys(sampleData).forEach(key => {
                preview = preview.replace(new RegExp('{{' + key + '}}', 'g'), sampleData[key]);
            });

            // Remove any remaining template variables
            preview = preview.replace(/{{[^}]+}}/g, '');

            // Set iframe content
            iframe.srcdoc = preview;
        }).catch(error => {
            iframe.srcdoc = `
                <!DOCTYPE html>
                <html>
                <head><meta charset="UTF-8"><title>Error</title></head>
                <body style="padding: 20px; font-family: Arial; color: #c41c00;">
                    <h2>❌ Error Loading Preview</h2>
                    <p>An error occurred while loading the document preview. Please try again.</p>
                </body>
                </html>
            `;
        });
    },

    removeDocumentFromCompany: function (documentId, companyId, documentName) {
        if (!confirm(`Remove "${documentName}" from this company?`)) {
            return;
        }

        const apiUrl = this.getApiUrl('/api/admin/document-assignments.php');
        const data = {
            document_id: documentId,
            company_id: companyId
        };

        $.ajax({
            url: apiUrl,
            method: 'DELETE',
            contentType: 'application/json',
            data: JSON.stringify(data),
            xhrFields: { withCredentials: true },
            success: (res) => {
                if (res.success) {
                    if (typeof App !== 'undefined' && App.notify) {
                        App.notify.success('Document removed from company');
                    } else {
                        alert('Document removed');
                    }
                    // Reload documents list
                    const companyName = $('#documentsModalTitle').text().replace('📄 ', '').replace(' - Documents', '');
                    this.viewDocuments(companyId, companyName);
                } else {
                    alert('Error: ' + (res.message || 'Unknown error'));
                }
            },
            error: (xhr, status, error) => {
                alert('Error removing document');
            }
        });
    },

};

// Initialize when page is loaded by App
// The app.js will call CompaniesPage.init() when the companies page is loaded
// This flag prevents duplicate initialization if the user revisits the page