<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="invoice-page-main">
        <div class="topbar">
            <h1 class="left" id="pageTitle">Create New Invoice</h1>
            <div>
                <button class="btn-white me-2" onclick="window.open(((window.App && typeof window.App.getBasePath === 'function') ? window.App.getBasePath() : '') + '/api/invoices.php?action=print&id=' + (window._pageParams && window._pageParams.id ? window._pageParams.id : ''), '_blank')">Print Invoice</button>
                <button class="btn-white" onclick="App.loadPage('invoice-list')">Back to Invoices</button>
            </div>
        </div>

        <div class="invoice-page-content">
            <!-- TWO COLUMN LAYOUT -->
            <div class="invoice-layout">

                <!-- LEFT COLUMN: Shipment Selector + Add Charge + Tax/Discount/Notes -->
                <div class="invoice-left">

                    <!-- Shipment Selector -->
                    <div class="left-panel">
                        <h3>Select Shipment</h3>
                        <div class="field">
                            <label>Shipment <span class="req">*</span></label>
                            <select id="globalShipmentSelect">
                                <option value="">-- Select Shipment --</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Consignee</label>
                            <input type="text" id="displayConsignee" disabled>
                        </div>
                        <div class="field">
                            <label>Arrival Date</label>
                            <input type="date" id="displayArrival" disabled>
                        </div>
                    </div>

                    <!-- Add Additional Charge -->
                    <div class="left-panel">
                        <h3>+ Add Additional Charge</h3>
                        <div class="hint invoice-hint">Add any extra charges beyond shipment charges.</div>
                        <div class="field">
                            <label>Description <span class="req">*</span></label>
                            <input type="text" id="adhocDesc" placeholder="e.g. Admin Fee, Inspection">
                        </div>
                        <div class="field">
                            <label>Category</label>
                            <select id="adhocCategory">
                                <option value="additional_charge">Additional Charge</option>
                                <option value="local_charge">Local Handling</option>
                                <option value="additional_charge">Documentation</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Amount (PKR) <span class="req">*</span></label>
                            <input type="number" id="adhocAmount" placeholder="e.g. 150" step="0.01">
                        </div>
                        <button class="btn btn-teal" id="addAdhocBtn">+ Add to Invoice</button>
                        <div class="hint invoice-note-spacer">Local Handling and Documentation are treated as invoice charge lines.</div>
                    </div>

                    <!-- Tax Rate, Discount, Notes -->
                    <div class="left-panel">
                        <h3>Invoice Adjustments</h3>
                        <div class="field">
                            <label>Tax Rate (%)</label>
                            <input type="number" id="taxRateInput" placeholder="e.g. 10" step="0.01" value="0">
                            <div class="hint">Tax percentage applied to subtotal plus additional charges.</div>
                        </div>
                        <div class="field">
                            <label>Discount (PKR)</label>
                            <input type="number" id="globalDiscountInput" placeholder="e.g. 100" step="0.01" value="0">
                            <div class="hint">Flat discount off the subtotal.</div>
                        </div>
                        <div class="field">
                            <label>Notes</label>
                            <textarea id="invoiceNotes"
                                placeholder="Internal notes or payment instructions...">Payment due within 7 days. Please include Invoice # with payment.</textarea>
                        </div>
                        <button class="btn btn-orange" id="applyAdjustmentsBtn">Apply Adjustments</button>
                        <button class="btn btn-success invoice-save-btn" id="saveInvoiceBtn">
                            <i class="fas fa-save"></i> Save Invoice
                        </button>
                    </div>

                </div>

                <!-- RIGHT COLUMN: Invoice Card -->
                <div class="invoice-right">
                    <div class="invoice-wrapper" id="invoiceCard">

                        <!-- Header -->
                        <div class="invoice-header">
                            <div class="left">
                                <h2>INVOICE</h2>
                                <div class="inv-number" id="invoiceNumber"># INV-XXXX-XXXX · Date: <span id="invoiceDate"></span></div>
                            </div>
                            <div class="right">
                                <strong>Indus Magna Shipping</strong><br>
                                Karachi, Pakistan · NTN<br>
                                info@indusmagnashipping.com · +92 312 3650805
                            </div>
                        </div>

                        <!-- Parties -->
                        <div class="party-grid">
                            <div class="party-box">
                                <h4>Bill To · Consignee</h4>
                                <div class="name" id="partyConsignee">-</div>
                                <div class="detail" id="partyDetails">-</div>
                            </div>
                            <div class="party-box">
                                <h4>Destination Agent</h4>
                                <div class="name" id="partyDestinationAgent">-</div>
                                <div class="detail" id="partyDestinationDetails">-</div>
                            </div>
                        </div>

                        <!-- Shipment meta -->
                        <div class="shipment-meta" id="shipmentMeta">
                            <div class="meta-item">
                                <span class="lbl">Tracking No</span>
                                <span class="val" id="metaTracking">-</span>
                            </div>
                            <div class="meta-item">
                                <span class="lbl">Container</span>
                                <span class="val" id="metaContainer">-</span>
                            </div>
                            <div class="meta-item">
                                <span class="lbl">Vessel / Voyage</span>
                                <span class="val" id="metaVessel">-</span>
                            </div>
                            <div class="meta-item">
                                <span class="lbl">Arrival Karachi</span>
                                <span class="val" id="metaArrival">-</span>
                            </div>
                            <div class="meta-item">
                                <span class="lbl">Free Days Expiry</span>
                                <span class="val" id="metaFreeDaysExpiry">-</span>
                            </div>
                        </div>

                        <!-- Charges breakdown table -->
                        <table class="charge-table" id="chargeTable">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Qty / Days</th>
                                    <th>Rate (PKR)</th>
                                    <th>Amount (PKR)</th>
                                    <th class="delete-cell"></th>
                                </tr>
                            </thead>
                            <tbody id="chargeTableBody">
                                <tr>
                                    <td colspan="6" class="invoice-empty-state">Select a shipment to view charges</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Summary box -->
                        <div class="summary-box">
                            <div class="summary-inner" id="summaryBox">
                                <div class="row"><span style="font-weight: 800;">Subtotal (Charges)</span> <span id="summarySubtotal">PKR 0</span></div>
                                <div class="row"><span style="font-weight: 800;">Tax</span> <span id="summaryTax">PKR 0</span></div>
                                <div class="row discount invoice-discount-row" id="discountRow"><span style="font-weight: 800;">Discount</span> <span
                                        id="summaryDiscount">- PKR 0</span></div>
                                <div class="row total"><span style="font-weight: 800;">Total Due</span> <span id="summaryTotal">PKR 0</span></div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="invoice-notes" id="invoiceNotesDisplay">
                            <strong>Notes:</strong> Payment due within 7 days. Please include Invoice # with payment.
                        </div>

                    </div><!-- end invoice-wrapper -->
                </div><!-- end invoice-right -->

            </div><!-- end invoice-layout -->

        </div><!-- end content -->
    </div><!-- end main -->

<script src="../assets/js/invoice-create.js"></script>
