document.addEventListener('DOMContentLoaded', () => {
    if (typeof App !== 'undefined' && typeof App.loadPage === 'function') {
        App.loadPage('dashboard');
    }
});
