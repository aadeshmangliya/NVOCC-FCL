const EmailLogsPage = {
    init: function () {
        this.loadLogs();
    },

    loadLogs: function () {
        fetch('../api/arrival_notices.php?action=logs')
            .then(r => r.json())
            .then(res => {
                const tbody = document.getElementById('email-logs-table-body');
                if (!tbody) return;
                tbody.innerHTML = '';
                if (res.success && Array.isArray(res.data)) {
                    if (res.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-muted">No sent emails yet.</td></tr>';
                        return;
                    }

                    res.data.forEach(item => {
                        tbody.insertAdjacentHTML('beforeend', `
                            <tr>
                                <td>${item.staff_name || item.staff_email || 'N/A'}</td>
                                <td>${item.tracking_number || 'N/A'}</td>
                                <td>${item.consignee_name || 'N/A'}</td>
                                <td>${item.consignee_email || 'N/A'}</td>
                                <td>${item.sent_at || 'N/A'}</td>
                                <td><span class="badge bg-success">Sent</span></td>
                            </tr>
                        `);
                    });
                }
            })
            .catch(() => {
                const tbody = document.getElementById('email-logs-table-body');
                if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-danger">Failed to load logs.</td></tr>';
            });
    }
};

window.EmailLogsPage = EmailLogsPage;
