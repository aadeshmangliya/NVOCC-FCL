/*  Bill of Lading modal logic */

(function () {
  'use strict';

  function qs(id) {
    return document.getElementById(id);
  }

  // BL number auto-generator
  window.generateBL = function () {
    const d = new Date();
    const yr = d.getFullYear();
    const mo = String(d.getMonth() + 1).padStart(2, '0');
    const rnd = String(Math.floor(Math.random() * 9000) + 1000);
    const bl = qs('bl_number');
    if (bl) bl.value = 'RGSL-BL-' + yr + mo + '-' + rnd;
  };

  // Package table: add / remove rows
  let rowCount = 1;

  window.addRow = function () {
    rowCount++;
    const tbody = qs('pkg-body');
    if (!tbody) return;

    const tr = document.createElement('tr');
    tr.innerHTML =
      '<td class="text-center text-muted">' + rowCount + '</td>' +
      '<td><input type="text" placeholder="Item description" name="item_desc[]"></td>' +
      '<td><input type="number" min="1" value="1" name="item_qty[]"></td>' +
      '<td><input type="number" step="0.01" name="item_weight[]"></td>' +
      '<td><input type="number" step="0.001" name="item_cbm[]"></td>' +
      '<td><input type="number" step="0.01" name="item_value[]"></td>' +
      '<td><button type="button" class="btn-rm" onclick="removeRow(this)" title="Remove">×</button></td>';

    tbody.appendChild(tr);
  };

  window.removeRow = function (btn) {
    const rows = document.querySelectorAll('#pkg-body tr');
    if (rows.length > 1) {
      const tr = btn.closest('tr');
      if (tr) tr.remove();

      // Re-number rows
      document.querySelectorAll('#pkg-body tr').forEach(function (row, idx) {
        if (row.cells && row.cells[0]) row.cells[0].textContent = idx + 1;
      });

      rowCount = document.querySelectorAll('#pkg-body tr').length;
    }
  };

  // Freight auto-total
  function calcTotal() {
    const names = ['ocean_freight', 'local_charges', 'doc_fee', 'other_charges'];
    let total = 0;

    names.forEach(function (n) {
      const el = document.querySelector('[name="' + n + '"]');
      total += parseFloat(el ? el.value : 0) || 0;
    });

    const tf = qs('total_freight');
    if (tf) tf.value = total.toFixed(2);
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.freight-input').forEach(function (el) {
      el.addEventListener('input', calcTotal);
    });

    // ── Tab active highlight (and ensure correct active classes) ──
    // Bootstrap already toggles, but we also keep it consistent for older CSS/edge cases.
    const tabButtons = Array.from(document.querySelectorAll('#blTabs .nav-link'));
    tabButtons.forEach(function (btn) {
      btn.addEventListener('shown.bs.tab', function () {
        // nav-link classes handled by bootstrap; keep panes consistent just in case
        tabButtons.forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
      });
    });

    // ── Next/Previous buttons for tabs ──
    // Add buttons dynamically into modal footer (since current modal has no Next/Prev).
    const modalEl = document.getElementById('addShipmentModal');
    const modalFooter = modalEl ? modalEl.querySelector('.modal-footer') : null;

    if (modalFooter && tabButtons.length > 0) {
      // Prevent duplicate insertion
      if (!document.getElementById('bl-prev')) {
        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.id = 'bl-prev';
        prevBtn.className = 'btn btn-outline-secondary btn-sm me-2';
        prevBtn.innerHTML = '<i class="bi bi-arrow-left-circle me-1"></i>Previous';
        modalFooter.insertBefore(prevBtn, modalFooter.firstChild);
      }

      if (!document.getElementById('bl-next')) {
        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.id = 'bl-next';
        nextBtn.className = 'btn btn-orange btn-sm';
        nextBtn.innerHTML = '<i class="bi bi-arrow-right-circle me-1"></i>Next';
        modalFooter.appendChild(nextBtn);
      }

      const prev = document.getElementById('bl-prev');
      const next = document.getElementById('bl-next');

      function getActiveIndex() {
        // Bootstrap sets .active on the current nav-link
        const active = tabButtons.findIndex(b => b.classList.contains('active'));
        return active >= 0 ? active : 0;
      }

      function showTabByIndex(idx) {
        const safeIdx = Math.max(0, Math.min(tabButtons.length - 1, idx));
        const tabBtn = tabButtons[safeIdx];
        if (!tabBtn) return;

        // Bootstrap tab show
        try {
          new bootstrap.Tab(tabBtn).show();
        } catch (e) {
          // fallback: if bootstrap Tab is not available, just click
          tabBtn.click();
        }

        // Update prev/next disabled state
        if (prev) prev.disabled = safeIdx === 0;
        if (next) next.disabled = safeIdx === tabButtons.length - 1;
      }

      // initial state
      showTabByIndex(getActiveIndex());

      // click handlers
      if (prev) {
        prev.addEventListener('click', function () {
          const i = getActiveIndex();
          showTabByIndex(i - 1);
        });
      }
      if (next) {
        next.addEventListener('click', function () {
          const i = getActiveIndex();
          showTabByIndex(i + 1);
        });
      }

      // also update buttons when user clicks tabs manually
      tabButtons.forEach(function (b, idx) {
        b.addEventListener('shown.bs.tab', function () {
          if (prev) prev.disabled = idx === 0;
          if (next) next.disabled = idx === tabButtons.length - 1;
        });
      });
    }

    // Reset button
    const resetBtn = qs('btn-reset-form');
    if (resetBtn) {
      resetBtn.addEventListener('click', function () {
        if (!confirm('Reset all form fields?')) return;
        const form = qs('bl-form');
        if (form) form.reset();

        const bl = qs('bl_number');
        if (bl) bl.value = '';

        const tf = qs('total_freight');
        if (tf) tf.value = '';

        const tbody = qs('pkg-body');
        if (tbody) {
          while (tbody.rows.length > 1) tbody.deleteRow(1);
          if (tbody.rows[0]) {
            const inputs = tbody.rows[0].querySelectorAll('input');
            inputs.forEach(function (i) {
              i.value = '';
            });
            if (tbody.rows[0].cells && tbody.rows[0].cells[0]) tbody.rows[0].cells[0].textContent = '1';
          }
        }

        rowCount = 1;
        showToast('Form has been reset.', 'secondary');
      });
    }

    // Toast helper
    window.showToast = function (msg, type) {
      const el = qs('bl-toast');
      if (!el) return;

      el.className = 'toast align-items-center text-white border-0 bg-' + (type || 'success');
      const toastMsg = qs('toast-msg');
      if (toastMsg) toastMsg.textContent = msg;

      try {
        bootstrap.Toast.getOrCreateInstance(el, { delay: 3500 }).show();
      } catch (e) {
        // ignore
      }
    };

    // Ensure total freight initial state
    calcTotal();
  });

  function collectFormData() {
    const form = qs('bl-form');
    if (!form) return {};

    const fd = new FormData(form);
    const data = { bl_number: (qs('bl_number')?.value || '').trim() };

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
})();

