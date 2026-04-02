document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function () {
        const label = this.nextElementSibling;
        if (this.files.length > 0) {
            label.innerHTML = `<i class="fa-solid fa-check"></i> ${this.files[0].name}`;
            label.style.background = '#22c55e';
        }
    });
});