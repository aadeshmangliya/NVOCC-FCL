<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="mb-1">Email Logs</h2>
      <p class="text-muted mb-0">Sent arrival notice emails with shipment and agent details.</p>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Agent</th>
              <th>Tracking No</th>
              <th>Consignee</th>
              <th>Consignee Email</th>
              <th>Sent At</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="email-logs-table-body">
            <tr>
              <td colspan="6" class="text-muted">Loading email logs...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/email-logs.js"></script>
