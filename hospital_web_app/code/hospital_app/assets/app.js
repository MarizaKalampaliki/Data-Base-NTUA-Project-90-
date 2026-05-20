(function () {
    const start = document.querySelector('input[name="week_start"]');
    const end = document.querySelector('input[name="week_end"]');
    if (start && end) {
        start.addEventListener('change', function () {
            if (!start.value) return;
            const d = new Date(start.value + 'T00:00:00');
            d.setDate(d.getDate() + 6);
            end.value = d.toISOString().slice(0, 10);
        });
    }

    document.querySelectorAll('.client-filter').forEach(function (input) {
        const selector = input.dataset.filterTarget;
        if (!selector) return;
        input.addEventListener('input', function () {
            const term = input.value.toLowerCase().trim();
            document.querySelectorAll(selector).forEach(function (el) {
                el.style.display = el.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    });
})();

function confirmDelete() {
    return confirm('Να διαγραφεί σίγουρα αυτή η εγγραφή; Αν συνδέεται με άλλους πίνακες, η βάση μπορεί να μην το επιτρέψει.');
}
