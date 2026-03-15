document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', function () {
        const year = this.dataset.year;

        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');

        document.querySelectorAll('.year-panel').forEach(p => p.classList.remove('active'));
        document.querySelector(`.year-panel[data-year="${year}"]`).classList.add('active');
    });
});