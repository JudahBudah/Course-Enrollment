if (searchInput) {
    searchInput.addEventListener('keyup', function() {
        const val  = this.value.toLowerCase();
        const rows = document.querySelectorAll('#enrollmentTable .enrollment-row');

        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
        });
    });
}