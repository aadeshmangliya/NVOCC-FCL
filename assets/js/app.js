/* ============================
   Main Application JS
   ============================ */

const App = {
    // Configuration
    loadedScripts: new Set(),
    loadedInlineScripts: new Set(),
    config: {
        baseUrl: document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '',
        appEnv: document.querySelector('meta[name="app-env"]')?.getAttribute('content') || 'production',
        appDebug: document.querySelector('meta[name="app-debug"]')?.getAttribute('content') === 'true',
        autoRefreshInterval: 30000,
    },

    // Get the install base path dynamically, regardless of folder name
    getBasePath: function () {
        const metaBaseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
        if (metaBaseUrl) {
            return metaBaseUrl.replace(/\/(admin|pages|api)$/i, '').replace(/\/+$/, '');
        }

        const path = window.location.pathname;
        const segments = path.split('/').filter(Boolean);
        const ignored = ['admin', 'pages', 'api'];

        for (let i = segments.length - 1; i >= 0; i--) {
            if (ignored.includes(segments[i].toLowerCase())) {
                return '/' + segments.slice(0, i).join('/');
            }
        }

        return '';
    },

    // Get API base URL dynamically
    getApiBaseUrl: function () {
        const basePath = this.getBasePath();
        return `${basePath}/api`;
    },

    getBearerToken: function () {
        return document.querySelector('meta[name="api-bearer-token"]')?.getAttribute('content') || '';
    },

    setupApiAuth: function () {
        const addBearerToken = (headers) => {
            const token = this.getBearerToken();
            if (!token) return headers;

            const normalizedHeaders = new Headers(headers || {});
            if (!normalizedHeaders.has('Authorization')) {
                normalizedHeaders.set('Authorization', `Bearer ${token}`);
            }
            return normalizedHeaders;
        };

        if (window.fetch && !window.__wpcargoFetchPatched) {
            const nativeFetch = window.fetch.bind(window);
            window.fetch = function (input, init = {}) {
                const nextInit = { ...init };
                nextInit.headers = addBearerToken(nextInit.headers || {});
                return nativeFetch(input, nextInit);
            };
            window.__wpcargoFetchPatched = true;
        }

        if (typeof jQuery !== 'undefined' && !window.__wpcargoAjaxPatched) {
            jQuery.ajaxSetup({
                beforeSend: function (xhr, settings) {
                    const url = typeof settings?.url === 'string' ? settings.url : '';
                    if (!url || !/\/api\//i.test(url)) {
                        return;
                    }

                    const token = App.getBearerToken();
                    if (token && !xhr.getRequestHeader('Authorization')) {
                        xhr.setRequestHeader('Authorization', `Bearer ${token}`);
                    }
                }
            });
            window.__wpcargoAjaxPatched = true;
        }
    },

    // Initialize app
    init: function () {
        // Set dynamic API base URL
        this.config.apiBaseUrl = this.getApiBaseUrl();

        this.setupApiAuth();
        this.setupEventListeners();
        this.setupAutoRefresh();
        this.setupDataTableObserver();
        // ensure loader helper available and convert any icon buttons
        this._ensureLoader?.();
        this.convertIconButtons?.();
    },

    // Small skeleton loader for page content (not full-screen)
    showSkeleton: function (target) {
        if (!target) return;
        const skeleton = `
            <div class="skeleton-page">
                <div class="skeleton-row title"></div>
                <div class="skeleton-row subtitle"></div>
                <div class="skeleton-card">
                    <div class="skeleton-line"></div>
                    <div class="skeleton-line"></div>
                    <div class="skeleton-line short"></div>
                </div>
                <div class="skeleton-card">
                    <div class="skeleton-line"></div>
                    <div class="skeleton-line"></div>
                    <div class="skeleton-line"></div>
                </div>
            </div>`;
        target.innerHTML = skeleton;
    },

    hideSkeleton: function (target) {
        if (!target) return;
        // nothing special; page load will replace content
    },

    // Disable interactive buttons/links while busy (no full-screen overlay)
    setBusy: function (busy) {
        const selectors = 'button, a.btn, input[type="button"], input[type="submit"]';
        document.querySelectorAll(selectors).forEach(el => {
            if (busy) {
                el.dataset._prevDisabled = el.disabled ? '0' : '0';
                el.disabled = true;
                el.classList.add('disabled-busy');
            } else {
                if (el.dataset._prevDisabled === '0') el.disabled = false;
                el.classList.remove('disabled-busy');
            }
        });
    },

    // Convert buttons with data-icon attribute to icon-only buttons
    convertIconButtons: function () {
        document.querySelectorAll('button[data-icon], a[data-icon]').forEach(el => {
            const icon = el.getAttribute('data-icon');
            if (!icon) return;
            el.innerHTML = `<i class="fas ${icon}"></i>`;
            el.classList.add('icon-only');
        });
    },

    // Setup data table observer
    setupDataTableObserver: function () {
        const container = document.getElementById('page-content');
        if (!container || typeof jQuery === 'undefined' || typeof jQuery.fn.dataTable === 'undefined') return;

        const observer = new MutationObserver(() => {
            setTimeout(() => this.applyDataTables(), 50);
        });

        observer.observe(container, { childList: true, subtree: true });
    },

    // Apply DataTables to any new tables inside page content
    applyDataTables: function () {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.dataTable === 'undefined') return;

        jQuery('#page-content table.table:not(.no-datatable)').each(function () {
            const table = this;
            const $table = jQuery(table);

            if (jQuery.fn.dataTable.isDataTable(table)) {
                return;
            }

            const firstBodyRow = table.querySelector('tbody tr');
            if (!firstBodyRow || firstBodyRow.querySelector('td[colspan], th[colspan]')) {
                return;
            }

            const headerRow = table.querySelector('thead tr') || table.querySelector('tbody tr');
            const headerCells = headerRow ? headerRow.querySelectorAll('th, td') : [];
            const bodyCells = firstBodyRow.querySelectorAll('th, td');
            if (headerCells.length === 0 || headerCells.length !== bodyCells.length) {
                return;
            }

            try {
                $table.DataTable({
                    responsive: true,
                    paging: true,
                    searching: true,
                    ordering: true,
                    lengthChange: false,
                    autoWidth: false,
                    language: {
                        search: 'Search:',
                        zeroRecords: 'No matching records found',
                        paginate: {
                            previous: 'Prev',
                            next: 'Next'
                        }
                    }
                });
            } catch (err) {
                console.warn('DataTables init skipped for table due to invalid structure:', err);
            }
        });
    },

    // Setup event listeners
    setupEventListeners: function () {
        // Navigation clicks (limit to sidebar/nav only, not bootstrap tab links inside modals)
        document.addEventListener('click', (e) => {
            // Ignore clicks inside the Bill of Lading modal (it uses .nav-link for tabs)
            if (e.target.closest('#addShipmentModal')) return;

            // Allow logout link to behave normally
            if (e.target.closest('.logout-link')) return;

            if (e.target.classList.contains('nav-link') || e.target.closest('.nav-link')) {
                e.preventDefault();
                const link = e.target.closest('.nav-link');
                const page = link.getAttribute('data-page');
                // If no data-page (example: bootstrap tab), do nothing
                if (!page) return;
                this.loadPage(page);
            }
        });

        // Sidebar menu clicks
        document.addEventListener('click', (e) => {
            // Allow logout link to behave normally
            if (e.target.closest('.logout-link')) return;

            if (e.target.classList.contains('sidebar-link') || e.target.closest('.sidebar-link')) {
                e.preventDefault();
                const link = e.target.closest('.sidebar-link');
                const page = link.getAttribute('data-page');
                if (page) this.loadPage(page);
            }
        });
    },

    // Load page via AJAX
    executeInlineScripts: function (target, html) {
        const temp = document.createElement('div');
        temp.innerHTML = html;

        const scripts = temp.querySelectorAll('script');
        scripts.forEach(script => script.remove());

        target.innerHTML = temp.innerHTML;

        scripts.forEach(script => {
            const newScript = document.createElement('script');
            if (script.src) {
                if (this.loadedScripts.has(script.src)) {
                    return;
                }
                this.loadedScripts.add(script.src);
                newScript.src = script.src;
                if (script.type) newScript.type = script.type;
                document.body.appendChild(newScript);
            } else {
                const scriptText = script.textContent.trim();
                if (!scriptText) {
                    return;
                }
                newScript.type = script.type || 'text/javascript';
                newScript.textContent = scriptText;
                document.body.appendChild(newScript);
            }
        });
    },

    loadPage: function (page) {
        const contentArea = document.getElementById('page-content');
        // Show skeleton loading state (page-level)
        this.showSkeleton(contentArea);

        // Get base path for pages
        const basePath = this.getBasePath();

        // Clear any page params
        window._pageParams = {};

        // Fetch page
        fetch(`${basePath}/pages/${page}.php`)
            .then(response => {
                if (!response.ok) throw new Error('Page not found');
                return response.text();
            })
            .then(html => {
                this.executeInlineScripts(contentArea, html);
                // convert any data-icon buttons in the new content
                this.convertIconButtons();

                // Update active navigation
                document.querySelectorAll('.nav-link, .sidebar-link').forEach(link => {
                    link.classList.remove('active');
                });
                document.querySelector(`[data-page="${page}"]`)?.classList.add('active');

                // Re-initialize API base URL for new content
                this.config.apiBaseUrl = this.getApiBaseUrl();

                // Trigger page-specific initialization
                this.initPageFeatures(page);
                this.applyDataTables();

                // Scroll to top
                window.scrollTo(0, 0);
            })
            .catch(error => {
                contentArea.innerHTML = '<div class="alert alert-danger">Error loading page. Please try again. ' + error + '</div>';
            });
    },

    // Initialize page-specific features
    initPageFeatures: function (page) {
        try {
            switch (page) {
                case 'dashboard':
                    if (typeof DashboardPage !== 'undefined' && typeof DashboardPage.init === 'function') {
                        DashboardPage.init();
                    }
                    break;
                case 'tracking':
                    if (typeof TrackingPage !== 'undefined' && typeof TrackingPage.init === 'function') {
                        TrackingPage.init();
                    }
                    break;
                case 'shipments':
                    if (typeof ShipmentsPage !== 'undefined' && typeof ShipmentsPage.init === 'function') {
                        ShipmentsPage.init();
                    }
                    break;
                case 'shipments-charges':
                    if (typeof ShipmentsChargesPage !== 'undefined' && typeof ShipmentsChargesPage.init === 'function') {
                        ShipmentsChargesPage.init();
                    }
                    break;
                case 'arrival_notice':
                    if (typeof ArrivalNoticePage !== 'undefined' && typeof ArrivalNoticePage.init === 'function') {
                        ArrivalNoticePage.init();
                    }
                    break;
                case 'email_logs':
                    if (typeof EmailLogsPage !== 'undefined' && typeof EmailLogsPage.init === 'function') {
                        EmailLogsPage.init();
                    }
                    break;
                case 'users':
                    if (typeof UsersPage !== 'undefined' && typeof UsersPage.init === 'function') {
                        UsersPage.init();
                    }
                    break;
                case 'companies':
                    if (typeof CompaniesPage !== 'undefined' && typeof CompaniesPage.init === 'function') {
                        CompaniesPage.init();
                    }
                    break;
                case 'documents':
                    if (typeof DocumentsPage !== 'undefined' && typeof DocumentsPage.init === 'function') {
                        // Check if called with company context from companies page
                        let companyId = window._selectedCompanyId || 0;
                        let companyName = window._selectedCompanyName || '';
                        DocumentsPage.init(companyId, companyName);
                    }
                    break;
                case 'invoice-list':
                    if (typeof InvoicePage !== 'undefined' && typeof InvoicePage.init === 'function') {
                        InvoicePage.init();
                    }
                    break;
                case 'payments':
                    if (typeof PaymentsPage !== 'undefined' && typeof PaymentsPage.init === 'function') {
                        PaymentsPage.init();
                    }
                    break;
                case 'cargo_release':
                    if (typeof CargoReleasePage !== 'undefined' && typeof CargoReleasePage.init === 'function') {
                        CargoReleasePage.init();
                    }
                    break;
                case 'invoice-create':
                    if (typeof InvoiceCreate !== 'undefined' && typeof InvoiceCreate.init === 'function') {
                        InvoiceCreate.init();
                    }
                    break;
                case 'invoices':
                    if (typeof InvoicePage !== 'undefined' && typeof InvoicePage.init === 'function') {
                        InvoicePage.init();
                    }
                    break;
            }
        } catch (e) {
        }
    },

    // Load page via AJAX with parameters
    loadPageWithParams: function (page, params) {
        const contentArea = document.getElementById('page-content');
        // Show skeleton loading state (page-level)
        this.showSkeleton(contentArea);

        // Get base path for pages
        const basePath = this.getBasePath();

        // Build URL with parameters
        let url = `${basePath}/pages/${page}.php`;
        if (params && Object.keys(params).length > 0) {
            const queryString = new URLSearchParams(params).toString();
            url += `?${queryString}`;
        }

        // Store params globally so inline page scripts can read them
        window._pageParams = params || {};

        // Fetch page
        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('Page not found');
                return response.text();
            })
            .then(html => {
                this.executeInlineScripts(contentArea, html);
                // convert any data-icon buttons in the new content
                this.convertIconButtons();

                // Update active navigation
                document.querySelectorAll('.nav-link, .sidebar-link').forEach(link => {
                    link.classList.remove('active');
                });
                document.querySelector(`[data-page="${page}"]`)?.classList.add('active');

                // Re-initialize API base URL for new content
                this.config.apiBaseUrl = this.getApiBaseUrl();

                // Trigger page-specific initialization
                this.initPageFeatures(page);
                this.applyDataTables();

                // Scroll to top
                window.scrollTo(0, 0);
            })
            .catch(error => {
                contentArea.innerHTML = '<div class="alert alert-danger">Error loading page. Please try again. ' + error + '</div>';
            });
    },

    // Helper to load scripts dynamically
    loadScript: function (url, callback) {
        const basePath = this.getBasePath();
        const scriptUrl = basePath + url;

        if (this.loadedScripts.has(scriptUrl)) {
            if (callback) callback();
            return;
        }

        this.loadedScripts.add(scriptUrl);

        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = scriptUrl;
        script.onload = callback;
        document.head.appendChild(script);
    },
    // Auto-refresh dashboard data
    setupAutoRefresh: function () {
        setInterval(() => {
            const currentPage = document.querySelector('.nav-link.active')?.getAttribute('data-page');
            if (currentPage === 'dashboard') {
                this.refreshDashboard();
            }
        }, this.config.autoRefreshInterval);
    },

    // Refresh dashboard
    refreshDashboard: function () {
        if (typeof DashboardPage !== 'undefined') {
            DashboardPage.loadStats();
        }
    },

    // API Helper - GET request
    api: {
        // Get the API base URL, with fallback
        getBase: () => App.config.apiBaseUrl || App.getApiBaseUrl(),

        // Ensure endpoint points to the actual PHP file
        get: (endpoint, params = {}) => {
            if (!endpoint.includes('.php')) {
                endpoint = `${endpoint}.php`;
            }

            let url = `${App.api.getBase()}/${endpoint}`;

            if (Object.keys(params).length > 0) {
                const query = new URLSearchParams(params);
                url += (endpoint.includes('?') ? '&' : '?') + query.toString();
            }

            App.setBusy(true);
            return fetch(url, { method: 'GET', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin' })
                .then(r => r.json())
                .finally(() => App.setBusy(false));
        },

        post: (endpoint, data = {}) => {
            if (!endpoint.includes('.php')) {
                endpoint = `${endpoint}.php`;
            }
            const url = `${App.api.getBase()}/${endpoint}`;
            App.setBusy(true);
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            }).then(r => r.json()).finally(() => App.setBusy(false));
        },

        put: (endpoint, data = {}) => {
            if (!endpoint.includes('.php')) {
                endpoint = `${endpoint}.php`;
            }
            const url = `${App.api.getBase()}/${endpoint}`;
            App.setBusy(true);
            return fetch(url, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            }).then(r => r.json()).finally(() => App.setBusy(false));
        },

        delete: (endpoint) => {
            if (!endpoint.includes('.php')) {
                endpoint = `${endpoint}.php`;
            }
            const url = `${App.api.getBase()}/${endpoint}`;
            App.setBusy(true);
            return fetch(url, { method: 'DELETE', credentials: 'same-origin' }).then(r => r.json()).finally(() => App.setBusy(false));
        }
    },

    // Notification helper
    notify: {
        success: (message) => {
            App.showAlert('success', message);
        },
        error: (message) => {
            App.showAlert('danger', message);
        },
        info: (message) => {
            App.showAlert('info', message);
        },
        warning: (message) => {
            App.showAlert('warning', message);
        }
    },

    // Show alert notification
    showAlert: function (type, message) {
        const alertHtml = `<div class="alert alert-${type}" style="margin-bottom: 20px;">
            ${message}
        </div>`;

        const contentArea = document.getElementById('page-content');
        const firstElement = contentArea.firstChild;

        const alertDiv = document.createElement('div');
        alertDiv.innerHTML = alertHtml;

        if (firstElement) {
            contentArea.insertBefore(alertDiv.firstElementChild, firstElement);
        } else {
            contentArea.appendChild(alertDiv.firstElementChild);
        }

        // Auto remove after 5 seconds
        setTimeout(() => {
            contentArea.querySelector('.alert')?.remove();
        }, 5000);
    },

    // Format currency
    formatCurrency: function (amount) {
        return 'Rs ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    },

    // Format date
    formatDate: function (date) {
        return new Date(date).toLocaleDateString('en-IN', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },

    // Format time
    formatTime: function (date) {
        return new Date(date).toLocaleTimeString('en-IN', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    App.init();

    // Mobile sidebar functionality
    const hamburger = document.getElementById('adminHamburger');
    const sidebar = document.getElementById('adminSidebar');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const overlay = document.getElementById('sidebar-overlay');

    function openSidebar() {
        if (sidebar) {
            sidebar.classList.add('open');
            if (overlay) overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeSidebar() {
        if (sidebar) {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (hamburger) {
        hamburger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            openSidebar();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            closeSidebar();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            closeSidebar();
        });
    }

    // Close sidebar when clicking on sidebar links (mobile only)
    if (sidebar) {
        sidebar.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });
    }

    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });

    // Note: global fetch wrapper removed to avoid always-on overlay; API wrappers handle setBusy per-call

    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
});