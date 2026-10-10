<?php
// Documents Management Page (Loads via AJAX)
?>
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="fas fa-file-alt me-2"></i>Document Templates</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-info" id="templateInfoHeaderBtn" type="button" onclick="openTemplateInfoModal();">
                <i class="fas fa-info-circle"></i> Info
            </button>
            <button class="btn btn-primary" id="addDocumentBtn" type="button">
                <i class="fas fa-plus"></i> Add Document Template
            </button>
        </div>
    </div>
    <!-- Documents List -->
    <div id="documents-list-container">
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3">Loading documents...</p>
        </div>
    </div>
</div>

<!-- Add/Edit Document Modal -->
<div class="modal fade" id="documentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentModalTitle">Add Document Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="documentForm">
                    <input type="hidden" name="id" id="doc_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Document Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="document_type" id="document_type" required>
                                <option value="">Select Type</option>
                                <option value="bill_of_lading">Bill of Lading</option>
                                <option value="delivery_order">Delivery Order</option>
                                <option value="igm">Import General Manifest (IGM)</option>
                                <option value="invoice">Commercial Invoice</option>
                                <option value="packing_list">Packing List</option>
                                <option value="certificate_of_origin">Certificate of Origin</option>
                                <option value="airway_bill">Airway Bill</option>
                                <option value="custom">Custom Document</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Template Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="template_name" id="template_name" required>
                        </div>
                        <div class="col-12 mb-3">
                            <p class="text-muted"><small><i class="fas fa-info-circle"></i> Note: Assign companies to this document later from the Companies section.</small></p>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">HTML Template</label>
                            <textarea class="form-control document-template-editor" name="html_template" id="html_template" rows="15"></textarea>
                            <small class="text-muted">
                                Use <code>&#123;&#123;variable_name&#125;&#125;</code> for dynamic data.
                                Use <code>&#123;&#123;&#35;items&#125;&#125;...&#123;&#123;/items&#125;&#125;</code> for looping through items.
                            </small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active_doc" id="is_active_doc" checked value="1">
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info" id="previewDocBtn">Preview</button>
                <button type="button" class="btn btn-primary" id="saveDocumentBtn">Save Template</button>
            </div>
        </div>
    </div>
</div>

<!-- Template Variables Info Modal -->
<div class="modal fade" id="templateVariablesModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Document Variables & Rules</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Syntax:</strong>
                    <code>&#123;&#123;variable_name&#125;&#125;</code> for single values,
                    <code>&#123;&#123;&#35;items&#125;&#125; ... &#123;&#123;/items&#125;&#125;</code> for repeating items,
                    <code>&#123;&#123;&#35;if variable_name&#125;&#125; ... &#123;&#123;/if&#125;&#125;</code> for conditional blocks.
                </div>
                <div id="templateVariablesContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Document Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="previewFrame" class="document-preview-iframe"></iframe>
            </div>
        </div>
    </div>
</div>