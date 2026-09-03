/* Shared behaviour for all Admin module pages */

// Open a modal by id
function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}

// Close a modal by id
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

// Close modal when clicking the dark overlay itself (not its content)
document.addEventListener('click', function (e) {
    if (e.target.classList && e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
    }
});

// Populate an "Edit" modal's fields from the closest data-carrying
// container's data-* attributes (works for <tr> rows and card <div>s alike)
function fillEditForm(button, formPrefix) {
    const row = button.closest('[data-id]');
    if (!row) return;
    const data = row.dataset;
    Object.keys(data).forEach(function (key) {
        const field = document.getElementById(formPrefix + '_' + key);
        if (field) field.value = data[key];
    });
}

// Live client-side filter for a .data-table based on a search input's value
function filterTable(inputEl, tableBodyId) {
    const query = inputEl.value.trim().toLowerCase();
    const tbody = document.getElementById(tableBodyId);
    if (!tbody) return;
    Array.from(tbody.rows).forEach(function (row) {
        if (row.classList.contains('empty-row')) return;
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}

// Confirm before a destructive delete form submits
function confirmDelete(form, message) {
    return confirm(message || 'Are you sure you want to delete this? This cannot be undone.');
}

// When arriving via a #row-... link (e.g. from the global search results),
// scroll to that row/card and briefly flash-highlight it.
document.addEventListener('DOMContentLoaded', function () {
    if (!location.hash) return;
    const el = document.querySelector(location.hash);
    if (!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el.classList.add('highlight-flash');
    setTimeout(function () { el.classList.remove('highlight-flash'); }, 2000);
});
