<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUserRole = $_SESSION['role'] ?? 'admin';
?>
<div class="container-fluid py-3" data-user-role="<?php echo htmlspecialchars($currentUserRole); ?>">
  <div class="arrival-page-toolbar">
    <div>
      <h2 class="mb-1">Arrival Notices</h2>
      <p class="text-muted mb-0">Create arrival notices for shipments, save drafts, and send emails to consignees.</p>
    </div>
    <button class="btn arrival-page-action-btn" id="add-arrival-notice-btn" data-bs-toggle="modal" data-bs-target="#arrivalNoticeModal">
      <i class="fas fa-plus"></i> New Arrival Notice
    </button>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr class="arrival-page-table-head">
              <th>Tracking No</th>
              <th>Consignee</th>
              <th>Consignee Email</th>
              <th>Arrival Date</th>
              <th>Free Days</th>
              <th>Status</th>
              <th>Sent Count</th>
              <th>Action</th>
              <th>Sent At</th>
            </tr>
          </thead>
          <tbody id="arrival-notices-table-body">
            <tr>
              <td colspan="9" class="text-muted">Loading arrival notices...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="arrivalNoticeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header arrival-page-modal-header">
        <h5 class="modal-title">Compose Arrival Notice</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="arrival-notice-form">
          <input type="hidden" id="arrival-notice-id" value="">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label arrival-page-form-label">Shipment <span class="text-danger">*</span></label>
              <select id="arrival-shipment" class="form-select" required></select>
            </div>
            <div class="col-md-6">
              <label class="form-label arrival-page-form-label">Tracking Number</label>
              <input type="text" id="arrival-tracking-number" class="form-control" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label arrival-page-form-label">Consignee Name</label>
              <input type="text" id="arrival-consignee-name" class="form-control" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label arrival-page-form-label">Consignee Email <span class="text-danger">*</span></label>
              <input type="email" id="arrival-consignee-email" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label arrival-page-form-label">Arrival Date <span class="text-danger">*</span></label>
              <input type="date" id="arrival-date" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label arrival-page-form-label">Free Days Allowed</label>
              <input type="number" id="arrival-free-days" class="form-control" min="0" value="0">
            </div>
            <div class="col-12">
              <label class="form-label arrival-page-form-label">Notice Message</label>
              <textarea id="arrival-notice-message" class="form-control" rows="5" placeholder="Dear Consignee, your shipment has arrived at the destination port..."></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" id="save-arrival-draft">Save as Draft</button>
        <button type="button" class="btn arrival-page-primary-btn" id="send-arrival-mail">Send Arrival Mail</button>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/arrival-notices.js"></script>