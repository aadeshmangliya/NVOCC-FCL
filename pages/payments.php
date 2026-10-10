<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$invoiceId = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;
?>
<div class="container-fluid py-3" data-invoice-id="<?php echo (int)$invoiceId; ?>">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1">Payments</h2>
            <p class="text-muted mb-0">Track invoice payments, record new payments, and view payment history securely.</p>
        </div>
        <div class="d-flex gap-2">
            <button id="paymentsRefreshBtn" class="btn btn-outline-secondary">
                <i class="fas fa-rotate-right"></i> Refresh
            </button>
            <button id="paymentsBackBtn" class="btn btn-outline-primary d-none">
                <i class="fas fa-arrow-left"></i> Back to List
            </button>
        </div>
    </div>

    <div id="paymentsAlert" class="mb-3"></div>
    <div id="paymentsContent">
        <div class="card">
            <div class="card-body text-center py-5 text-muted">Loading payment records...</div>
        </div>
    </div>
</div>

<script src="../assets/js/payments.js"></script>
