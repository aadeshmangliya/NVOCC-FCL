<?php
if (session_status() === PHP_SESSION_NONE) {session_start();}
?>
<div class="page-content" id="shipments-charges-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Shipment Charges</h2>
            <p class="text-muted mb-0">Manage detention, demurrage and local charges.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#detentionModal">
                <i class="fas fa-plus"></i> Create Detention/Demurrage
            </button>
            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#localChargeModal">
                <i class="fas fa-plus"></i> Add Local Charge
            </button>
        </div>
    </div>

    <div id="charges-alert"></div>

    <div class="card mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="chargesTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active charges-nav-btn" id="detention-tab" data-bs-toggle="tab" data-bs-target="#detention-pane" type="button" role="tab" aria-controls="detention-pane" aria-selected="true">Detention / Demurrage Records</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link charges-nav-btn" id="local-tab" data-bs-toggle="tab" data-bs-target="#local-pane" type="button" role="tab" aria-controls="local-pane" aria-selected="false">Local Charges</button>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content" id="chargesTabContent">
                <!-- Detention Tab -->
                <div class="tab-pane fade show active" id="detention-pane" role="tabpanel" aria-labelledby="detention-tab">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Tracking Number</th>
                                    <th>Description</th>
                                    <th>Expiry</th>
                                    <th>Delay Days</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="detention-body">
                                <tr><td colspan="7" class="text-muted text-center py-3">No records yet.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Local Charges Tab -->
                <div class="tab-pane fade" id="local-pane" role="tabpanel" aria-labelledby="local-tab">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Tracking Number</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="local-charge-body">
                                <tr><td colspan="5" class="text-muted text-center py-3">No records yet.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detention / Demurrage Modal -->
<div class="modal fade" id="detentionModal" tabindex="-1" aria-labelledby="detentionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detentionModalLabel">Create Detention / Demurrage Charge</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Select Shipment <span class="text-danger">*</span></label>
                        <select id="detention-shipment-select" class="form-select shipment-selector">
                            <option value="">Loading shipments...</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Consignee</label>
                        <input type="text" id="consignee-name" class="form-control" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Consignee Email</label>
                        <input type="email" id="consignee-email" class="form-control" readonly>
                    </div>
                    
                    <div class="col-12 mt-4">
                        <h6 class="border-bottom pb-2">Arrival & Free Days</h6>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Arrival Date</label>
                        <input type="date" id="arrival-date" class="form-control" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Free Days</label>
                        <input type="text" id="free-days-allowed" class="form-control" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="text" id="expiry-date" class="form-control" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <input type="text" id="days-left" class="form-control" readonly>
                    </div>
                    
                    <div class="col-12 mt-4">
                        <h6 class="border-bottom pb-2">Charge Details</h6>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Charge Type</label>
                        <select id="detention-type" class="form-select">
                            <option>Detention</option>
                            <option>Demurrage</option>
                            <option>Detention + Demurrage</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Delay Days</label>
                        <input type="number" id="detention-delay-days" class="form-control" value="0" min="0" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rate per Day (PKR)</label>
                        <input type="number" id="detention-rate" class="form-control" value="200" min="0" step="0.01">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Total Amount</label>
                        <input type="text" id="detention-amount" class="form-control" readonly value="PKR 0.00">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Remarks</label>
                        <textarea id="detention-remarks" class="form-control" rows="2" placeholder="Optional remarks"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-secondary" id="reset-detention-btn">Reset</button>
                <button type="button" class="btn btn-primary" id="create-detention-btn">Create Charge</button>
            </div>
        </div>
    </div>
</div>

<!-- Local Charge Modal -->
<div class="modal fade" id="localChargeModal" tabindex="-1" aria-labelledby="localChargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="localChargeModalLabel">Add Local Charge</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Select Shipment <span class="text-danger">*</span></label>
                        <select id="local-shipment-select" class="form-select shipment-selector">
                            <option value="">Loading shipments...</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Charge Category</label>
                        <select id="local-category" class="form-select">
                            <option>Port Charges</option>
                            <option>Documentation</option>
                            <option>Transport</option>
                            <option>Inspection</option>
                            <option>Customs Clearance</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select id="local-status" class="form-select">
                            <option value="unpaid">Unpaid</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Charge Description</label>
                        <input type="text" id="local-description" class="form-control" placeholder="e.g. Terminal Handling Charges (THC)">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Amount (PKR)</label>
                        <input type="number" id="local-amount" class="form-control" min="0" step="0.01">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-secondary" id="reset-local-btn">Reset</button>
                <button type="button" class="btn btn-primary" id="create-local-btn">Create Charge</button>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/shipments-charges.js?v=<?php echo time(); ?>"></script>