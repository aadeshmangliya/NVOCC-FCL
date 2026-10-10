<?php
// Companies Management Page (Loads via AJAX)
?>
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="fas fa-building me-2"></i>Companies Management</h2>
        <button class="btn btn-primary" id="addCompanyBtn">
            <i class="fas fa-plus"></i> Add New Company
        </button>
    </div>

    <!-- Companies List -->
    <div id="companies-list-container">
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3">Loading companies...</p>
        </div>
    </div>
</div>

<!-- Add/Edit Company Modal -->
<div class="modal fade" id="companyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="companyModalTitle">Add New Company</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="companyForm">
                    <input type="hidden" name="id" id="company_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Company Key <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="company_key" id="company_key" required
                                placeholder="e.g., royal_gulf">
                            <small class="text-muted">Use lowercase with underscores only</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="company_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Display Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="display_name" id="display_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Website</label>
                            <input type="text" class="form-control" name="website" id="website" placeholder="www.example.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Carrier Name</label>
                            <input type="text" class="form-control" name="carrier_name" id="carrier_name"
                                placeholder="For B/L display">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Company Address</label>
                            <input type="text" class="form-control" name="address" id="company_address"
                                placeholder="For Delivery Order header">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" id="company_phone">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-control" name="email" id="company_email">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">National Identity No.</label>
                            <input type="text" class="form-control" name="national_identity_no" id="company_national_id">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Company Logo</label>
                            <input type="file" class="form-control" id="company_logo" accept="image/*">
                            <small class="text-muted">Supported formats: JPG, PNG, GIF (Max 2MB)</small>
                            <div id="logoPreview" class="company-logo-preview d-none">
                                <img id="logoImg" src="" alt="Logo Preview" class="company-logo-image">
                                <button type="button" class="btn btn-sm btn-danger ms-2" id="removeLogo">Remove Logo</button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Primary Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color company-color-input" name="color_primary" id="color_primary" value="#006a6a">
                                <input type="text" class="form-control" id="color_primary_text" placeholder="#006a6a">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Secondary Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color company-color-input" name="color_secondary" id="color_secondary" value="#d45f00">
                                <input type="text" class="form-control" id="color_secondary_text" placeholder="#d45f00">
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked value="1">
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCompanyBtn">Save Company</button>
            </div>
        </div>
    </div>
</div>

<!-- Documents Modal -->
<div class="modal fade" id="documentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentsModalTitle">
                    <i class="fas fa-file-alt me-2"></i>Company Documents
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="company-documents-list">
                    <div class="text-center p-5">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <p class="mt-3 text-muted">Loading documents...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="addDocumentToCompanyBtn">
                    <i class="fas fa-plus"></i> Manage Documents
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Document Preview Modal (for companies) -->
<div class="modal fade" id="documentPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalTitle">Document Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="companyDocumentPreview" class="company-preview-iframe"></iframe>
            </div>
        </div>
    </div>
</div>