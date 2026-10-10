<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$currentUserRole = $_SESSION['role'] ?? 'admin';
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="user-role" content="<?php echo htmlspecialchars($currentUserRole); ?>">

<link rel="stylesheet" href="../assets/css/shipments-table.css">
<link rel="stylesheet" href="../assets/css/rgsl-bl-modal.css">
<div id="shipments-page" class="page-content" data-user-role="<?php echo htmlspecialchars($currentUserRole); ?>">

  <!-- ══════ LIST VIEW ══════ -->
  <div id="shipments-list-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Manage Shipments</h2>
      <?php if (($currentUserRole ?? 'admin') !== 'agent'): ?>
        <button class="btn btn-primary" id="open-add-shipment-btn">
          <i class="fas fa-plus"></i> Add New Shipment
        </button>
      <?php endif; ?>
    </div>

    <!-- Shipments List -->
    <div id="shipments-container"></div>
  </div>

  <!-- View Shipment summary stays a modal (read-only, not the create/edit flow) -->
  <div class="modal fade" id="viewShipmentModal" tabindex="-1" aria-labelledby="viewShipmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewShipmentModalLabel">Shipment Overview</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="view-shipment-body">
          <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 mb-0">Loading shipment details...</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ══════ FORM VIEW (shown inline instead of a modal) ══════ -->
  <div id="shipments-form-view" style="display:none;">
    <div class="card shadow-sm">
      <div class="modal-header d-flex justify-content-between align-items-center">
        <button type="button" class="btn btn-sm" id="btn-back-to-list" style="color:#fff; border-color:rgba(255,255,255,.5);">
          <i class="bi bi-arrow-left"></i> Back
        </button>
        <h5 class="modal-title mb-0" id="blModalLabel">
          <i class="bi bi-file-earmark-text me-2"></i>
          Royal Gulf Shipping &amp; Logistics LLC — Bill of Lading
        </h5>
        <div style="width:70px;"><!-- spacer to balance the Back button --></div>
      </div>
      <div class="card-body px-4 pt-3 pb-2">
        <form id="bl-form" novalidate>
          <!-- ── Tabs ── -->
          <ul class="nav nav-tabs mb-0" id="blTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-parties"
                type="button"><i class="bi bi-people"></i>Parties</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-voyage"
                type="button"><i class="bi bi-ship"></i>Voyage</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-cargo"
                type="button"><i class="bi bi-box-seam"></i>Cargo</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-freight"
                type="button"><i class="bi bi-cash-coin"></i>Freight</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tracking"
                type="button"><i class="bi bi-geo-alt"></i>Tracking & BL</button></li>
          </ul>
          <div class="tab-content border border-top-0 rounded-bottom p-3">
            <!-- ══════ TAB 1: PARTIES ══════ -->
            <div class="tab-pane fade show active" id="tab-parties">
              <div class="sec-head"><i class="bi bi-building"></i>Shipper / Exporter</div>
              <div class="row g-3">
                <div class="col-md-2">
                  <label class="form-label">Shipper Name <span class="req">*</span></label>
                  <input type="text" class="form-control" name="shipper_name" required
                    placeholder="Company or person name">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Shipper Reference No</label>
                  <input type="text" class="form-control" name="shipper_ref" placeholder="SH-REF-001">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Shipper Email <span class="req">*</span></label>
                  <input type="email" class="form-control" name="shipper_email" required
                    placeholder="shipper@example.com">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Shipper Phone</label>
                  <input type="tel" class="form-control" name="shipper_phone" placeholder="+92-XXX-XXXXXXX">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Shipper Address</label>
                  <textarea class="form-control" name="shipper_address" rows="2"
                    placeholder="Full address with city, country"></textarea>
                </div>
              </div>

              <div class="sec-head"><i class="bi bi-person-check"></i>Consignee/Receiver</div>
              <div class="row g-3">
                <div class="col-md-2">
                  <label class="form-label">Consignee Name <span class="req">*</span></label>
                  <input type="text" class="form-control" name="consignee_name" required
                    placeholder="Company or person name">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Consignee Phone</label>
                  <input type="tel" class="form-control" name="consignee_phone" placeholder="+971-XXX-XXXXXXX">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Consignee Email</label>
                  <input type="email" class="form-control" name="consignee_email" placeholder="consignee@example.com">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Consignee Tax / VAT ID</label>
                  <input type="text" class="form-control" name="consignee_tax_id" placeholder="Tax or company reg no">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Consignee Address <span class="req">*</span></label>
                  <textarea class="form-control" name="consignee_address" rows="2" required
                    placeholder="Full delivery address"></textarea>
                </div>
              </div>



              <div class="sec-head"><i class="bi bi-bell"></i>Notify Party</div>
              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="same-as-consignee-toggle"
                  name="notify_same_as_consignee_toggle">
                <label class="form-check-label" for="same-as-consignee-toggle">
                  <strong>Notify Party same as consignee</strong>
                </label>
                <div class="form-text">When enabled, the notify party fields are hidden and the notify party details
                  will mirror the consignee details.</div>
              </div>
              <div id="notify-sync-banner" class="alert alert-info py-2 mb-3 d-none">
                <i class="bi bi-info-circle me-2"></i>Notify party details are currently using the consignee
                information.
              </div>
              <div id="notify-fields-wrapper" class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Notify Party Name</label>
                  <input type="text" class="form-control" name="notify_name" placeholder="Agent or bank name">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Notify Party Phone</label>
                  <input type="tel" class="form-control" name="notify_phone">
                </div>
                <div class="col-12">
                  <label class="form-label">Notify Party Address</label>
                  <textarea class="form-control" name="notify_address" rows="2"
                    placeholder="Notify party full address"></textarea>
                </div>
              </div>

            </div><!-- /tab-parties -->

            <!-- ══════ TAB 2: VOYAGE ══════ -->
            <div class="tab-pane fade" id="tab-voyage">

              <div class="sec-head"><i class="bi bi-anchor"></i>Vessel & Voyage Information</div>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Vessel Name</label>
                  <input type="text" class="form-control" name="vessel_name" placeholder="MV Sea Pioneer">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Pre-carriage by</label>
                  <input type="text" class="form-control" name="pre_carriage" placeholder="Pre-carriage vessel">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Originals Release At</label>
                  <input type="text" class="form-control" name="originals_release_at" placeholder="DUBAI">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Freight Payable At</label>
                  <input type="text" class="form-control" name="freight_payable_at" placeholder="DUBAI">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Place of Delivery</label>
                  <input type="text" class="form-control" name="place_of_delivery" placeholder="Final delivery place">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Mode Load Area</label>
                  <input type="text" class="form-control" name="mode_load_area" placeholder="Loading area">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Mode Discharge Area</label>
                  <input type="text" class="form-control" name="mode_discharge_area" placeholder="Discharge area">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Voyage Number</label>
                  <input type="text" class="form-control" name="voyage_no" placeholder="VOY-2025-001">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Flag (Country)</label>
                  <input type="text" class="form-control" name="vessel_flag" placeholder="Panama">
                </div>
                <div class="col-md-4">
                  <label class="form-label">IMO Number</label>
                  <input type="text" class="form-control" name="imo_number" placeholder="IMO 1234567">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Carrier / Shipping Line <span class="req">*</span></label>
                  <select class="form-select" name="carrier" required>
                    <option value="">-- Select --</option>
                    <option>Royal Gulf Shipping LLC</option>
                    <option>Maersk Line</option>
                    <option>MSC</option>
                    <option>CMA CGM</option>
                    <option>Evergreen</option>
                    <option>COSCO</option>
                    <option>DHL</option>
                    <option>FedEx</option>
                    <option>USPS</option>
                    <option>Emirates SkyCargo</option>
                    <option>Other</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Carrier Reference No</label>
                  <input type="text" class="form-control" name="carrier_reference_no" placeholder="CRR-2025-001">
                </div>
              </div>

              <div class="sec-head"><i class="bi bi-map"></i>Ports & Dates</div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Port of Loading (POL) <span class="req">*</span></label>
                  <input type="text" class="form-control" name="pol" required placeholder="Karachi, Pakistan">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Port of Discharge (POD) <span class="req">*</span></label>
                  <input type="text" class="form-control" name="pod" required placeholder="Dubai, UAE">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Place of Receipt</label>
                  <input type="text" class="form-control" name="place_receipt" placeholder="Inland origin point">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Final Destination</label>
                  <input type="text" class="form-control" name="final_destination" placeholder="End delivery point">
                </div>
                <div class="col-md-3">
                  <label class="form-label">ETD (Departure Date)</label>
                  <input type="date" class="form-control" name="etd">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Departure Time</label>
                  <input type="time" class="form-control" name="departure_time">
                </div>
                <div class="col-md-3">
                  <label class="form-label">ETA (Arrival Date)</label>
                  <input type="date" class="form-control" name="eta">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Pickup Date</label>
                  <input type="date" class="form-control" name="pickup_date">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Mode of Transport <span class="req">*</span></label>
                  <select class="form-select" name="mode" required>
                    <option value="">-- Select --</option>
                    <option>Sea Transport (FCL)</option>
                    <option>Sea Transport (LCL)</option>
                    <option>Air Freight</option>
                    <option>Land / Road Freight</option>
                    <option>Multimodal</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Type of Shipment</label>
                  <select class="form-select" name="type_of_shipment">
                    <option value="">-- Select --</option>
                    <option>FCL (Full Container Load)</option>
                    <option>LCL (Less Container Load)</option>
                    <option>Air Freight</option>
                    <option>Break Bulk</option>
                    <option>RoRo</option>
                    <option>Truckload</option>
                    <option>Van Move</option>
                  </select>
                </div>
              </div>

              <div class="sec-head"><i class="bi bi-box2-heart"></i>Container Details</div>
              <div id="container-details-wrapper">
                <div class="border rounded p-3 mb-3 container-entry" data-container-index="0">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Container 1</h6>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-container-btn">Remove</button>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label">Container Number</label>
                      <input type="text" class="form-control" name="container_no[]" placeholder="MSCU1234567">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Container Size / Type</label>
                      <select class="form-select" name="container_type[]">
                        <option value="">-- Select --</option>
                        <option>20' GP (General Purpose)</option>
                        <option>40' GP</option>
                        <option>40' HC (High Cube)</option>
                        <option>20' Reefer</option>
                        <option>40' Reefer</option>
                        <option>20' Open Top</option>
                        <option>40' Open Top</option>
                        <option>Flat Rack</option>
                        <option>N/A</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Seal Number</label>
                      <input type="text" class="form-control" name="seal_no[]" placeholder="Seal ID">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Origin Country</label>
                      <select class="form-select" name="origin[]">
                        <option value="">-- Select --</option>
                        <option>Pakistan</option>
                        <option>Afghanistan</option>
                        <option>India</option>
                        <option>Bangladesh</option>
                        <option>UAE</option>
                        <option>Saudi Arabia</option>
                        <option>Qatar</option>
                        <option>Kuwait</option>
                        <option>China</option>
                        <option>USA</option>
                        <option>UK</option>
                        <option>Germany</option>
                        <option>Other</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Destination Country</label>
                      <select class="form-select" name="destination[]">
                        <option value="">-- Select --</option>
                        <option>UAE</option>
                        <option>Saudi Arabia</option>
                        <option>Qatar</option>
                        <option>Kuwait</option>
                        <option>Pakistan</option>
                        <option>Afghanistan</option>
                        <option>India</option>
                        <option>Bangladesh</option>
                        <option>China</option>
                        <option>USA</option>
                        <option>UK</option>
                        <option>Germany</option>
                        <option>Other</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-primary btn-sm" id="add-container-btn">
                  <i class="bi bi-plus-circle"></i> Add Another Container
                </button>
                <small class="text-muted">You can add multiple containers for one shipment.</small>
              </div>

            </div><!-- /tab-voyage -->

            <!-- ══════ TAB 3: CARGO ══════ -->
            <div class="tab-pane fade" id="tab-cargo">

              <div class="sec-head"><i class="bi bi-boxes"></i>Cargo Description</div>
              <div class="form-text text-muted mb-3">Tip: Add containers first (Voyage tab) — cargo accordions will be created automatically for each container.</div>
              <div id="cargo-accordion-wrapper" class="accordion mb-3" role="tablist">
                <!-- Accordion items are generated dynamically based on container rows -->
              </div>
              <div class="sec-head"><i class="bi bi-exclamation-triangle"></i>Dangerous Goods (if applicable)</div>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">UN Number</label>
                  <input type="text" class="form-control" name="un_number" placeholder="UN 1234">
                </div>
                <div class="col-md-4">
                  <label class="form-label">DG Class</label>
                  <input type="text" class="form-control" name="dg_class" placeholder="Class 3">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Packing Group</label>
                  <select class="form-select" name="packing_group">
                    <option value="">-- N/A --</option>
                    <option>PG I (High Danger)</option>
                    <option>PG II (Medium)</option>
                    <option>PG III (Low)</option>
                  </select>
                </div>
              </div>

              <div class="sec-head"><i class="bi bi-table"></i>Package Itemization</div>
              <div class="table-responsive">
                <table class="pkg-table">
                  <thead>
                    <tr>
                      <th class="pkg-table-col-num">#</th>
                      <th>Description</th>
                      <th class="pkg-table-col-qty">Qty</th>
                      <th class="pkg-table-col-weight">Weight (kg)</th>
                      <th class="pkg-table-col-cbm">CBM</th>
                      <th class="pkg-table-col-value">Unit Value (USD)</th>
                      <th class="pkg-table-col-action"></th>
                    </tr>
                  </thead>
                  <tbody id="pkg-body">
                    <tr>
                      <td class="text-center text-muted">1</td>
                      <td><input type="text" placeholder="Item description" name="item_desc[]"></td>
                      <td><input type="number" min="1" value="1" name="item_qty[]"></td>
                      <td><input type="number" step="0.01" name="item_weight[]"></td>
                      <td><input type="number" step="0.001" name="item_cbm[]"></td>
                      <td><input type="number" step="0.01" name="item_value[]"></td>
                      <td><button type="button" class="btn-rm" onclick="removeRow(this)" title="Remove">×</button></td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <button type="button" class="btn-add-row mt-2" onclick="addRow()">
                <i class="bi bi-plus-circle me-1"></i>Add Item Row
              </button>

            </div><!-- /tab-cargo -->

            <!-- ══════ TAB 4: FREIGHT ══════ -->
            <div class="tab-pane fade" id="tab-freight">

              <div class="sec-head"><i class="bi bi-currency-dollar"></i>Freight Charges</div>
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">Freight Terms</label>
                  <select class="form-select" name="freight_terms">
                    <option value="">-- Select --</option>
                    <option>Prepaid</option>
                    <option>Collect</option>
                    <option>Third Party</option>
                    <option>As Arranged</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Payment Mode</label>
                  <select class="form-select" name="payment_mode">
                    <option value="">-- Select --</option>
                    <option>Bank Transfer (TT)</option>
                    <option>Letter of Credit (LC)</option>
                    <option>Cash</option>
                    <option>Cheque</option>
                    <option>Online Payment</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Currency</label>
                  <select class="form-select" name="currency">
                    <option value="USD">USD – US Dollar</option>
                    <option value="AED">AED – UAE Dirham</option>
                    <option value="PKR">PKR – Pakistani Rupee</option>
                    <option value="EUR">EUR – Euro</option>
                    <option value="GBP">GBP – British Pound</option>
                    <option value="SAR">SAR – Saudi Riyal</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Tariff Item No</label>
                  <input type="text" class="form-control" name="tariff_item_no" placeholder="FREIGHT PREPAID">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Ocean Freight</label>
                  <input type="number" class="form-control freight-input" name="ocean_freight" step="0.01"
                    placeholder="0.00">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Local Charges</label>
                  <input type="number" class="form-control freight-input" name="local_charges" step="0.01"
                    placeholder="0.00">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Documentation Fee</label>
                  <input type="number" class="form-control freight-input" name="doc_fee" step="0.01" placeholder="0.00">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Other Charges</label>
                  <input type="number" class="form-control freight-input" name="other_charges" step="0.01"
                    placeholder="0.00">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Total Freight</label>
                  <input type="number" class="form-control fw-bold" name="total_freight" id="total_freight" step="0.01"
                    placeholder="Auto-calculated" readonly>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Amount in Words</label>
                  <input type="text" class="form-control" name="freight_in_words"
                    placeholder="e.g. US Dollars One Thousand Only">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Destination Charges</label>
                  <textarea class="form-control" name="destination_charges" rows="2"
                    placeholder="All destination charges on consignee's account"></textarea>
                </div>
              </div>

              <div class="sec-head"><i class="bi bi-file-earmark-ruled"></i>Trade & Customs</div>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Incoterms</label>
                  <select class="form-select" name="incoterms">
                    <option value="">-- Select --</option>
                    <option>EXW – Ex Works</option>
                    <option>FOB – Free on Board</option>
                    <option>CIF – Cost Insurance Freight</option>
                    <option>CFR – Cost and Freight</option>
                    <option>DAP – Delivered at Place</option>
                    <option>DDP – Delivered Duty Paid</option>
                    <option>FCA – Free Carrier</option>
                    <option>CPT</option>
                    <option>CIP</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">LC Number</label>
                  <input type="text" class="form-control" name="lc_number" placeholder="Letter of Credit No">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Invoice Number</label>
                  <input type="text" class="form-control" name="invoice_no" placeholder="Commercial Invoice No">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Packing List No</label>
                  <input type="text" class="form-control" name="packing_list_no">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Certificate of Origin No</label>
                  <input type="text" class="form-control" name="co_no">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Customs Declaration No</label>
                  <input type="text" class="form-control" name="customs_no">
                </div>
              </div>

              <div class="sec-head"><i class="bi bi-shield-check"></i>Insurance</div>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Insured By</label>
                  <select class="form-select" name="insured_by">
                    <option value="">-- Select --</option>
                    <option>Shipper</option>
                    <option>Consignee</option>
                    <option>Carrier</option>
                    <option>Not Insured</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Insurance Policy No</label>
                  <input type="text" class="form-control" name="insurance_policy">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Insurance Value (USD)</label>
                  <input type="number" class="form-control" name="insurance_value" step="0.01">
                </div>
              </div>

            </div><!-- /tab-freight -->

            <!-- ══════ TAB 5: TRACKING & BL ══════ -->
            <div class="tab-pane fade" id="tab-tracking">

              <div class="sec-head"><i class="bi bi-pin-map"></i>Tracking & Delivery</div>
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">Tracking Number</label>
                  <input type="text" class="form-control" name="tracking_number" id="tracking_number"
                    placeholder="Auto Generated" readonly>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Current Location</label>
                  <input type="text" class="form-control" name="current_location" placeholder="e.g. Karachi Port">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Shipment Status</label>
                  <select class="form-select" name="status">
                    <option value="pending">Pending</option>
                    <option value="booking_confirmed">Booking Confirmed</option>
                    <option value="picked_up">Picked Up</option>
                    <option value="cargo_received">Cargo Received</option>
                    <option value="customs_clearance">Customs Clearance</option>
                    <option value="vessel_loaded">Vessel Loaded</option>
                    <option value="in_transit">In Transit</option>
                    <option value="port_of_discharge">Port of Discharge</option>
                    <option value="out_for_delivery">Out for Delivery</option>
                    <option value="delivered">Delivered</option>
                    <option value="on_hold">On Hold</option>
                    <option value="exception">Exception</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">No. of Original BL</label>
                  <select class="form-select" name="no_of_original_bl">
                    <option value="3">3 Originals</option>
                    <option value="2">2 Originals</option>
                    <option value="1">1 Original</option>
                    <option value="0">None (Seaway Bill)</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Estimated Delivery</label>
                  <input type="date" class="form-control" name="estimated_delivery">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Actual Delivery</label>
                  <input type="date" class="form-control" name="actual_delivery">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Issue Date</label>
                  <input type="date" class="form-control" name="issue_date" placeholder="JEBEL ALI">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Pickup Time</label>
                  <input type="time" class="form-control" name="pickup_time">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Issue Place</label>
                  <input type="text" class="form-control" name="issue_place" placeholder="JEBEL ALI">
                </div>

                <div class="col-md-4">
                  <label class="form-label">Booking No</label>
                  <input type="text" class="form-control" name="booking_no" placeholder="BK-2026-00123">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Export Reference</label>
                  <input type="text" class="form-control" name="export_reference" placeholder="EXP-REF-001">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Forwarding Agent</label>
                  <input type="text" class="form-control" name="forwarding_agent" placeholder="Forwarding agent name">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Destination Agent</label>
                  <input type="text" class="form-control" name="destination_agent" placeholder="Destination agent name">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Destination Agent Email</label>
                  <input type="email" class="form-control" name="destination_agent_email" placeholder="destination.agent@example.com">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Destination Agent Address</label>
                  <textarea class="form-control" name="destination_agent_address" rows="2"
                    placeholder="Destination agent full address"></textarea>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Destination Agent Phone</label>
                  <input type="text" class="form-control" name="destination_agent_phone" placeholder="+XXX-XXX-XXXXXXX">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Company</label>
                  <select class="form-select" name="company" id="company-select">
                    <option value="">Loading companies...</option>
                  </select>
                </div>
              </div>

              <div class="sec-head"><i class="bi bi-sticky"></i>Remarks & Special Instructions</div>
              <div class="row g-3">
                <div class="col-6">
                  <label class="form-label">Special Instructions</label>
                  <textarea class="form-control" name="special_instructions" rows="2"
                    placeholder="Temperature requirements, fragile handling, delivery instructions..."></textarea>
                </div>
                <div class="col-6">
                  <label class="form-label">Internal Comments <small class="text-muted">(not printed on
                      B/L)</small></label>
                  <textarea class="form-control" name="comments" rows="2" placeholder="Internal notes"></textarea>
                </div>
              </div>

              <div class="sec-head"><i class="bi bi-pen"></i>B/L Issuance & Clauses</div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">B/L Type</label>
                  <select class="form-select" name="bl_type">
                    <option>Original BL (3 Originals)</option>
                    <option>Seaway Bill (Non-negotiable)</option>
                    <option>Express Release</option>
                    <option>Telex Release</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Number of Originals</label>
                  <select class="form-select" name="num_originals">
                    <option value="3">3 Originals</option>
                    <option value="2">2 Originals</option>
                    <option value="1">1 Original</option>
                    <option value="0">None (Seaway Bill)</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Place of Issue</label>
                  <input type="text" class="form-control" name="place_of_issue" placeholder="Karachi, Pakistan">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Date of Issue</label>
                  <input type="date" class="form-control" name="date_of_issue">
                </div>
                <div class="col-12">
                  <label class="form-label">Shipper's Declaration</label>
                  <textarea class="form-control" name="declaration" rows="2"
                    placeholder="Shipper's goods description declaration..."></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Authorized Signatory</label>
                  <input type="text" class="form-control" name="signatory" placeholder="Authorized person name">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Designation</label>
                  <input type="text" class="form-control" name="designation" placeholder="Manager / Director">
                </div>
              </div>

            </div><!-- /tab-tracking -->

          </div><!-- /tab-content -->
        </form>
      </div><!-- /card-body -->

      <!-- Footer -->
      <div class="modal-footer d-flex justify-content-end gap-2" id="bl-form-footer">
        <button type="button" class="btn btn-secondary btn-sm" id="btn-cancel-form">
          Cancel
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-reset-form">
          Reset
        </button>
        <button type="button" class="btn btn-success btn-sm" id="create-shipment-btn">
          Create Shipment
        </button>
      </div>

    </div><!-- /card -->
  </div><!-- /shipments-form-view -->
</div><!-- /shipments-page -->

<!-- <script src="../assets/js/shipments.js"></script> -->
<script src="../assets/js/shipments-page.js"></script>