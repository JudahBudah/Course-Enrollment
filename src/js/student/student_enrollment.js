document.querySelectorAll('thead .select-all').forEach(selectAll => {
    selectAll.addEventListener('change', function () {
        const table = this.closest('table');
        table.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
            cb.checked = this.checked;
        });
    });
});