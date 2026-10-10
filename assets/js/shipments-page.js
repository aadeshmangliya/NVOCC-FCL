window.userRole = document.querySelector('meta[name="user-role"]')?.getAttribute('content') || '';

(function () {
    'use strict';

    window.generateBL = function () {
        const d = new Date();
        const yr = d.getFullYear();
        const mo = String(d.getMonth() + 1).padStart(2, '0');
        const rnd = String(Math.floor(Math.random() * 9000) + 1000);
        document.getElementById('bl_number').value = 'RGSL-BL-' + yr + mo + '-' + rnd;
    };

    let rowCount = 1;

    window.addRow = function () {
        rowCount++;
        const tbody = document.getElementById('pkg-body');
        const tr = document.createElement('tr');
        tr.innerHTML =
            '<td class="text-center text-muted">' + rowCount + '</td>' +
            '<td><input type="text" placeholder="Item description" name="item_desc[]"></td>' +
            '<td><input type="number" min="1" value="1" name="item_qty[]"></td>' +
            '<td><input type="number" step="0.01" name="item_weight[]"></td>' +
            '<td><input type="number" step="0.001" name="item_cbm[]"></td>' +
            '<td><input type="number" step="0.01" name="item_value[]"></td>' +
            '<td><button type="button" class="btn-rm" onclick="removeRow(this)" title="Remove">&#215;</button></td>';
        tbody.appendChild(tr);
    };

    window.removeRow = function (btn) {
        const rows = document.querySelectorAll('#pkg-body tr');
        if (rows.length > 1) {
            btn.closest('tr').remove();
            document.querySelectorAll('#pkg-body tr').forEach(function (row, idx) {
                row.cells[0].textContent = idx + 1;
            });
        }
    };

    function calcTotal() {
        const names = ['ocean_freight', 'local_charges', 'doc_fee', 'other_charges'];
        let total = 0;
        names.forEach(function (n) {
            const el = document.querySelector('[name="' + n + '"]');
            total += parseFloat(el ? el.value : 0) || 0;
        });
        const tf = document.getElementById('total_freight');
        if (tf) tf.value = total.toFixed(2);
    }

    document.querySelectorAll('.freight-input').forEach(function (el) {
        el.addEventListener('input', calcTotal);
    });

    function showToast(msg, type) {
        const el = document.getElementById('bl-toast');
        if (!el) return;
        el.className = 'toast align-items-center text-white border-0 bg-' + (type || 'success');
        document.getElementById('toast-msg').textContent = msg;
        bootstrap.Toast.getOrCreateInstance(el, { delay: 3500 }).show();
    }

    function collectFormData() {
        const form = document.getElementById('bl-form');
        if (!form) return {};
        const fd = new FormData(form);
        const data = { bl_number: document.getElementById('bl_number').value.trim() };
        const arrFields = ['item_desc', 'item_qty', 'item_weight', 'item_cbm', 'item_value'];
        arrFields.forEach(function (f) {
            data[f] = [];
        });
        fd.forEach(function (val, key) {
            if (key.endsWith('[]')) {
                const base = key.slice(0, -2);
                if (data[base]) data[base].push(val);
            } else {
                data[key] = val;
            }
        });
        return data;
    }

    const resetButton = document.getElementById('btn-reset-form');
    if (resetButton) {
        resetButton.addEventListener('click', function () {
            if (!confirm('Reset all form fields?')) return;
            document.getElementById('bl-form').reset();
            document.getElementById('bl_number').value = '';
            document.getElementById('total_freight').value = '';
            const tbody = document.getElementById('pkg-body');
            while (tbody.rows.length > 1) tbody.deleteRow(1);
            tbody.rows[0].querySelectorAll('input').forEach(function (i) {
                i.value = '';
            });
            tbody.rows[0].cells[0].textContent = '1';
            rowCount = 1;
            showToast('Form has been reset.', 'secondary');
        });
    }
})();
