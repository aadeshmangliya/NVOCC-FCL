/* Shipments Page JS - Bill of Lading Form - WITH EDIT */

const ShipmentsPage = {
    editingStatusRowId: null,
    editingShipmentId: null, // Track which shipment is being edited
    rowCount: 1,
    companyOptionsCache: null,
    companyOptionsPromise: null,

    init: function () {
        if (!document.getElementById('shipments-container')) return;

        this.companyOptionsCache = null;
        this.companyOptionsPromise = null;

        this.loadCompanyOptions();
        this.loadShipments();
        this.setupBLForm();
        this.setupPackageTable();
        this.setupContainerManager();
        this.initializeFirstTab();
        this.setupInlineFormToggle();
    },

    // ==================== INLINE FORM VIEW (replaces the old Bootstrap modal) ====================

    showFormView: function () {
        const listView = document.getElementById('shipments-list-view');
        const formView = document.getElementById('shipments-form-view');
        if (listView) listView.style.display = 'none';
        if (formView) formView.style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });

        this.activateFirstTab();
        this.updateNavButtons(0);

        try {
            if (!this.editingShipmentId) {
                this.renderCargoAccordions();
            }
        } catch (e) { /* ignore */ }
    },

    hideFormView: function () {
        const listView = document.getElementById('shipments-list-view');
        const formView = document.getElementById('shipments-form-view');
        if (formView) formView.style.display = 'none';
        if (listView) listView.style.display = 'block';

        this.resetModalState();
    },

    setupInlineFormToggle: function () {
        const self = this;

        const openBtn = document.getElementById('open-add-shipment-btn');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                self.editingShipmentId = null;
                self.resetForm();
                self.showFormView();
            });
        }

        const backBtn = document.getElementById('btn-back-to-list');
        if (backBtn) {
            backBtn.addEventListener('click', () => {
                if (!confirm('Go back to the shipments list? Unsaved changes will be lost.')) return;
                self.hideFormView();
            });
        }

        const cancelBtn = document.getElementById('btn-cancel-form');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                if (!confirm('Cancel and go back to the shipments list? Unsaved changes will be lost.')) return;
                self.hideFormView();
            });
        }
    },

    setupNotifyPartySync: function () {
        const toggle = document.getElementById('same-as-consignee-toggle');
        if (!toggle) return;

        const wrapper = document.getElementById('notify-fields-wrapper');
        const banner = document.getElementById('notify-sync-banner');

        const consigneeFields = {
            notify_name: document.querySelector('[name="consignee_name"]'),
            notify_phone: document.querySelector('[name="consignee_phone"]'),
            notify_address: document.querySelector('[name="consignee_address"]')
        };

        const notifyFields = {
            notify_name: document.querySelector('[name="notify_name"]'),
            notify_phone: document.querySelector('[name="notify_phone"]'),
            notify_address: document.querySelector('[name="notify_address"]')
        };

        const syncFromConsignee = () => {
            if (!toggle.checked) return;

            Object.entries(consigneeFields).forEach(([notifyKey, sourceField]) => {
                const targetField = notifyFields[notifyKey];
                if (sourceField && targetField) {
                    targetField.value = sourceField.value || '';
                }
            });
        };

        const applyToggleState = () => {
            if (toggle.checked) {
                syncFromConsignee();
                if (wrapper) wrapper.classList.add('d-none');
                if (banner) banner.style.display = 'block';
            } else {
                if (wrapper) wrapper.classList.remove('d-none');
                if (banner) banner.style.display = 'none';
            }
        };

        toggle.addEventListener('change', applyToggleState);

        Object.values(consigneeFields).forEach(field => {
            if (field) {
                field.addEventListener('input', () => {
                    if (toggle.checked) {
                        syncFromConsignee();
                    }
                });
                field.addEventListener('change', () => {
                    if (toggle.checked) {
                        syncFromConsignee();
                    }
                });
            }
        });

        applyToggleState();
    },

    getBasePath: function () {
        let path = window.location.pathname || '/';
        const adminIndex = path.indexOf('/admin/');

        if (adminIndex !== -1) {
            return path.substring(0, adminIndex);
        }

        const normalized = path.replace(/\/+$/, '');
        const lastSlash = normalized.lastIndexOf('/');

        if (lastSlash > 0) {
            const lastSegment = normalized.substring(lastSlash + 1);
            if (lastSegment.includes('.')) {
                return normalized.substring(0, lastSlash);
            }
        }

        return normalized || '';
    },

    getCurrentUserRole: function () {
        const roleSources = [
            window.currentUserRole,
            window.userRole,
            document.querySelector('meta[name="user-role"]')?.getAttribute('content'),
            document.getElementById('shipments-page')?.getAttribute('data-user-role'),
            document.querySelector('[data-user-role]')?.getAttribute('data-user-role'),
            document.body?.getAttribute('data-user-role')
        ];

        const role = roleSources
            .find(value => value !== undefined && value !== null && String(value).trim() !== '')
            ?.toString()
            .trim()
            .toLowerCase() || '';

        return role;
    },

    isStaffUser: function () {
        const role = this.getCurrentUserRole();
        return role === 'agent' || role === 'staff_user' || role.includes('agent');
    },

    getCompaniesData: function (companyFilter = '') {
        if (!companyFilter && this.companyOptionsCache !== null) {
            return Promise.resolve(this.companyOptionsCache);
        }

        if (!companyFilter && this.companyOptionsPromise) {
            return this.companyOptionsPromise;
        }

        const apiUrl = this.getApiUrl('/api/get_companies.php');
        const separator = apiUrl.includes('?') ? '&' : '?';
        const queryParts = [`_${Date.now()}`];

        if (companyFilter) {
            queryParts.push(`company=${encodeURIComponent(companyFilter)}`);
        }

        const bustUrl = `${apiUrl}${separator}${queryParts.join('&')}`;

        this.companyOptionsPromise = $.ajax({
            url: bustUrl,
            method: 'GET',
            dataType: 'json',
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            beforeSend: function (xhr) {
                const bearer = document.querySelector('meta[name="api-bearer-token"]')?.getAttribute('content');
                if (bearer) xhr.setRequestHeader('Authorization', 'Bearer ' + bearer);
            }
        }).then((res) => {
            const data = (res && res.success && Array.isArray(res.data)) ? res.data : [];
            if (!companyFilter) {
                this.companyOptionsCache = data;
            }
            return data;
        }).catch(() => {
            if (!companyFilter) {
                this.companyOptionsCache = [];
            }
            return [];
        });

        this.companyOptionsPromise.always(() => {
            this.companyOptionsPromise = null;
        });

        return this.companyOptionsPromise;
    },

    loadCompanyOptions: function (selectedValue = '') {
        const companySelect = document.querySelector('select[name="company"]');
        if (!companySelect) return;

        const currentValue = selectedValue || companySelect.value || '';
        companySelect.innerHTML = '';

        const loadingOption = document.createElement('option');
        loadingOption.value = '';
        loadingOption.textContent = 'Loading companies...';
        companySelect.appendChild(loadingOption);

        this.getCompaniesData().then((companies) => {
            companySelect.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = '-- Select Company --';
            companySelect.appendChild(placeholder);

            if (Array.isArray(companies) && companies.length > 0) {
                companies.forEach(company => {
                    const option = document.createElement('option');
                    const value = company.id != null ? String(company.id) : '';
                    const label = company.display_name || company.name || company.company_key || 'Unnamed Company';
                    option.value = value;
                    option.textContent = label;
                    if (
                        String(currentValue) === String(company.id) ||
                        String(currentValue) === String(company.company_key) ||
                        String(currentValue) === String(company.name)
                    ) {
                        option.selected = true;
                    }
                    companySelect.appendChild(option);
                });
            } else {
                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = 'No companies found';
                emptyOption.disabled = true;
                companySelect.appendChild(emptyOption);
            }
        }).catch(() => {
            companySelect.innerHTML = '';
            const errorOption = document.createElement('option');
            errorOption.value = '';
            errorOption.textContent = 'Failed to load companies';
            companySelect.appendChild(errorOption);
        });
    },

    // ==================== SHIPMENTS LIST ====================

    loadShipments: function () {
        const container = document.getElementById('shipments-container');
        if (!container) return;

        container.innerHTML = `
            <div class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-3">Loading shipments...</p>
            </div>`;

        App.api.get('shipments')
            .then(response => {
                if (response && response.success) {
                    this.displayShipments(response.data || []);
                } else {
                    container.innerHTML = `<div class="alert alert-warning">No shipments found</div>`;
                }
            })
            .catch(error => {
                container.innerHTML = `<div class="alert alert-danger">Failed to load shipments.</div>`;
            });
    },

    displayShipments: function (shipments) {
        const container = document.getElementById('shipments-container');
        if (!container) return;

        if (!shipments || shipments.length === 0) {
            container.innerHTML = `<div class="alert alert-info">No shipments found. Click "Add New Shipment" to create one.</div>`;
            return;
        }

        let html = `<div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Tracking Number</th>
                    <th>Shipper</th>
                    <th>Consignee</th>
                    <th>POL → POD</th>
                    <th>Weight (kg)</th>
                    <th>Status</th>
                    <th style="min-width: 180px;">Actions</th>
                </tr>
            </thead>
            <tbody>`;

        for (let i = 0; i < shipments.length; i++) {
            const s = shipments[i];
            const isEditing = this.editingStatusRowId === s.id;

            let statusHtml = '';
            if (isEditing) {
                statusHtml = `
                <select id="status-select-${s.id}" class="form-select form-select-sm">
                    <option value="pending" ${s.status === 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="booking_confirmed" ${s.status === 'booking_confirmed' ? 'selected' : ''}>Booking Confirmed</option>
                    <option value="cargo_received" ${s.status === 'cargo_received' ? 'selected' : ''}>Cargo Received</option>
                    <option value="customs_clearance" ${s.status === 'customs_clearance' ? 'selected' : ''}>Customs Clearance</option>
                    <option value="vessel_loaded" ${s.status === 'vessel_loaded' ? 'selected' : ''}>Vessel Loaded</option>
                    <option value="in_transit" ${s.status === 'in_transit' ? 'selected' : ''}>In Transit</option>
                    <option value="port_of_discharge" ${s.status === 'port_of_discharge' ? 'selected' : ''}>Port of Discharge</option>
                    <option value="out_for_delivery" ${s.status === 'out_for_delivery' ? 'selected' : ''}>Out for Delivery</option>
                    <option value="delivered" ${s.status === 'delivered' ? 'selected' : ''}>Delivered</option>
                    <option value="on_hold" ${s.status === 'on_hold' ? 'selected' : ''}>On Hold</option>
                    <option value="cancelled" ${s.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                </select>`;
            } else {
                const statusColors = {
                    'delivered': 'success',
                    'cancelled': 'danger',
                    'pending': 'warning',
                    'on_hold': 'secondary',
                    'in_transit': 'info',
                    'out_for_delivery': 'primary'
                };
                const color = statusColors[s.status] || 'info';
                statusHtml = `<span class="badge bg-${color}">${(s.status || 'pending').replace(/_/g, ' ')}</span>`;
            }

            let actionsHtml = '';

            if (isEditing) {
                actionsHtml = `
                <button class="btn btn-sm btn-success me-1" onclick="ShipmentsPage.saveStatus(${s.id})">
                    <i class="fas fa-save"></i>
                </button>
                <button class="btn btn-sm btn-secondary" onclick="ShipmentsPage.cancelStatusEdit()">
                    <i class="fas fa-times"></i>
                </button>`;
            } else {
                const isStaff = this.isStaffUser();
                actionsHtml = `
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-info" onclick="ShipmentsPage.viewShipment(${s.id})" title="View Shipment">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-info" onclick="ShipmentsPage.editStatus(${s.id})" title="Change Status">
                        <i class="fas fa-tag"></i>
                    </button>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Documents">
                            <i class="fas fa-file-alt"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-start" id="doc-dropdown-${s.id}" style="position: absolute; z-index: 9999; transform: none;">
                            <li class="dropdown-header"><i class="fas fa-building"></i> Loading companies...</li>
                        </ul>
                    </div>
                    ${!isStaff ? `<button class="btn btn-primary" onclick="ShipmentsPage.editShipment(${s.id})" title="Edit Shipment">
                        <i class="fas fa-edit"></i>
                    </button>` : ''}
                    ${!isStaff ? `<button class="btn btn-danger" onclick="ShipmentsPage.deleteShipment(${s.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>` : ''}
                </div>`;
            }

            const route = (s.pol && s.pod) ? `${s.pol} → ${s.pod}` : 'N/A';

            html += `
            <tr id="shipment-row-${s.id}" ${isEditing ? 'class="table-warning"' : ''}>
                <td><strong>${this.escapeHtml(s.tracking_number) || 'N/A'}</strong></td>
                <td>${this.escapeHtml(s.shipper_name) || 'N/A'}</td>
                <td>${this.escapeHtml(s.consignee_name) || 'N/A'}</td>
                <td>${this.escapeHtml(route)}</td>
                <td>${s.gross_weight || 'N/A'}</td>
                <td id="status-cell-${s.id}">${statusHtml}</td>
                <td id="actions-cell-${s.id}" style="white-space: nowrap;">${actionsHtml}</td>
             </tr>
        `;
        }

        html += `
            </tbody>
        </table>
    </div>`;

        container.innerHTML = html;

        // Load companies for each shipment's dropdown
        const self = this;
        shipments.forEach(s => {
            if (this.editingStatusRowId !== s.id) {
                setTimeout(() => {
                    self.loadCompaniesForShipment(s.id, s.tracking_number, s.company);
                }, 200);
            }
        });
    },

    // Add escapeHtml helper function if not exists
    escapeHtml: function (text) {
        if (!text) return '';
        return String(text).replace(/[&<>]/g, function (m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    },

    // Fix dropdown positioning to prevent overflow
    adjustDropdownPosition: function (dropdownElement) {
        if (!dropdownElement || !dropdownElement.length) return;

        const dropdown = dropdownElement[0];
        const dropdownBtn = dropdownElement.prev('button');

        if (!dropdownBtn.length) return;

        // First, remove any problematic inset styles that Bootstrap might have applied
        dropdown.style.inset = 'auto';

        const btnRect = dropdownBtn[0].getBoundingClientRect();

        // Position dropdown below or above button using fixed positioning
        const topPos = btnRect.bottom + window.scrollY + 5;
        const leftPos = btnRect.left + window.scrollX;

        dropdownElement.css({
            'position': 'fixed',
            'top': topPos + 'px',
            'left': leftPos + 'px',
            'right': 'auto',
            'bottom': 'auto',
            'z-index': '9999',
            'transform': 'none',
            'display': 'block',
            'inset': 'auto',
            'width': 'auto',
            'max-width': '300px'
        });

        // Check if dropdown goes below viewport, if so, show above button
        const dropdownRect = dropdown.getBoundingClientRect();
        if (dropdownRect.bottom > window.innerHeight - 10) {
            const newTopPos = btnRect.top + window.scrollY - dropdownRect.height - 5;
            dropdownElement.css('top', Math.max(10, newTopPos) + 'px');
        }
    },

    // Load companies for a specific shipment dropdown
    // Load companies for a specific shipment dropdown
    loadCompaniesForShipment: function (shipmentId, trackingNumber, shipmentCompany = '') {
        const dropdown = $(`#doc-dropdown-${shipmentId}`);
        if (!dropdown.length) return;

        const shouldFilterForStaff = this.isStaffUser() && !!shipmentCompany;
        const filterValue = shouldFilterForStaff ? shipmentCompany : '';

        this.getCompaniesData(filterValue).then((companies) => {
            if (Array.isArray(companies) && companies.length > 0) {
                let html = '<li class="dropdown-header"><i class="fas fa-building"></i> Select Company</li>';
                html += '<li><hr class="dropdown-divider"></li>';

                companies.forEach(company => {
                    html += `
                    <li style="position: relative;">
                        <a class="dropdown-item company-submenu" href="#" 
                           data-company-key="${company.company_key}" 
                           data-company-name="${company.display_name}" 
                           data-shipment-id="${shipmentId}" 
                           data-tracking-number="${trackingNumber}" 
                           style="color: ${company.color_primary}">
                            <i class="fas fa-building me-2"></i> ${company.display_name}
                            <i class="fas fa-chevron-right float-end mt-1"></i>
                        </a>
                        <ul class="dropdown-menu submenu" id="doc-submenu-${shipmentId}-${company.company_key}"
                            style="display: none; position: absolute; right: 100%; top: 0; min-width: 200px; z-index: 10000; transform: none;">
                            <li class="dropdown-header">Loading documents...</li>
                        </ul>
                    </li>
                `;
                });
                dropdown.html(html);

                // Add positioning listener to dropdown button
                const dropdownBtn = dropdown.prev('button');
                if (dropdownBtn.length) {
                    // Create a Bootstrap dropdown instance to handle show event properly
                    const bsDropdown = new bootstrap.Dropdown(dropdownBtn[0]);

                    dropdownBtn.on('show.bs.dropdown', function () {
                        // Reset any problematic inset values immediately
                        dropdown[0].style.inset = 'auto';
                        dropdown[0].style.position = 'absolute';

                        setTimeout(() => {
                            ShipmentsPage.adjustDropdownPosition(dropdown);
                        }, 10);
                    }).on('hide.bs.dropdown', function () {
                        // Reset positioning when dropdown closes
                        dropdown.css({
                            'position': 'absolute',
                            'top': 'auto',
                            'left': 'auto',
                            'display': 'none',
                            'inset': 'auto'
                        });
                    });
                }

                // Add event listeners for company items
                dropdown.find('.company-submenu').off('click').on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const companyKey = $(this).data('company-key');
                    const companyName = $(this).data('company-name');
                    const shipId = $(this).data('shipment-id');
                    const trackNum = $(this).data('tracking-number');

                    // Hide all other submenus in this dropdown
                    dropdown.find('.submenu').hide();

                    // Show this company's submenu
                    const $submenu = $(`#doc-submenu-${shipId}-${companyKey}`);
                    $submenu.css({
                        'display': 'block',
                        'position': 'absolute',
                        'right': '100%',
                        'left': 'auto',
                        'top': '0',
                        'z-index': '10000',
                        'transform': 'none'
                    });

                    // Load documents if not already loaded
                    if ($submenu.find('.dropdown-header').text() === 'Loading documents...') {
                        ShipmentsPage.loadDocumentsForShipment(shipId, trackNum, companyKey, companyName, $(this));
                    }
                });
            } else {
                dropdown.html('<li class="dropdown-header text-danger">No companies found</li>');
            }
        }).catch(() => {
            dropdown.html('<li class="dropdown-header text-danger">Failed to load companies</li>');
        });
    },

    // Load documents for a specific company and shipment
    loadDocumentsForShipment: function (shipmentId, trackingNumber, companyKey, companyName, $trigger) {
        // Create submenu element
        const $parentLi = $trigger.closest('li');
        let $submenu = $(`#doc-submenu-${shipmentId}-${companyKey}`);

        if (!$submenu.length) {
            $parentLi.append(`<ul class="dropdown-menu submenu" id="doc-submenu-${shipmentId}-${companyKey}" style="position: absolute; left: 100%; top: 0; z-index: 10000; transform: none;"><li class="dropdown-header">Loading documents...</li></ul>`);
            $submenu = $(`#doc-submenu-${shipmentId}-${companyKey}`);
        }

        $submenu.show();
        $submenu.css({
            'z-index': '10000',
            'transform': 'none',
            'display': 'block'
        });

        const apiUrl = this.getApiUrl('/api/get_company_documents.php?company_key=' + companyKey);

        $.ajax({
            url: apiUrl,
            method: 'GET',
            dataType: 'json',
            beforeSend: function (xhr) {
                const bearer = document.querySelector('meta[name="api-bearer-token"]')?.getAttribute('content');
                if (bearer) xhr.setRequestHeader('Authorization', 'Bearer ' + bearer);
            },
            success: function (res) {
                if (res.success && res.data && res.data.length > 0) {
                    let html = '<li class="dropdown-header"><i class="fas fa-file-alt"></i> ' + companyName + ' Documents</li>';
                    html += '<li><hr class="dropdown-divider"></li>';

                    res.data.forEach(doc => {
                        html += `
                        <li>
                            <a class="dropdown-item document-generate" href="#" data-company-key="${companyKey}" data-document-type="${doc.document_type}" data-tracking-number="${trackingNumber}" data-template-name="${doc.template_name}">
                                <i class="fas fa-file-pdf text-danger me-2"></i> ${doc.template_name}
                                ${doc.is_default ? '<span class="badge bg-primary ms-2">Default</span>' : ''}
                            </a>
                        </li>
                    `;
                    });
                    $submenu.html(html);

                    // Add event listeners for document generation
                    $submenu.find('.document-generate').off('click').on('click', function (e) {
                        e.preventDefault();
                        const company = $(this).data('company-key');
                        const docType = $(this).data('document-type');
                        const tracking = $(this).data('tracking-number');
                        const templateName = $(this).data('template-name');

                        ShipmentsPage.generateDocument(company, docType, tracking, templateName);
                    });
                } else {
                    $submenu.html('<li class="dropdown-header text-muted">No documents available for this company</li>');
                }
            },
            error: function (xhr, status, error) {
                $submenu.html('<li class="dropdown-header text-danger">Error loading documents</li>');
            }
        });
    },

    // Get base URL dynamically
    getBaseUrl: function () {
        let path = window.location.pathname || '/';
        const adminIndex = path.indexOf('/admin/');

        if (adminIndex !== -1) {
            return path.substring(0, adminIndex);
        }

        const normalized = path.replace(/\/+$/, '');
        const lastSlash = normalized.lastIndexOf('/');

        if (lastSlash > 0) {
            const lastSegment = normalized.substring(lastSlash + 1);
            if (lastSegment.includes('.')) {
                return normalized.substring(0, lastSlash);
            }
        }

        return normalized || '';
    },

    // Generate document for shipment
    generateDocument: function (companyKey, documentType, trackingNumber, templateName) {
        if (!trackingNumber) {
            alert('Tracking number is required');
            return;
        }

        // Build URL with all parameters
        const baseUrl = this.getBaseUrl();
        let url = `${baseUrl}/api/manifest_v2.php?tracking_number=${encodeURIComponent(trackingNumber)}&company=${companyKey}&document_type=${documentType}`;

        // Add template_name if provided
        if (templateName) {
            url += `&template_name=${encodeURIComponent(templateName)}`;
        }
        window.open(url, '_blank');
    },

    // Helper to get API URL
    getApiUrl: function (endpoint) {
        let path = window.location.pathname || '/';
        const adminIndex = path.indexOf('/admin/');
        let basePath = '';

        if (adminIndex !== -1) {
            basePath = path.substring(0, adminIndex);
        } else {
            const normalized = path.replace(/\/+$/, '');
            const lastSlash = normalized.lastIndexOf('/');
            if (lastSlash > 0) {
                const lastSegment = normalized.substring(lastSlash + 1);
                if (lastSegment.includes('.')) {
                    basePath = normalized.substring(0, lastSlash);
                } else {
                    basePath = normalized;
                }
            }
        }

        return basePath + endpoint;
    },

    // ==================== EDIT SHIPMENT ====================

    editShipment: function (id) {
        // Show loading
        App.notify.info('Loading shipment data...');

        // Fetch shipment details
        App.api.get(`shipments.php/${id}`)
            .then(res => {
                if (res.success && res.data) {
                    this.openEditModal(res.data);
                } else {
                    App.notify.error('Failed to load shipment data');
                }
            })
            .catch(err => {
                App.notify.error('Error loading shipment');
            });
    },

    openEditModal: function (shipment) {
        const form = document.getElementById('bl-form');
        if (!form) {
            App.notify.error('Form not found');
            return;
        }

        // Store editing ID
        this.editingShipmentId = shipment.id;

        // Reset form first
        this.resetForm();

        // Update form header title
        const modalTitle = document.getElementById('blModalLabel');
        if (modalTitle) {
            modalTitle.innerHTML = `<i class="bi bi-pencil-square me-2"></i>Edit Shipment - ${shipment.tracking_number}`;
        }

        // Update button text
        const createBtn = document.getElementById('create-shipment-btn');
        if (createBtn) {
            createBtn.innerHTML = 'Update Shipment';
        }

        // Fill form with shipment data
        this.populateForm(shipment);

        // Show the inline form view
        this.showFormView();

        // Go to first tab
        setTimeout(() => {
            this.goToTab(0);
        }, 100);
    },

    populateForm: function (data) {
        const form = document.getElementById('bl-form');
        if (!form) return;

        // List of all form fields
        const fields = [
            // Parties Tab
            'shipper_name', 'shipper_ref', 'shipper_email', 'shipper_phone', 'shipper_address',
            'consignee_name', 'consignee_phone', 'consignee_email', 'consignee_tax_id', 'consignee_address',
            'notify_name', 'notify_phone', 'notify_address', 'pre_carriage',


            // Voyage Tab
            'vessel_name', 'voyage_no', 'vessel_flag', 'imo_number', 'carrier', 'carrier_reference_no',
            'pol', 'pod', 'place_receipt', 'final_destination', 'originals_release_at', 'mode_discharge_area',
            'etd', 'departure_time', 'eta', 'pickup_date', 'pickup_time', 'place_of_delivery',
            'mode', 'type_of_shipment', 'freight_payable_at', 'mode_load_area',
            'seal_no', 'origin', 'destination',

            // Cargo Tab
            'goods_description', 'hs_code', 'commodity_type', 'total_pkgs_text', 'declared_value',
            'num_packages', 'package_type', 'gross_weight', 'net_weight',
            'volume_cbm', 'package_dimensions', 'marks_numbers',
            'country_of_origin', 'cargo_value', 'package_description',
            'un_number', 'dg_class', 'packing_group',

            // Freight Tab
            'freight_terms', 'payment_mode', 'currency', 'tariff_item_no',
            'ocean_freight', 'local_charges', 'doc_fee', 'other_charges',
            'total_freight', 'freight_in_words', 'destination_charges',
            'incoterms', 'lc_number', 'invoice_no', 'packing_list_no', 'co_no', 'customs_no',
            'insured_by', 'insurance_policy', 'insurance_value',

            // Tracking Tab
            'tracking_number', 'current_location', 'status', 'issue_place',
            'estimated_delivery', 'actual_delivery', 'courier', 'issue_date', 'company',
            'special_instructions', 'comments', 'forwarding_agent', 'destination_agent',
            'destination_agent_address', 'destination_agent_email', 'destination_agent_phone', 'booking_no',
            'bl_type', 'num_originals', 'place_of_issue', 'date_of_issue',
            'declaration', 'signatory', 'designation', 'export_reference'
        ];

        // Populate each field
        fields.forEach(fieldName => {
            const input = form.querySelector(`[name="${fieldName}"]`);
            if (input && data[fieldName] !== undefined && data[fieldName] !== null) {
                input.value = data[fieldName];
            }
        });

        const toggle = form.querySelector('#same-as-consignee-toggle');
        if (toggle) {
            const sameAsConsignee = data.notify_same_as_consignee === 'yes' || data.notify_same_as_consignee === 'Yes' || data.notify_same_as_consignee === true || data.notify_same_as_consignee === 1;
            toggle.checked = sameAsConsignee;
            toggle.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Populate containers if available
        if (data.containers && Array.isArray(data.containers)) {
            this.populateContainerEntries(data.containers);
        } else {
            this.populateContainerEntries([]);
        }

        // Populate cargo rows into the cargo accordions when editing
        if (data.cargo_rows && Array.isArray(data.cargo_rows)) {
            // Ensure accordions rendered
            setTimeout(() => {
                data.cargo_rows.forEach((c, idx) => {
                    try {
                        const fields = [
                            'goods_description', 'hs_code', 'commodity_type', 'num_packages', 'package_type', 'gross_weight', 'net_weight', 'volume_cbm', 'package_dimensions', 'marks_numbers', 'country_of_origin', 'cargo_value', 'package_description', 'un_number', 'dg_class', 'packing_group', 'total_pkgs_text', 'declared_value'
                        ];
                        fields.forEach(f => {
                            const el = form.querySelector(`[name="cargo[${idx}][${f}]"]`);
                            if (el) el.value = c[f] ?? '';
                        });

                        // container no display field
                        const cno = form.querySelector(`[name="cargo[${idx}][container_no_display]"]`);
                        if (cno) cno.value = c.container_no ?? c.container_no_display ?? '';

                        // update header label too
                        this.updateCargoAccordionLabel(idx, c.container_no ?? c.container_no_display ?? '');
                    } catch (e) {
                        console.warn('Failed to populate cargo row', idx, e);
                    }
                });
            }, 120);
        }

        // Populate package items if available
        if (data.items && Array.isArray(data.items) && data.items.length > 0) {
            this.populatePackageItems(data.items);
        }

        if (data.company !== undefined && data.company !== null) {
            this.loadCompanyOptions(data.company);
        }

        // Recalculate total freight
        this.calculateTotalFreight();
    },

    populatePackageItems: function (items) {
        const tbody = document.getElementById('pkg-body');
        if (!tbody) return;

        // Clear existing rows
        tbody.innerHTML = '';
        this.rowCount = 0;

        items.forEach((item, idx) => {
            this.rowCount++;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center text-muted">${this.rowCount}</td>
                <td><input type="text" name="item_desc[]" class="form-control form-control-sm" value="${item.item_description || ''}"></td>
                <td><input type="number" min="1" name="item_qty[]" class="form-control form-control-sm" value="${item.quantity || 1}"></td>
                <td><input type="number" step="0.01" name="item_weight[]" class="form-control form-control-sm" value="${item.weight || ''}"></td>
                <td><input type="number" step="0.001" name="item_cbm[]" class="form-control form-control-sm" value="${item.cbm || ''}"></td>
                <td><input type="number" step="0.01" name="item_value[]" class="form-control form-control-sm" value="${item.unit_value || ''}"></td>
                <td><button type="button" class="btn-rm" onclick="removeRow(this)" title="Remove">×</button></td>
            `;
            tbody.appendChild(tr);
        });

        // If no items, add one empty row
        if (items.length === 0) {
            this.addPackageRow();
        }
    },

    // ==================== STATUS EDITING ====================

    editStatus: function (id) {
        this.editingStatusRowId = id;
        this.loadShipments();
    },

    saveStatus: function (id) {
        const select = document.getElementById(`status-select-${id}`);
        if (!select) return;

        const newStatus = select.value;

        App.api.post('shipments.php', { id: id, status: newStatus })
            .then(res => {
                if (res.success) {
                    App.notify.success(`Status updated to ${newStatus.replace(/_/g, ' ')}`);
                } else {
                    App.notify.error(res.message || 'Failed to update status');
                }
                this.cancelStatusEdit();
            })
            .catch(() => {
                App.notify.error('Server error');
                this.cancelStatusEdit();
            });
    },

    cancelStatusEdit: function () {
        this.editingStatusRowId = null;
        this.loadShipments();
    },

    // ==================== FIRST TAB INITIALIZATION ====================

    initializeFirstTab: function () {
        setTimeout(() => {
            const firstTab = document.querySelector('#blTabs .nav-link:first-child');
            const firstPane = document.getElementById('tab-parties');

            if (firstTab && firstPane) {
                document.querySelectorAll('#blTabs .nav-link').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => {
                    p.classList.remove('show', 'active');
                });

                firstTab.classList.add('active');
                firstPane.classList.add('show', 'active');
            }

        }, 100);
    },

    resetModalState: function () {
        // Reset editing ID
        this.editingShipmentId = null;

        // Reset form header title
        const modalTitle = document.getElementById('blModalLabel');
        if (modalTitle) {
            modalTitle.innerHTML = '<i class="bi bi-file-earmark-text me-2"></i> Bill of Lading';
        }

        // Reset button text
        const createBtn = document.getElementById('create-shipment-btn');
        if (createBtn) {
            createBtn.innerHTML = 'Create Shipment';
        }

        // Reset form
        this.resetForm();
    },

    activateFirstTab: function () {
        const tabButtons = document.querySelectorAll('#blTabs .nav-link');
        const tabPanes = document.querySelectorAll('#shipments-form-view .tab-pane');

        tabButtons.forEach(t => t.classList.remove('active'));
        tabPanes.forEach(p => p.classList.remove('show', 'active'));

        if (tabButtons[0]) tabButtons[0].classList.add('active');
        if (tabPanes[0]) {
            tabPanes[0].classList.add('show', 'active');
        }
    },

    // ==================== B/L FORM SETUP ====================

    setupBLForm: function () {
        const self = this;

        const createBtn = document.getElementById('create-shipment-btn');
        if (createBtn) {
            createBtn.addEventListener('click', () => self.saveShipment());
        }

        const resetBtn = document.getElementById('btn-reset-form');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => self.resetForm());
        }

        this.setupTabNavigation();
        this.setupFreightCalculation();
        this.setupNotifyPartySync();
    },

    setupTabNavigation: function () {
        const self = this;
        const tabButtons = Array.from(document.querySelectorAll('#blTabs .nav-link'));
        if (tabButtons.length === 0) return;

        const modalFooter = document.getElementById('bl-form-footer');
        if (!modalFooter) return;

        if (!document.getElementById('bl-prev')) {
            const prevBtn = document.createElement('button');
            prevBtn.type = 'button';
            prevBtn.id = 'bl-prev';
            prevBtn.className = 'btn btn-outline-secondary btn-sm';
            prevBtn.innerHTML = 'Previous';
            prevBtn.style.marginRight = 'auto';
            modalFooter.insertBefore(prevBtn, modalFooter.firstChild);
        }

        if (!document.getElementById('bl-next')) {
            const nextBtn = document.createElement('button');
            nextBtn.type = 'button';
            nextBtn.id = 'bl-next';
            nextBtn.className = 'btn btn-primary btn-sm';
            nextBtn.innerHTML = 'Next';

            const createBtn = document.getElementById('create-shipment-btn');
            if (createBtn) {
                modalFooter.insertBefore(nextBtn, createBtn);
            } else {
                modalFooter.appendChild(nextBtn);
            }
        }

        const prevBtn = document.getElementById('bl-prev');
        const nextBtn = document.getElementById('bl-next');

        function getActiveIndex() {
            return tabButtons.findIndex(b => b.classList.contains('active'));
        }

        function showTab(idx) {
            const safeIdx = Math.max(0, Math.min(tabButtons.length - 1, idx));

            try {
                const tab = new bootstrap.Tab(tabButtons[safeIdx]);
                tab.show();
            } catch (e) {
                tabButtons.forEach((t, i) => {
                    t.classList.toggle('active', i === safeIdx);
                });
                document.querySelectorAll('#shipments-form-view .tab-pane').forEach((p, i) => {
                    p.classList.toggle('show', i === safeIdx);
                    p.classList.toggle('active', i === safeIdx);
                });
            }

            self.updateNavButtons(safeIdx);
        }

        this.updateNavButtons(0);

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                const current = getActiveIndex();
                if (current > 0) showTab(current - 1);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                const current = getActiveIndex();
                if (current < tabButtons.length - 1) showTab(current + 1);
            });
        }

        tabButtons.forEach((btn, idx) => {
            btn.addEventListener('shown.bs.tab', () => self.updateNavButtons(idx));
            btn.addEventListener('click', () => {
                setTimeout(() => self.updateNavButtons(idx), 50);
            });
        });
    },

    updateNavButtons: function (idx) {
        const tabButtons = document.querySelectorAll('#blTabs .nav-link');
        const prevBtn = document.getElementById('bl-prev');
        const nextBtn = document.getElementById('bl-next');

        if (prevBtn) prevBtn.disabled = idx === 0;
        if (nextBtn) nextBtn.disabled = idx === tabButtons.length - 1;
    },

    // ==================== CONTAINER MANAGER ====================

    setupContainerManager: function () {
        const wrapper = document.getElementById('container-details-wrapper');
        const addBtn = document.getElementById('add-container-btn');

        if (!wrapper || !addBtn) return;

        addBtn.addEventListener('click', () => this.addContainerRow());

        wrapper.addEventListener('click', (event) => {
            const removeButton = event.target.closest('.remove-container-btn');
            if (removeButton) {
                this.removeContainerRow(removeButton);
            }
        });

        // Handle input changes inside container entries.
        // - If container_no changes, update existing accordion label/value live (do not re-render to avoid wiping cargo inputs).
        // - For other container fields, re-render after a short debounce (structure changes might be needed).
        wrapper.addEventListener('input', (e) => {
            const target = e.target;
            if (!target) return;
            const name = target.getAttribute && target.getAttribute('name');
            if (!name) return;

            if (name.indexOf('container_no') !== -1) {
                // find container-entry index for this input
                const entry = target.closest('.container-entry');
                if (!entry) return;
                const idx = parseInt(entry.getAttribute('data-container-index') || '0', 10);
                const val = target.value || '';
                this.updateCargoAccordionLabel(idx, val);
                return;
            }

            if (name.indexOf('container_type') !== -1 || name.indexOf('seal_no') !== -1 || name.indexOf('origin') !== -1 || name.indexOf('destination') !== -1) {
                clearTimeout(this._cargoRenderTimeout);
                this._cargoRenderTimeout = setTimeout(() => this.renderCargoAccordions(), 120);
            }
        });

        this.syncContainerLabels();
    },

    addContainerRow: function (data = {}) {
        const wrapper = document.getElementById('container-details-wrapper');
        if (!wrapper) return;

        const index = wrapper.querySelectorAll('.container-entry').length;
        const entry = document.createElement('div');
        entry.className = 'border rounded p-3 mb-3 container-entry';
        entry.setAttribute('data-container-index', index);
        entry.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Container ${index + 1}</h6>
                <button type="button" class="btn btn-outline-danger btn-sm remove-container-btn">Remove</button>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Container Number</label>
                    <input type="text" class="form-control" name="container_no[]" value="${this.escapeHtml(data.container_no || '')}" placeholder="MSCU1234567">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Container Size / Type</label>
                    <select class="form-select" name="container_type[]">
                        <option value="">-- Select --</option>
                        <option value="20' GP (General Purpose)" ${data.container_type === "20' GP (General Purpose)" ? 'selected' : ''}>20' GP (General Purpose)</option>
                        <option value="40' GP" ${data.container_type === "40' GP" ? 'selected' : ''}>40' GP</option>
                        <option value="40' HC (High Cube)" ${data.container_type === "40' HC (High Cube)" ? 'selected' : ''}>40' HC (High Cube)</option>
                        <option value="20' Reefer" ${data.container_type === "20' Reefer" ? 'selected' : ''}>20' Reefer</option>
                        <option value="40' Reefer" ${data.container_type === "40' Reefer" ? 'selected' : ''}>40' Reefer</option>
                        <option value="20' Open Top" ${data.container_type === "20' Open Top" ? 'selected' : ''}>20' Open Top</option>
                        <option value="40' Open Top" ${data.container_type === "40' Open Top" ? 'selected' : ''}>40' Open Top</option>
                        <option value="Flat Rack" ${data.container_type === "Flat Rack" ? 'selected' : ''}>Flat Rack</option>
                        <option value="N/A" ${data.container_type === 'N/A' ? 'selected' : ''}>N/A</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Seal Number</label>
                    <input type="text" class="form-control" name="seal_no[]" value="${this.escapeHtml(data.seal_no || '')}" placeholder="Seal ID">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Origin Country</label>
                    <select class="form-select" name="origin[]">
                        <option value="">-- Select --</option>
                        <option value="Pakistan" ${data.origin === 'Pakistan' ? 'selected' : ''}>Pakistan</option>
                        <option value="Afghanistan" ${data.origin === 'Afghanistan' ? 'selected' : ''}>Afghanistan</option>
                        <option value="India" ${data.origin === 'India' ? 'selected' : ''}>India</option>
                        <option value="Bangladesh" ${data.origin === 'Bangladesh' ? 'selected' : ''}>Bangladesh</option>
                        <option value="UAE" ${data.origin === 'UAE' ? 'selected' : ''}>UAE</option>
                        <option value="Saudi Arabia" ${data.origin === 'Saudi Arabia' ? 'selected' : ''}>Saudi Arabia</option>
                        <option value="Qatar" ${data.origin === 'Qatar' ? 'selected' : ''}>Qatar</option>
                        <option value="Kuwait" ${data.origin === 'Kuwait' ? 'selected' : ''}>Kuwait</option>
                        <option value="China" ${data.origin === 'China' ? 'selected' : ''}>China</option>
                        <option value="USA" ${data.origin === 'USA' ? 'selected' : ''}>USA</option>
                        <option value="UK" ${data.origin === 'UK' ? 'selected' : ''}>UK</option>
                        <option value="Germany" ${data.origin === 'Germany' ? 'selected' : ''}>Germany</option>
                        <option value="Other" ${data.origin === 'Other' ? 'selected' : ''}>Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Destination Country</label>
                    <select class="form-select" name="destination[]">
                        <option value="">-- Select --</option>
                        <option value="UAE" ${data.destination === 'UAE' ? 'selected' : ''}>UAE</option>
                        <option value="Saudi Arabia" ${data.destination === 'Saudi Arabia' ? 'selected' : ''}>Saudi Arabia</option>
                        <option value="Qatar" ${data.destination === 'Qatar' ? 'selected' : ''}>Qatar</option>
                        <option value="Kuwait" ${data.destination === 'Kuwait' ? 'selected' : ''}>Kuwait</option>
                        <option value="Pakistan" ${data.destination === 'Pakistan' ? 'selected' : ''}>Pakistan</option>
                        <option value="Afghanistan" ${data.destination === 'Afghanistan' ? 'selected' : ''}>Afghanistan</option>
                        <option value="India" ${data.destination === 'India' ? 'selected' : ''}>India</option>
                        <option value="Bangladesh" ${data.destination === 'Bangladesh' ? 'selected' : ''}>Bangladesh</option>
                        <option value="China" ${data.destination === 'China' ? 'selected' : ''}>China</option>
                        <option value="USA" ${data.destination === 'USA' ? 'selected' : ''}>USA</option>
                        <option value="UK" ${data.destination === 'UK' ? 'selected' : ''}>UK</option>
                        <option value="Germany" ${data.destination === 'Germany' ? 'selected' : ''}>Germany</option>
                        <option value="Other" ${data.destination === 'Other' ? 'selected' : ''}>Other</option>
                    </select>
                </div>
            </div>`;

        wrapper.appendChild(entry);
        console.log('[ShipmentsPage] addContainerRow: added index=', index, 'container_no=', data.container_no || '');
        this.syncContainerLabels();
        // Re-render cargo accordions to match containers
        this.renderCargoAccordions();
    },

    removeContainerRow: function (button) {
        const wrapper = document.getElementById('container-details-wrapper');
        if (!wrapper) return;

        const entries = wrapper.querySelectorAll('.container-entry');
        const entry = button.closest('.container-entry');

        if (!entry) return;

        if (entries.length <= 1) {
            entry.querySelectorAll('input, select').forEach((field) => {
                field.value = '';
            });
        } else {
            entry.remove();
        }

        console.log('[ShipmentsPage] removeContainerRow: removed');
        this.syncContainerLabels();
        // Re-render cargo accordions after removal
        this.renderCargoAccordions();
    },

    syncContainerLabels: function () {
        const entries = document.querySelectorAll('#container-details-wrapper .container-entry');
        entries.forEach((entry, index) => {
            const heading = entry.querySelector('h6');
            if (heading) heading.textContent = `Container ${index + 1}`;
            // ensure data-container-index is kept in sync (used by live-updates)
            entry.setAttribute('data-container-index', index);
        });
    },

    // Generate cargo accordion sections that correspond to container entries
    renderCargoAccordions: function () {
        const wrapper = document.getElementById('cargo-accordion-wrapper');
        const containerWrapper = document.getElementById('container-details-wrapper');
        if (!wrapper || !containerWrapper) {
            console.log('[ShipmentsPage] renderCargoAccordions: missing wrappers', { wrapper: !!wrapper, containerWrapper: !!containerWrapper });
            return;
        }

        const containers = Array.from(containerWrapper.querySelectorAll('.container-entry'));
        console.log('[ShipmentsPage] renderCargoAccordions: found containers count=', containers.length);
        wrapper.innerHTML = '';

        containers.forEach((entry, idx) => {
            const containerNo = entry.querySelector('[name="container_no[]"]')?.value || '';
            console.log('[ShipmentsPage] renderCargoAccordions: creating accordion for idx=', idx, 'containerNo=', containerNo);
            const item = this.createCargoAccordionItem(idx, containerNo);
            wrapper.appendChild(item);
        });

        // If no containers (shouldn't happen) keep a single accordion
        if (containers.length === 0) {
            console.log('[ShipmentsPage] renderCargoAccordions: no containers found, creating default accordion');
            wrapper.appendChild(this.createCargoAccordionItem(0, ''));
        }
    },

    createCargoAccordionItem: function (index, containerNo) {
        const id = `cargo-accordion-item-${index}`;
        const card = document.createElement('div');
        card.className = 'accordion-item';
        // set id so updates can find this card
        card.id = id;
        card.innerHTML = `
                        <h2 class="accordion-header" id="heading-${id}">
                            <button class="accordion-button ${index > 0 ? 'collapsed' : ''}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${id}" aria-expanded="${index === 0}" aria-controls="collapse-${id}">
                                Cargo for Container ${index + 1} ${containerNo ? '— ' + this.escapeHtml(containerNo) : ''}
                            </button>
                        </h2>
                        <div id="collapse-${id}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" aria-labelledby="heading-${id}" data-bs-parent="#cargo-accordion-wrapper">
                            <div class="accordion-body">
                                                <div class="row g-3">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Container Number</label>
                                                        <input type="text" class="form-control" name="cargo[${index}][container_no_display]" readonly value="${this.escapeHtml(containerNo || '')}" data-linked-container-index="${index}">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Description of Goods</label>
                                                        <input type="text" class="form-control" name="cargo[${index}][goods_description]" placeholder="e.g. Textile Fabrics">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">HS Code</label>
                                                        <input type="text" class="form-control" name="cargo[${index}][hs_code]" placeholder="5208.12">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Commodity Type</label>
                                                        <input type="text" class="form-control" name="cargo[${index}][commodity_type]" placeholder="e.g. Textile">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">No. of Packages</label>
                                                        <input type="number" class="form-control" name="cargo[${index}][num_packages]" min="0">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Package Type</label>
                                                        <input type="text" class="form-control" name="cargo[${index}][package_type]">
                                                    </div>
                                                </div>
                                                <div class="row g-3 mt-2">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Gross Weight (kg)</label>
                                                        <input type="number" step="0.01" class="form-control" name="cargo[${index}][gross_weight]">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Net Weight (kg)</label>
                                                        <input type="number" step="0.01" class="form-control" name="cargo[${index}][net_weight]">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Volume / CBM</label>
                                                        <input type="number" step="0.001" class="form-control" name="cargo[${index}][volume_cbm]">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Package Dimensions</label>
                                                        <input type="text" class="form-control" name="cargo[${index}][package_dimensions]" placeholder="L×W×H">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Total Pkgs Text</label>
                                                        <input type="text" class="form-control" name="cargo[${index}][total_pkgs_text]" placeholder="e.g. 2 Boxes">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Marks &amp; Numbers</label>
                                                        <input type="text" class="form-control" name="cargo[${index}][marks_numbers]">
                                                    </div>
                                                </div>
                                                <div class="row g-3 mt-2">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Country of Origin</label>
                                                        <input type="text" class="form-control" name="cargo[${index}][country_of_origin]">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Cargo Value</label>
                                                        <input type="number" step="0.01" class="form-control" name="cargo[${index}][cargo_value]">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">UN Number</label>
                                                        <input type="text" class="form-control" name="cargo[${index}][un_number]">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">DG Class</label>
                                                        <input type="text" class="form-control" name="cargo[${index}][dg_class]">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Packing Group</label>
                                                        <input type="text" class="form-control" name="cargo[${index}][packing_group]">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Declared Value</label>
                                                        <input type="number" step="0.01" class="form-control" name="cargo[${index}][declared_value]">
                                                    </div>
                                                </div>
                                                <div class="row g-3 mt-2">
                                                    <div class="col-12">
                                                        <label class="form-label">Package Description</label>
                                                        <textarea class="form-control" name="cargo[${index}][package_description]" rows="2"></textarea>
                                                    </div>
                                                </div>
                            </div>
                        </div>
                `;
        return card;
    },

    // Update the accordion header text and readonly container number field for a given index
    updateCargoAccordionLabel: function (index, containerNo) {
        try {
            const wrapper = document.getElementById('cargo-accordion-wrapper');
            if (!wrapper) return;
            const headerBtn = wrapper.querySelector(`#cargo-accordion-item-${index} .accordion-header .accordion-button`);
            if (headerBtn) {
                // update button text while preserving 'Cargo for Container X' prefix
                const base = `Cargo for Container ${index + 1}`;
                headerBtn.textContent = containerNo ? `${base} — ${containerNo}` : base;
            }

            const input = wrapper.querySelector(`input[name="cargo[${index}][container_no_display]"]`);
            if (input) input.value = containerNo || '';
        } catch (e) {
            // ignore errors
        }
    },

    populateContainerEntries: function (containers) {
        const wrapper = document.getElementById('container-details-wrapper');
        if (!wrapper) return;

        wrapper.innerHTML = '';

        if (!containers || containers.length === 0) {
            this.addContainerRow();
            // ensure cargo accordion exists for single container
            this.renderCargoAccordions();
            return;
        }

        containers.forEach((container) => this.addContainerRow(container));
        // render cargo accordions for all containers
        this.renderCargoAccordions();
    },

    setupPackageTable: function () {
        window.addRow = () => this.addPackageRow();
        window.removeRow = (btn) => this.removePackageRow(btn);
    },

    addPackageRow: function () {
        this.rowCount++;
        const tbody = document.getElementById('pkg-body');
        if (!tbody) {
            return;
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center text-muted">${this.rowCount}</td>
            <td><input type="text" placeholder="Item description" name="item_desc[]" class="form-control form-control-sm"></td>
            <td><input type="number" min="1" value="1" name="item_qty[]" class="form-control form-control-sm"></td>
            <td><input type="number" step="0.01" name="item_weight[]" class="form-control form-control-sm"></td>
            <td><input type="number" step="0.001" name="item_cbm[]" class="form-control form-control-sm"></td>
            <td><input type="number" step="0.01" name="item_value[]" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn-rm" onclick="removeRow(this)" title="Remove">×</button></td>
        `;
        tbody.appendChild(tr);
    },

    removePackageRow: function (btn) {
        const tbody = document.getElementById('pkg-body');
        const rows = tbody?.querySelectorAll('tr');

        if (rows && rows.length > 1) {
            btn.closest('tr').remove();

            document.querySelectorAll('#pkg-body tr').forEach((row, idx) => {
                if (row.cells[0]) row.cells[0].textContent = idx + 1;
            });

            this.rowCount = document.querySelectorAll('#pkg-body tr').length;
        }
    },

    // ==================== FREIGHT CALCULATION ====================

    setupFreightCalculation: function () {
        const freightInputs = document.querySelectorAll('.freight-input');
        freightInputs.forEach(input => {
            input.addEventListener('input', () => this.calculateTotalFreight());
        });
    },

    calculateTotalFreight: function () {
        const fields = ['ocean_freight', 'local_charges', 'doc_fee', 'other_charges'];
        let total = 0;

        fields.forEach(name => {
            const el = document.querySelector(`[name="${name}"]`);
            total += parseFloat(el?.value) || 0;
        });

        const totalField = document.getElementById('total_freight');
        if (totalField) totalField.value = total.toFixed(2);
    },

    // ==================== SAVE/UPDATE SHIPMENT ====================

    saveShipment: function () {
        const form = document.getElementById('bl-form');
        if (!form) {
            App.notify.error('Form not found');
            return;
        }

        // Validate required fields
        const sameAsConsignee = form.querySelector('#same-as-consignee-toggle')?.checked || false;
        const shipperNameField = form.querySelector('[name="shipper_name"]');
        const shipperEmailField = form.querySelector('[name="shipper_email"]');
        const consigneeName = form.querySelector('[name="consignee_name"]')?.value.trim();
        const consigneeEmail = form.querySelector('[name="consignee_email"]')?.value.trim();
        const consigneePhone = form.querySelector('[name="consignee_phone"]')?.value.trim();
        const consigneeAddress = form.querySelector('[name="consignee_address"]')?.value.trim();

        if (!shipperNameField?.value.trim()) {
            App.notify.error('Shipper Name is required');
            this.goToTab(0);
            shipperNameField?.focus();
            return;
        }

        if (!shipperEmailField?.value.trim()) {
            App.notify.error('Shipper Email is required');
            this.goToTab(0);
            shipperEmailField?.focus();
            return;
        }

        if (!consigneeName) {
            App.notify.error('Consignee Name is required');
            this.goToTab(0);
            form.querySelector('[name="consignee_name"]')?.focus();
            return;
        }

        if (!consigneeAddress) {
            App.notify.error('Consignee Address is required');
            this.goToTab(0);
            form.querySelector('[name="consignee_address"]')?.focus();
            return;
        }

        // Collect all form data
        const formData = new FormData(form);
        const data = {};

        // Handle array fields
        const arrayFields = ['item_desc', 'item_qty', 'item_weight', 'item_cbm', 'item_value', 'container_no', 'container_type', 'seal_no', 'origin', 'destination'];
        arrayFields.forEach(f => data[f] = []);

        formData.forEach((val, key) => {
            if (key.endsWith('[]')) {
                const base = key.slice(0, -2);
                if (data[base]) data[base].push(val);
            } else {
                data[key] = val;
            }
        });

        // Parse cargo[...] nested inputs (keys like cargo[0][goods_description]) into data.cargo array
        data.cargo = data.cargo || [];
        Object.keys(data).forEach(k => {
            const m = k.match(/^cargo\[(\d+)\]\[(.+)\]$/);
            if (m) {
                const idx = parseInt(m[1], 10);
                const field = m[2];
                data.cargo[idx] = data.cargo[idx] || {};
                data.cargo[idx][field] = data[k];
                delete data[k];
            }
        });

        // If editing, add the ID
        if (this.editingShipmentId) {
            data.id = this.editingShipmentId;
        }

        data.notify_same_as_consignee = sameAsConsignee ? 'yes' : 'no';

        if (sameAsConsignee) {
            data.notify_name = consigneeName || '';
            data.notify_phone = consigneePhone || '';
            data.notify_address = consigneeAddress || '';
        }

        // Show loading
        const btn = document.getElementById('create-shipment-btn');
        const originalText = btn?.innerHTML;
        const isUpdate = !!this.editingShipmentId;

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${isUpdate ? 'Updating...' : 'Saving...'}`;
        }

        App.api.post('shipments.php', data)
            .then(res => {
                if (res.success) {
                    if (isUpdate) {
                        App.notify.success('Shipment updated successfully!');
                    } else {
                        App.notify.success('Shipment created! Tracking: ' + (res.data?.tracking_number || ''));
                    }

                    // Go back to the list view
                    this.hideFormView();

                    // Reload shipments
                    this.loadShipments();
                } else {
                    App.notify.error(res.message || 'Failed to save shipment');
                }
            })
            .catch(err => {
                App.notify.error('Server error while saving');
            })
            .finally(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            });
    },

    goToTab: function (idx) {
        const tabButtons = document.querySelectorAll('#blTabs .nav-link');
        if (tabButtons[idx]) {
            try {
                const tab = new bootstrap.Tab(tabButtons[idx]);
                tab.show();
            } catch (e) {
                tabButtons[idx].click();
            }
            this.updateNavButtons(idx);
        }
    },

    // ==================== RESET FORM ====================

    resetForm: function () {
        const form = document.getElementById('bl-form');
        if (form) {
            form.reset();
            form.classList.remove('was-validated');
        }

        const totalFreight = document.getElementById('total_freight');
        if (totalFreight) totalFreight.value = '';

        const sameAsConsigneeToggle = document.getElementById('same-as-consignee-toggle');
        if (sameAsConsigneeToggle) {
            sameAsConsigneeToggle.checked = false;
            sameAsConsigneeToggle.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Reset container rows to one empty row
        this.populateContainerEntries([]);
        // render cargo accordions for one container
        this.renderCargoAccordions();

        // Reset package table to 1 empty row
        const tbody = document.getElementById('pkg-body');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td class="text-center text-muted">1</td>
                    <td><input type="text" placeholder="Item description" name="item_desc[]" class="form-control form-control-sm"></td>
                    <td><input type="number" min="1" value="1" name="item_qty[]" class="form-control form-control-sm"></td>
                    <td><input type="number" step="0.01" name="item_weight[]" class="form-control form-control-sm"></td>
                    <td><input type="number" step="0.001" name="item_cbm[]" class="form-control form-control-sm"></td>
                    <td><input type="number" step="0.01" name="item_value[]" class="form-control form-control-sm"></td>
                    <td><button type="button" class="btn-rm" onclick="removeRow(this)" title="Remove">×</button></td>
                </tr>
            `;
        }
        this.rowCount = 1;

        this.goToTab(0);
    },

    // ==================== VIEW SHIPMENT ====================

    viewShipment: function (id) {
        if (typeof App !== 'undefined' && typeof App.loadPageWithParams === 'function') {
            App.loadPageWithParams('shipment-details', { id: id });
            return;
        }

        const basePath = this.getBasePath();
        const targetUrl = `${basePath}/pages/shipment-details.php?id=${encodeURIComponent(id)}`;
        window.location.href = targetUrl;
    },

    renderShipmentOverview: function (shipment) {
        const body = document.getElementById('view-shipment-body');
        if (!body) return;

        const status = (shipment.status || 'pending').replace(/_/g, ' ');
        const route = (shipment.pol && shipment.pod) ? `${shipment.pol} → ${shipment.pod}` : 'N/A';
        const weight = shipment.gross_weight || shipment.net_weight || 'N/A';
        const notes = shipment.special_instructions || shipment.comments || 'No additional notes';

        body.innerHTML = `
            <div class="row g-3">
                <div class="col-12">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <h5 class="mb-1">${this.escapeHtml(shipment.tracking_number || 'N/A')}</h5>
                                    <div class="text-muted">${this.escapeHtml(shipment.shipper_name || 'N/A')} → ${this.escapeHtml(shipment.consignee_name || 'N/A')}</div>
                                </div>
                                <span class="badge bg-primary text-capitalize">${this.escapeHtml(status)}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <h6 class="mb-3">Basic Details</h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Tracking No</dt><dd class="col-sm-7">${this.escapeHtml(shipment.tracking_number || 'N/A')}</dd>
                            <dt class="col-sm-5">Status</dt><dd class="col-sm-7 text-capitalize">${this.escapeHtml(status)}</dd>
                            <dt class="col-sm-5">Current Location</dt><dd class="col-sm-7">${this.escapeHtml(shipment.current_location || 'N/A')}</dd>
                            <dt class="col-sm-5">Route</dt><dd class="col-sm-7">${this.escapeHtml(route)}</dd>
                            <dt class="col-sm-5">Weight</dt><dd class="col-sm-7">${this.escapeHtml(weight)}</dd>
                        </dl>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <h6 class="mb-3">Party Information</h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Shipper</dt><dd class="col-sm-7">${this.escapeHtml(shipment.shipper_name || 'N/A')}</dd>
                            <dt class="col-sm-5">Consignee</dt><dd class="col-sm-7">${this.escapeHtml(shipment.consignee_name || 'N/A')}</dd>
                            <dt class="col-sm-5">Carrier</dt><dd class="col-sm-7">${this.escapeHtml(shipment.carrier || 'N/A')}</dd>
                            <dt class="col-sm-5">Booking No</dt><dd class="col-sm-7">${this.escapeHtml(shipment.booking_no || 'N/A')}</dd>
                            <dt class="col-sm-5">Export Ref</dt><dd class="col-sm-7">${this.escapeHtml(shipment.export_reference || 'N/A')}</dd>
                        </dl>
                    </div>
                </div>
                <div class="col-12">
                    <div class="border rounded p-3">
                        <h6 class="mb-2">Notes</h6>
                        <div class="text-muted">${this.escapeHtml(notes)}</div>
                    </div>
                </div>
            </div>`;
    },

    // ==================== DELETE SHIPMENT ====================

    deleteShipment: function (id) {
        if (this.editingStatusRowId !== null) {
            App.notify.warning('Please save or cancel status edit first');
            return;
        }

        if (!confirm('Are you sure you want to delete this shipment?')) return;

        App.api.delete(`shipments.php/${id}`)
            .then(res => {
                if (res.success) {
                    App.notify.success('Shipment deleted!');
                    this.loadShipments();
                } else {
                    App.notify.error(res.message || 'Delete failed');
                }
            })
            .catch(() => App.notify.error('Delete failed'));
    }
};

// Initialize when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    ShipmentsPage.init();
});