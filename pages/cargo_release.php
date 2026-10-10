<div class="container-fluid py-3 full-height">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1">Cargo Release</h2>
            <p class="text-muted mb-0">Manage shipment release status, lock cargo, and update release actions in real time.</p>
        </div>
        <div class="d-flex gap-2">
            <button id="cargoReleaseRefreshBtn" class="btn btn-outline-secondary">
                <i class="fas fa-rotate-right"></i> Refresh
            </button>
        </div>
    </div>

    <div id="cargoReleaseAlert" class="mb-3"></div>
    <div id="cargoReleaseContent">
        <div class="card">
            <div class="card-body text-center py-5 text-muted">Loading cargo release records...</div>
        </div>
    </div>
</div>

<script src="../assets/js/cargo-release.js"></script>