<?php
// Tracking Page with Dynamic Company/Document Dropdown
?>

<div class="page-content">
    <h2 class="mb-4">Track Your Shipment</h2>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Enter Tracking Number</h5>
                </div>
                <div class="card-body">
                    <form id="tracking-form">
                        <div class="input-group">
                            <input type="text" class="form-control form-control-lg" id="tracking-number" 
                                   placeholder="Enter tracking number (e.g., 202506-0001)" required>
                            <button type="submit" class="btn btn-primary btn-lg">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Dynamic Company & Documents Dropdown -->
    <div class="row mt-4">
        <div class="col-md-8">
            <div id="company-documents-container"></div>
        </div>
    </div>
    <!-- Results -->
    <div id="tracking-results" class="mt-4"></div>
    <div id="tracking-history" class="mt-4"></div>
</div>
<script src="assets/js/document_dropdown.js"></script>
<script src="assets/js/pages-tracking.js"></script>