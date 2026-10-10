/**
 * Documents Management Page JavaScript
 * NEW FLOW: Documents created without company, assigned later from Companies section
 */

const DocumentsPage = {
    documents: [],
    variableGroups: [
        {
            title: 'Header & Shipment Identity',
            variables: [
                { name: 'company_display_name', description: 'Company name shown in the top logo/header title.' },
                { name: 'company_tagline', description: 'Short company tagline under the logo.' },
                { name: 'company_website', description: 'Website link shown in the header metadata.' },
                { name: 'company_established', description: 'Established year shown in the header.' },
                { name: 'tracking_number', description: 'Shipment or B/L reference number shown as the main document number.' },
                { name: 'booking_no', description: 'Booking/reference number used in the header section.' },
                { name: 'bl_type', description: 'Document type like OCEAN EXPORT, ORIGINAL, etc.' },
                { name: 'export_reference', description: 'Customer or export reference displayed beside booking number.' }
            ]
        },
        {
            title: 'Shipper & Consignee',
            variables: [
                { name: 'shipper_name', description: 'Name of exporter/shipper in the left section.' },
                { name: 'shipper_address', description: 'Full shipper address.' },
                { name: 'shipper_tax_id', description: 'Tax or NTN information if available.' },
                { name: 'shipper_vat', description: 'VAT number for shipper details.' },
                { name: 'consignee_name', description: 'Name of the consignee/customer.' },
                { name: 'consignee_address', description: 'Consignee address displayed under consignee section.' },
                { name: 'consignee_tax_id', description: 'Consignee tax/VAT/NTN number.' },
                { name: 'consignee_trn', description: 'TRN number used if the consignee is a UAE entity.' },
                { name: 'notify_name', description: 'Notify party name for cargo delivery follow-up.' },
                { name: 'notify_address', description: 'Notify party address.' },
                { name: 'notify_phone', description: 'Notify party contact number.' },
                { name: 'notify_email', description: 'Notify party email address.' }
            ]
        },
        {
            title: 'Agents & Route Details',
            variables: [
                { name: 'forwarding_agent', description: 'Forwarding agent or NVOCC name.' },
                { name: 'forwarding_agent_address', description: 'Forwarding agent address shown in the agent row.' },
                { name: 'destination_agent', description: 'Destination agent/correspondent displayed in the full-width block.' },
                { name: 'destination_agent_address', description: 'Destination agent address.' },
                { name: 'destination_agent_phone', description: 'Destination agent phone contact.' },
                { name: 'destination_agent_email', description: 'Destination agent email contact.' },
                { name: 'place_receipt', description: 'Place of receipt/origin for the transport.' },
                { name: 'pre_carriage', description: 'Pre-carriage mode or method before vessel loading.' },
                { name: 'pol', description: 'Port of Loading (POL) used in the shipment route section.' },
                { name: 'etd', description: 'Estimated Time of Departure for POL.' },
                { name: 'vessel_name', description: 'Name of the vessel carrying the goods.' },
                { name: 'voyage_no', description: 'Vessel voyage/route number.' },
                { name: 'imo_number', description: 'IMO number shown under vessel details.' },
                { name: 'pod', description: 'Port of Discharge (POD).' },
                { name: 'eta', description: 'Estimated Time of Arrival for POD.' },
                { name: 'place_of_delivery', description: 'Final delivery or discharge place.' },
                { name: 'freight_payable_at', description: 'Place where freight is payable.' },
                { name: 'type_of_shipment', description: 'Transport type like Ocean Freight / Air / Road.' },
                { name: 'carrier', description: 'Export carrier name shown in the route summary.' },
                { name: 'incoterms', description: 'Incoterms such as FOB, CIF, EXW, etc.' },
                { name: 'originals_release_at', description: 'Location where original documents are released.' },
                { name: 'issue_place', description: 'Place where document was issued.' },
                { name: 'date_of_issue', description: 'Date of issuance shown in the footer.' }
            ]
        },
        {
            title: 'Cargo Description & Package Details',
            variables: [
                { name: 'marks_numbers', description: 'Marks and numbers shown in the cargo description block.' },
                { name: 'container_no', description: 'Container number shown in the cargo details section.' },
                { name: 'seal_no', description: 'Seal number.' },
                { name: 'container_type', description: 'Container type such as 40 HC, 20 GP, etc.' },
                { name: 'num_packages', description: 'Total package count displayed in cargo summary.' },
                { name: 'package_type', description: 'Packaging type such as cartons, pallets, boxes.' },
                { name: 'goods_description', description: 'Main cargo description used in the goods area.' },
                { name: 'commodity_type', description: 'Commodity category or product type.' },
                { name: 'hs_code', description: 'HS code for the goods.' },
                { name: 'gross_weight', description: 'Gross cargo weight shown in KG.' },
                { name: 'net_weight', description: 'Net weight or weight after deductions.' },
                { name: 'volume_cbm', description: 'Cargo cubic meter volume.' },
                { name: 'freight_terms', description: 'Freight term like CIF, FOB, CFR.' },
                { name: 'destination_charges', description: 'Destination charge details in cargo note section.' },
                { name: 'special_instructions', description: 'Container or packing instructions / additional notes.' },
                { name: 'declaration', description: 'Declaration / hazardous instructions paragraph.' }
            ]
        },
        {
            title: 'Package Item Loop',
            variables: [
                { name: 'items', description: 'Starts the repeating item loop for each package item.' },
                { name: 'item_description', description: 'Description of each item inside the package table.' },
                { name: 'quantity', description: 'Item quantity or number of cartons for each row.' },
                { name: 'weight', description: 'Weight of each item row in kg.' },
                { name: 'cbm', description: 'CBM measurement for the individual item row.' },
                { name: 'unit_value', description: 'Per item or total unit value displayed in the currency column.' },
                { name: 'currency', description: 'Currency used in the item table totals.' },
                { name: 'total_packages', description: 'Total package count at the bottom of the table.' },
                { name: 'total_weight', description: 'Total shipment weight at the bottom.' },
                { name: 'total_cbm', description: 'Total shipment volume in CBM.' },
                { name: 'total_value', description: 'Total cargo value shown in the summary row.' }
            ]
        },
        {
            title: 'Footer & Legal Notes',
            variables: [
                { name: 'company_website', description: 'Website shown in the legal terms block.' },
                { name: 'no_of_original_bl', description: 'Number of original documents issued.' },
                { name: 'num_originals', description: 'Actual count of original copies.' },
                { name: 'place_of_issue', description: 'Place where B/L is issued.' },
                { name: 'date_of_issue', description: 'Date B/L was issued.' },
                { name: 'carrier_name', description: 'Carrier name displayed in signature section.' },
                { name: 'signatory', description: 'Authorized signatory name.' },
                { name: 'designation', description: 'Designation/title of the signatory.' }
            ]
        }
    ],

    init: function () {
        this.loadDocuments();
        this.bindEvents();
    },

    bindEvents: function () {
        $('#addDocumentBtn').off('click').on('click', () => this.resetForm());
        $('#saveDocumentBtn').off('click').on('click', () => this.saveDocument());
        $('#previewDocBtn').off('click').on('click', () => this.previewDocument());
    },

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

    getApiUrl: function (endpoint) {
        const baseUrl = this.getBaseUrl();
        return baseUrl + endpoint;
    },

    loadDocuments: function () {
        const container = $('#documents-list-container');
        container.html('<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-3">Loading documents...</p></div>');

        const apiUrl = this.getApiUrl('/api/admin/documents.php?all=1');

        $.ajax({
            url: apiUrl,
            method: 'GET',
            dataType: 'json',
            xhrFields: { withCredentials: true },
            success: (res) => {
                if (res.success) {
                    this.documents = res.data || [];
                    this.displayDocuments();
                } else {
                    container.html('<div class="alert alert-danger">Failed to load documents: ' + (res.message || 'Unknown error') + '</div>');
                }
            },
            error: (xhr, status, error) => {
                container.html('<div class="alert alert-danger">Error loading documents. Please try again.</div>');
            }
        });
    },

    displayDocuments: function () {
        const container = $('#documents-list-container');
        container.empty();

        if (this.documents.length === 0) {
            container.html('<div class="alert alert-info"><i class="fas fa-info-circle"></i> No document templates found. Click "Add Document Template" to create one.</div>');
            return;
        }

        let html = '<div class="row">';
        this.documents.forEach(doc => {
            html += `
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title">
                                    <i class="fas fa-file-alt me-2 text-primary"></i>
                                    ${this.escapeHtml(doc.template_name)}
                                </h5>
                            </div>
                            <p class="text-muted small mb-2">
                                <strong>Type:</strong> ${doc.document_type.replace(/_/g, ' ').toUpperCase()}
                            </p>
                            <p class="text-muted small mb-2">
                                <strong>Status:</strong> ${doc.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}
                            </p>
                            <small class="text-muted d-block mb-3">Created: ${doc.created_at || 'N/A'}</small>
                        </div>
                        <div class="card-footer bg-transparent">
                            <button class="btn btn-sm btn-primary" onclick="DocumentsPage.editDocument(${doc.id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="DocumentsPage.deleteDocument(${doc.id})">
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
        $('#documentForm')[0].reset();
        $('#doc_id').val('');
        $('#html_template').val('');
        $('#is_active_doc').prop('checked', true);
        $('#documentModalTitle').text('Add Document Template');
        $('#documentModal').modal('show');
    },

    saveDocument: function () {
        const data = {
            id: $('#doc_id').val() || null,
            document_type: $('#document_type').val(),
            template_name: $('#template_name').val(),
            html_template: $('#html_template').val(),
            is_active: $('#is_active_doc').is(':checked') ? 1 : 0
        };

        if (!data.document_type || !data.template_name) {
            alert('Please fill in all required fields');
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
                    this.loadDocuments();
                    alert('Document created successfully!');
                } else {
                    alert('Error: ' + (res.message || 'Unknown error'));
                }
            },
            error: (xhr, status, error) => {
                alert('Error saving document.');
            }
        });
    },

    editDocument: function (id) {
        const doc = this.documents.find(d => d.id === id);
        if (doc) {
            $('#doc_id').val(doc.id);
            $('#document_type').val(doc.document_type);
            $('#template_name').val(doc.template_name);
            $('#html_template').val(doc.html_template || '');
            $('#is_active_doc').prop('checked', doc.is_active === 1);
            $('#documentModalTitle').text('Edit Document Template');
            $('#documentModal').modal('show');
        }
    },

    deleteDocument: function (id) {
        if (!confirm('Are you sure you want to delete this document?')) {
            return;
        }

        const apiUrl = this.getApiUrl('/api/admin/documents.php');

        $.ajax({
            url: apiUrl,
            method: 'DELETE',
            contentType: 'application/json',
            data: JSON.stringify({ id: id }),
            xhrFields: { withCredentials: true },
            success: (res) => {
                if (typeof res === 'string') res = JSON.parse(res);
                if (res.success) {
                    this.loadDocuments();
                    alert('Document deleted!');
                } else {
                    alert('Error: ' + (res.message || 'Unknown error'));
                }
            },
            error: (xhr, status, error) => {
                alert('Error deleting document.');
            }
        });
    },

    showTemplateInfo: function () {
        const content = $('#templateVariablesContent');

        const visualMarkup = this.variableGroups.map(group => {
            const tagList = group.variables.map(variable => `
                <div class="variable-tag-card">
                    <div class="variable-tag">{{${this.escapeHtml(variable.name)}}}</div>
                    <div class="variable-tag-text">${this.escapeHtml(variable.description)}</div>
                </div>
            `).join('');

            return `
                <div class="guide-section-block">
                    <div class="guide-section-title">${this.escapeHtml(group.title)}</div>
                    <div class="guide-section-tags">${tagList}</div>
                </div>
            `;
        }).join('');

        content.html(`
            <style>
                * { box-sizing: border-box; }
                .variable-guide-outer {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    color: #111;
                    background: #eef2f5;
                    padding: 10px;
                }
                .variable-guide-demo {
                    max-width: 1260px;
                    margin: 0 auto 18px;
                    background: #fff;
                    border: 1px solid #e2e8f0;
                    box-shadow: 0 20px 35px -12px rgba(0,0,0,.12);
                }
                .variable-guide-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 26px 32px 22px;
                    background: #0f3b4f;
                    border-bottom: 3px solid #d67d2f;
                    color: #fff;
                }
                .variable-guide-brand {
                    background: #fff;
                    color: #0f3b4f;
                    display: inline-block;
                    padding: 8px 18px;
                    border-left: 3px solid #d67d2f;
                    font-size: 20px;
                    font-weight: 700;
                    letter-spacing: 1px;
                    line-height: 1.2;
                }
                .variable-guide-brand small {
                    display: block;
                    color: #2c5a74;
                    font-size: 11px;
                    font-weight: 500;
                    letter-spacing: .3px;
                    margin-top: 4px;
                    text-transform: none;
                }
                .variable-guide-docno {
                    text-align: right;
                }
                .variable-guide-docno .doc-title {
                    font-size: 34px;
                    letter-spacing: 5px;
                    margin: 0 0 5px;
                    color: #d67d2f;
                    font-weight: 700;
                    line-height: 1.1;
                }
                .variable-guide-docno .doc-sub {
                    font-size: 10px;
                    color: #cbdbe0;
                    letter-spacing: 1px;
                    margin: 0;
                }
                .variable-guide-docno .doc-badge {
                    display: inline-block;
                    margin-top: 10px;
                    background: #d67d2f;
                    color: #0f3b4f;
                    padding: 4px 14px;
                    border-radius: 30px;
                    font-size: 8px;
                    font-weight: 800;
                    letter-spacing: 1px;
                }
                .variable-guide-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    border-bottom: 1px solid #dee2e6;
                }
                .variable-guide-box {
                    padding: 14px 20px;
                    border-right: 1px solid #dee2e6;
                    min-height: 104px;
                    background: #fff;
                }
                .variable-guide-box:last-child { border-right: none; }
                .variable-guide-box-title {
                    font-size: 9px;
                    font-weight: 800;
                    text-transform: uppercase;
                    letter-spacing: 1.2px;
                    color: #2c5a74;
                    margin-bottom: 6px;
                    border-left: 2px solid #d67d2f;
                    padding-left: 7px;
                }
                .variable-guide-tag-row {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 6px;
                }
                .variable-guide-tag-pill {
                    display: inline-block;
                    background: #f1f5f9;
                    color: #0f3b4f;
                    border: 1px solid #dfeaf6;
                    border-radius: 20px;
                    padding: 4px 8px;
                    font-size: 10px;
                    font-weight: 700;
                }
                .variable-guide-full {
                    padding: 14px 22px;
                    border-bottom: 1px solid #dee2e6;
                }
                .cargo-title {
                    background: #f4f7fa;
                    padding: 10px 22px;
                    font-size: 11px;
                    font-weight: 800;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                    color: #0f3b4f;
                    border-bottom: 1px solid #dce5ec;
                    border-top: 1px solid #dce5ec;
                }
                .goods-header {
                    display: grid;
                    grid-template-columns: 0.8fr 1fr 0.6fr 2.2fr 0.8fr 0.8fr;
                    background: #f8fafc;
                    border-bottom: 1px solid #cfdfed;
                }
                .goods-header .goods-col {
                    font-size: 8.5px;
                    font-weight: 800;
                    text-transform: uppercase;
                    padding: 10px 12px;
                    letter-spacing: 0.8px;
                    color: #1e4a6b;
                    border-right: 1px solid #e2edf7;
                }
                .goods-row {
                    display: grid;
                    grid-template-columns: 0.8fr 1fr 0.6fr 2.2fr 0.8fr 0.8fr;
                    border-bottom: 1px solid #e9edf2;
                }
                .goods-row .goods-cell {
                    padding: 12px 12px;
                    font-size: 9.5px;
                    border-right: 1px solid #eef2f8;
                    word-break: break-word;
                    background: #fff;
                }
                .goods-row .goods-cell:last-child { border-right: none; }
                .variable-tag-box {
                    display: inline-block;
                    background: #f5f1e6;
                    color: #0f3b4f;
                    border: 1px solid #e8d8ad;
                    border-radius: 12px;
                    padding: 2px 6px;
                    font-size: 8.5px;
                    margin-top: 5px;
                    font-weight: 700;
                }
                .items-wrap {
                    padding: 16px 24px;
                    background: #fff;
                    border-top: 1px solid #e2e8f0;
                }
                .items-title {
                    font-size: 11px;
                    font-weight: 800;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                    color: #0f3b4f;
                    margin-bottom: 12px;
                    border-left: 3px solid #d67d2f;
                    padding-left: 12px;
                }
                .item-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 8.5px;
                }
                .item-table th {
                    background: #eef3fa;
                    padding: 9px 8px;
                    text-align: left;
                    font-weight: 800;
                    color: #0f3b4f;
                    border: 1px solid #cfdfed;
                }
                .item-table td {
                    border: 1px solid #e2e8f0;
                    padding: 8px;
                    background: #fff;
                }
                .total-row {
                    background: #f9f5e8;
                    font-weight: 800;
                }
                .footer-two {
                    display: grid;
                    grid-template-columns: 2.2fr 1fr;
                    border-top: 2px solid #cfdfed;
                }
                .terms-box {
                    padding: 20px 24px;
                    font-size: 7.5px;
                    line-height: 1.6;
                    text-align: justify;
                    border-right: 1px solid #e2e8f0;
                    background: #fff;
                }
                .sign-box {
                    padding: 20px 20px;
                    background: #fff;
                }
                .sign-place {
                    font-size: 10px;
                    font-weight: 600;
                    margin-bottom: 20px;
                }
                .carrier-name-footer {
                    font-weight: 800;
                    font-size: 10px;
                    color: #0f3b4f;
                    margin: 12px 0;
                }
                .sign-lines {
                    display: flex;
                    justify-content: space-between;
                    margin-top: 25px;
                    font-size: 7px;
                    font-weight: 800;
                    text-transform: uppercase;
                    color: #3b6a88;
                    border-top: 1px dashed #cbd5e1;
                    padding-top: 14px;
                }
                .small-meta {
                    font-size: 7px;
                    color: #4b6a7c;
                    margin-top: 3px;
                }
                @media (max-width: 768px) {
                    .variable-guide-header {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 12px;
                    }
                    .variable-guide-grid,
                    .footer-two {
                        grid-template-columns: 1fr;
                    }
                    .goods-header,
                    .goods-row {
                        grid-template-columns: 1fr 1fr;
                    }
                }
            </style>

            <div class="variable-guide-outer">
                <div class="variable-guide-demo">
                    <div class="variable-guide-header">
                        <div class="variable-guide-brand">
                            <small>Sea Freight & Logistics</small>
                        </div>
                        <div class="variable-guide-docno">
                            <div class="doc-title">B/L</div>
                            <p class="doc-sub">MULTIMODAL TRANSPORT DOCUMENT · ORIGINAL</p>
                            <div class="doc-badge">OCEAN EXPORT</div>
                        </div>
                    </div>

                    <div class="variable-guide-grid">
                        <div class="variable-guide-box">
                            <div class="variable-guide-box-title">Shipper / Exporter</div>
                            <div class="variable-guide-tag-row">
                                <span class="variable-guide-tag-pill">{{shipper_name}}</span>
                                <span class="variable-guide-tag-pill">{{shipper_address}}</span>
                            </div>
                        </div>
                        <div class="variable-guide-box">
                            <div class="variable-guide-box-title">B/L No. / Booking</div>
                            <div class="variable-guide-tag-row">
                                <span class="variable-guide-tag-pill">{{tracking_number}}</span>
                                <span class="variable-guide-tag-pill">{{booking_no}}</span>
                            </div>
                        </div>
                    </div>

                    <div class="variable-guide-grid">
                        <div class="variable-guide-box">
                            <div class="variable-guide-box-title">Consignee</div>
                            <div class="variable-guide-tag-row">
                                <span class="variable-guide-tag-pill">{{consignee_name}}</span>
                                <span class="variable-guide-tag-pill">{{consignee_address}}</span>
                            </div>
                        </div>
                        <div class="variable-guide-box">
                            <div class="variable-guide-box-title">Forwarding Agent</div>
                            <div class="variable-guide-tag-row">
                                <span class="variable-guide-tag-pill">{{forwarding_agent}}</span>
                                <span class="variable-guide-tag-pill">{{forwarding_agent_address}}</span>
                            </div>
                        </div>
                    </div>

                    <div class="variable-guide-grid">
                        <div class="variable-guide-box">
                            <div class="variable-guide-box-title">Notify Party</div>
                            <div class="variable-guide-tag-row">
                                <span class="variable-guide-tag-pill">{{notify_name}}</span>
                                <span class="variable-guide-tag-pill">{{notify_address}}</span>
                            </div>
                        </div>
                        <div class="variable-guide-box">
                            <div class="variable-guide-box-title">Place of Receipt</div>
                            <div class="variable-guide-tag-row">
                                <span class="variable-guide-tag-pill">{{place_receipt}}</span>
                            </div>
                        </div>
                    </div>

                    <div class="variable-guide-full">
                        <div class="variable-guide-box-title">Destination Agent / Correspondent</div>
                        <div class="variable-guide-tag-row">
                            <span class="variable-guide-tag-pill">{{destination_agent}}</span>
                            <span class="variable-guide-tag-pill">{{destination_agent_address}}</span>
                            <span class="variable-guide-tag-pill">{{destination_agent_phone}}</span>
                            <span class="variable-guide-tag-pill">{{destination_agent_email}}</span>
                        </div>
                    </div>

                    <div class="variable-guide-grid">
                        <div class="variable-guide-box">
                            <div class="variable-guide-box-title">Port of Loading</div>
                            <div class="variable-guide-tag-row">
                                <span class="variable-guide-tag-pill">{{pol}}</span>
                                <span class="variable-guide-tag-pill">{{etd}}</span>
                            </div>
                        </div>
                        <div class="variable-guide-box">
                            <div class="variable-guide-box-title">Pre-carriage</div>
                            <div class="variable-guide-tag-row">
                                <span class="variable-guide-tag-pill">{{pre_carriage}}</span>
                            </div>
                        </div>
                    </div>

                    <div class="variable-guide-grid">
                        <div class="variable-guide-box">
                            <div class="variable-guide-box-title">Vessel / Voyage</div>
                            <div class="variable-guide-tag-row">
                                <span class="variable-guide-tag-pill">{{vessel_name}}</span>
                                <span class="variable-guide-tag-pill">{{voyage_no}}</span>
                            </div>
                        </div>
                        <div class="variable-guide-box">
                            <div class="variable-guide-box-title">Port of Discharge</div>
                            <div class="variable-guide-tag-row">
                                <span class="variable-guide-tag-pill">{{pod}}</span>
                                <span class="variable-guide-tag-pill">{{eta}}</span>
                            </div>
                        </div>
                    </div>

                    <div class="cargo-title">PARTICULARS DECLARED BY SHIPPER – CARGO DESCRIPTION</div>

                    <div class="goods-header">
                        <div class="goods-col">MARKS & NOS.</div>
                        <div class="goods-col">CONTAINER / SEAL NO.</div>
                        <div class="goods-col">NO. OF PKGS</div>
                        <div class="goods-col">DESCRIPTION OF GOODS</div>
                        <div class="goods-col">GROSS WEIGHT</div>
                        <div class="goods-col">MEASUREMENT</div>
                    </div>
                    <div class="goods-row">
                        <div class="goods-cell">{{marks_numbers}}</div>
                        <div class="goods-cell"><span class="variable-tag-box">{{container_no}}</span><br><span class="small-meta">{{seal_no}}</span></div>
                        <div class="goods-cell">{{num_packages}}</div>
                        <div class="goods-cell">
                            <strong>{{goods_description}}</strong><br>
                            {{commodity_type}}<br>
                            <span class="variable-tag-box">{{hs_code}}</span>
                        </div>
                        <div class="goods-cell"><span style="font-weight:700; font-size:11px;">{{gross_weight}}</span></div>
                        <div class="goods-cell">{{volume_cbm}}</div>
                    </div>

                    <div class="items-wrap">
                        <div class="items-title">PACKAGE ITEMIZATION BREAKDOWN</div>
                        <table class="item-table">
                            <thead>
                                <tr>
                                    <th>&#123;&#123;&#35;items&#125;&#125; item_description &#123;&#123;/items&#125;&#125;</th>
                                    <th>&#123;&#123;&#35;items&#125;&#125; quantity &#123;&#123;/items&#125;&#125;</th>
                                    <th>&#123;&#123;&#35;items&#125;&#125; weight &#123;&#123;/items&#125;&#125;</th>
                                    <th>&#123;&#123;&#35;items&#125;&#125; cbm &#123;&#123;/items&#125;&#125;</th>
                                    <th>&#123;&#123;&#35;items&#125;&#125; unit_value &#123;&#123;/items&#125;&#125;</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{item_description}}</td>
                                    <td>{{quantity}}</td>
                                    <td>{{weight}}</td>
                                    <td>{{cbm}}</td>
                                    <td>{{unit_value}}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td><strong>{{total_packages}}</strong></td>
                                    <td><strong>{{total_weight}}</strong></td>
                                    <td><strong>{{total_cbm}}</strong></td>
                                    <td><strong>{{currency}}</strong></td>
                                    <td><strong>{{total_value}}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="variable-guide-full" style="border-top: 1px solid #e2e8f0; background: #fff;">
                        <div class="variable-guide-box-title">Special Instructions / Declaration</div>
                        <div class="variable-guide-tag-row">
                            <span class="variable-guide-tag-pill">{{special_instructions}}</span>
                            <span class="variable-guide-tag-pill">{{declaration}}</span>
                        </div>
                    </div>

                    <div class="footer-two">
                        <div class="terms-box">
                            <strong>RECEIVED for shipment</strong> in apparent good order and condition, except as otherwise noted. The total number of packages or units stated herein. This Bill of Lading is issued subject to the Carrier's standard terms and conditions. The merchant agrees that this shipment is subject to the Hague-Visby Rules where applicable.
                        </div>
                        <div class="sign-box">
                            <div class="sign-place">PLACE & DATE OF ISSUANCE: <strong>{{place_of_issue}} – {{date_of_issue}}</strong></div>
                            <div class="sign-place">ON BOARD DATE: <strong>{{etd}}</strong></div>
                            <div class="carrier-name-footer">{{carrier_name}}<br>As Carrier</div>
                            <div class="small-meta">Signatory: {{signatory}}</div>
                            <div class="sign-lines">
                                <span>{{designation}}</span>
                                <span>Electronically Verified</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="variable-guide-list">
                    ${visualMarkup}
                </div>
            </div>
        `);

        const modalElement = document.getElementById('templateVariablesModal');
        if (modalElement && window.bootstrap && bootstrap.Modal) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            return;
        }

        $('#templateVariablesModal').modal('show');
    },

    previewDocument: function () {
        const htmlTemplate = $('#html_template').val();
        if (!htmlTemplate) {
            alert('Please enter HTML template first');
            return;
        }

        const sampleData = {
            tracking_number: 'SAMPLE123456',
            shipper_name: 'Sample Shipper',
            consignee_name: 'Sample Consignee',
            vessel_name: 'Sample Vessel',
            pol: 'PORT OF LOADING',
            pod: 'PORT OF DISCHARGE',
            gross_weight: '1000 KG',
            goods_description: 'Sample Goods Description',
            color_primary: '#006a6a',
            color_secondary: '#d45f00',
            carrier_name: 'Sample Carrier',
            company_name: 'Sample Company',
            items: [
                { description: 'Item 1', quantity: 10, weight: 100 },
                { description: 'Item 2', quantity: 20, weight: 200 }
            ]
        };

        const preview = this.renderTemplate(htmlTemplate, sampleData);
        $('#previewFrame').attr('srcdoc', preview);
        $('#previewModal').modal('show');
    },

    renderTemplate: function (template, data) {
        let result = template;

        Object.keys(data).forEach(key => {
            if (!Array.isArray(data[key])) {
                result = result.replace(new RegExp('{{' + key + '}}', 'g'), data[key]);
            }
        });

        if (data.items && data.items.length > 0) {
            const itemsPattern = /{{#items}}([\s\S]*?){{\/items}}/;
            const itemsContent = result.match(itemsPattern);
            if (itemsContent) {
                let itemsHtml = '';
                data.items.forEach(item => {
                    let itemHtml = itemsContent[1];
                    itemHtml = itemHtml.replace(/{{description}}/g, item.description || '');
                    itemHtml = itemHtml.replace(/{{quantity}}/g, item.quantity || '');
                    itemHtml = itemHtml.replace(/{{weight}}/g, item.weight || '');
                    itemsHtml += itemHtml;
                });
                result = result.replace(itemsPattern, itemsHtml);
            }
        }

        return result;
    }
};

window.DocumentsPage = DocumentsPage;
window.openTemplateInfoModal = function () {
    if (typeof DocumentsPage !== 'undefined' && DocumentsPage && typeof DocumentsPage.showTemplateInfo === 'function') {
        DocumentsPage.showTemplateInfo();
        return;
    }

    console.warn('DocumentsPage is not ready');
};

// Initialization is handled by app.js when navigating to the documents page
