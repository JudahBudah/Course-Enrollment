/* admin_manual_enroll.js — page-specific scripts */

const classSelect   = document.getElementById('classSelect');
const unitWarning   = document.getElementById('unitWarning');
const currentUnits  = window._currentUnits || 0;

if (classSelect) {
    classSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const units    = parseInt(selected.getAttribute('data-units') || 0);
        const total    = currentUnits + units;

        if (total > 24) {
            unitWarning.style.display = 'flex';
            unitWarning.querySelector('.unit-warning-text').textContent =
                'Warning: Total units will be ' + total + ' (exceeds 24 unit limit)';
        } else {
            unitWarning.style.display = 'none';
        }
    });
}