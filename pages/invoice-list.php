<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUserRole = $_SESSION['role'] ?? 'admin';
?>
<div class="container-fluid py-3" data-user-role="<?php echo htmlspecialchars($currentUserRole); ?>">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1">Invoices</h2>
            <p class="text-muted mb-0">Manage shipment invoices, charges, and payments.</p>
        </div>
        <button onclick="App.loadPage('invoice-create')" class="btn invoice-list-primary-btn">
            <i class="fas fa-plus"></i> New Invoice
        </button>
    </div>

    <!-- Invoices Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="invoiceTable" class="table table-hover align-middle no-datatable">
                    <thead>
                        <tr class="invoice-list-table-head">
                            <th>Invoice #</th>
                            <th>Tracking No</th>
                            <th>Consignee</th>
                            <th>Created By</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="invoiceTableBody">
                        <tr>
                            <td colspan="9" class="invoice-list-empty">Loading invoices...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/invoice-list.js"></script>