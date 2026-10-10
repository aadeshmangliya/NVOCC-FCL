/* ==================
   Dashboard Page JS
   ================== */

(function () {
    if (window.DashboardPage && typeof window.DashboardPage.init === 'function') {
        return;
    }

    window.DashboardPage = {
        currentActivityPage: 1,
        currentActivityPerPage: 10,

        init: function () {
            this.loadStats();
            this.loadRecentActivity(this.currentActivityPage, this.currentActivityPerPage);
            this.setupCharts();
        },

        loadStats: function () {
            App.api.get('dashboard.php/stats')
                .then(response => {
                    if (response.success) {
                        this.updateStatCards(response.data);
                        return;
                    }

                    const container = document.getElementById('stats-container');
                    if (container) {
                        container.innerHTML = '<div class="alert alert-danger">Failed to load statistics.</div>';
                    }
                })
                .catch((error) => {
                    const container = document.getElementById('stats-container');
                    if (container) {
                        container.innerHTML = '<div class="alert alert-danger">' + error.message + '</div>';
                    }
                });
        },

        updateStatCards: function (stats) {
            const container = document.getElementById('stats-container');
            if (!container) return;

            const formatAmount = (amount) => {
                const numeric = Number(amount || 0);
                return 'Rs ' + numeric.toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            };

            const cards = [
                {
                    iconClass: 'fa-box',
                    value: stats.total_shipments || 0,
                    label: 'Total Shipments',
                    variant: ''
                },
                {
                    iconClass: 'fa-bell',
                    value: stats.arrival_notices_total || 0,
                    label: 'Arrival Notices',
                    variant: 'success'
                },
                {
                    iconClass: 'fa-file-invoice-dollar',
                    value: formatAmount(stats.invoices_total_amount || 0),
                    label: 'Invoices Total',
                    detail: `${stats.invoices_total || 0} invoices`,
                    variant: 'info'
                },
                {
                    iconClass: 'fa-check-circle',
                    value: formatAmount(stats.invoices_paid_amount || 0),
                    label: 'Invoices Paid',
                    detail: `${stats.invoices_paid || 0} paid`,
                    variant: 'success'
                },
                {
                    iconClass: 'fa-clock',
                    value: formatAmount(stats.invoices_pending_amount || 0),
                    label: 'Invoices Pending',
                    detail: `${Math.max(0, (stats.invoices_total || 0) - (stats.invoices_paid || 0))} remaining`,
                    variant: 'warning'
                },
                {
                    iconClass: 'fa-lock',
                    value: stats.cargo_locked || 0,
                    label: 'Cargo Locked',
                    variant: 'warning'
                },
                {
                    iconClass: 'fa-truck',
                    value: stats.cargo_released || 0,
                    label: 'Cargo Released',
                    variant: 'info'
                }
            ];

            const html = `
                <div class="row g-3 mb-4">
                    ${cards.map(card => `
                        <div class="col-lg-4 col-md-6">
                            <div class="stat-card ${card.variant}">
                                <div class="stat-card-icon"><i class="fas ${card.iconClass}"></i></div>
                                <div class="stat-card-content">
                                    <h3>${card.value}</h3>
                                    <p>${card.label}</p>
                                    ${card.amount ? `<small class="text-muted">${card.amount}</small>` : ''}
                                    ${card.detail ? `<small class="text-muted d-block">${card.detail}</small>` : ''}
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;

            container.innerHTML = html;
        },

        loadRecentActivity: function (page = 1, perPage = 10) {
            this.currentActivityPage = Number(page) || 1;
            this.currentActivityPerPage = Number(perPage) || 10;

            App.api.get('dashboard.php/activity', {
                page: this.currentActivityPage,
                per_page: this.currentActivityPerPage
            })
                .then(response => {
                    if (response.success) {
                        this.updateActivityTimeline(response.data);
                        return;
                    }

                    const container = document.getElementById('activity-container');
                    if (container) {
                        container.innerHTML = '<div class="alert alert-danger">Failed to load recent activity.</div>';
                    }
                })
                .catch(() => {
                    const container = document.getElementById('activity-container');
                    if (container) {
                        container.innerHTML = '<div class="alert alert-danger">Network error while loading activity.</div>';
                    }
                });
        },

        updateActivityTimeline: function (payload) {
            const container = document.getElementById('activity-container');
            if (!container) return;

            const list = Array.isArray(payload) ? payload : (payload?.items || payload?.data || []);
            const activities = Array.isArray(list) ? list : [];
            const pagination = payload?.pagination || payload?.data?.pagination || {};
            const currentPage = Number(pagination.page || this.currentActivityPage || 1);
            const perPage = Number(pagination.per_page || this.currentActivityPerPage || 10);
            const total = Number(pagination.total || activities.length || 0);
            const totalPages = Math.max(1, Number(pagination.total_pages || Math.ceil(total / perPage || 1)));

            let html = `
                <div class="activity-toolbar">
                    <div class="activity-toolbar-left">
                        <label for="activity-per-page">Show</label>
                        <select id="activity-per-page" class="form-select form-select-sm">
                            <option value="10" ${perPage === 10 ? 'selected' : ''}>10</option>
                            <option value="25" ${perPage === 25 ? 'selected' : ''}>25</option>
                            <option value="50" ${perPage === 50 ? 'selected' : ''}>50</option>
                            <option value="100" ${perPage === 100 ? 'selected' : ''}>100</option>
                        </select>
                        <span>entries</span>
                    </div>
                    <div class="activity-toolbar-right">Page ${currentPage} of ${totalPages}</div>
                </div>
                <div class="timeline">
            `;

            if (activities.length > 0) {
                activities.forEach(activity => {
                    const statusBadge = `<span class="status-badge badge-${activity.status}">${(activity.status || '').replace('_', ' ')}</span>`;
                    html += `
                        <div class="timeline-item completed">
                            <div class="timeline-time">${App.formatTime(activity.created_at)}</div>
                            <div class="timeline-content">
                                <h5>${activity.shipper_name || 'Shipment'} - ${activity.tracking_number || 'N/A'}</h5>
                                <p>${statusBadge} | Location: ${activity.location || 'N/A'}</p>
                                <small class="text-muted">${activity.remarks || 'No remarks'}</small>
                            </div>
                        </div>
                    `;
                });
            } else {
                html += '<p class="timeline-empty">No recent activity</p>';
            }

            html += '</div>';

            if (totalPages > 1) {
                const pageButtons = [];
                const maxVisiblePages = 5;
                let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
                let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
                if (endPage - startPage < maxVisiblePages - 1) {
                    startPage = Math.max(1, endPage - maxVisiblePages + 1);
                }

                for (let page = startPage; page <= endPage; page++) {
                    pageButtons.push(`
                        <button type="button" class="btn btn-sm ${page === currentPage ? 'btn-primary' : 'btn-outline-secondary'} activity-page-btn" data-page="${page}">
                            ${page}
                        </button>
                    `);
                }

                html += `
                    <div class="activity-pagination">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-page="${Math.max(1, currentPage - 1)}" ${currentPage <= 1 ? 'disabled' : ''}>Previous</button>
                        <div class="activity-page-numbers">${pageButtons.join('')}</div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-page="${Math.min(totalPages, currentPage + 1)}" ${currentPage >= totalPages ? 'disabled' : ''}>Next</button>
                    </div>
                `;
            }

            container.innerHTML = html;

            const pageSelect = document.getElementById('activity-per-page');
            if (pageSelect) {
                pageSelect.addEventListener('change', (event) => {
                    this.loadRecentActivity(1, event.target.value);
                });
            }

            container.querySelectorAll('.activity-page-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const page = Number(button.dataset.page);
                    if (!Number.isNaN(page) && page >= 1) {
                        this.loadRecentActivity(page, this.currentActivityPerPage);
                    }
                });
            });

            container.querySelectorAll('.activity-pagination button[data-page]').forEach(button => {
                button.addEventListener('click', () => {
                    const page = Number(button.dataset.page);
                    if (!Number.isNaN(page) && page >= 1) {
                        this.loadRecentActivity(page, this.currentActivityPerPage);
                    }
                });
            });
        },

        setupCharts: function () {
            // Chart.js integration would go here
            // For now, we'll use a simple bar chart representation
        }
    };
})();
