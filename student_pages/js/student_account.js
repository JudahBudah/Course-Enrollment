document.addEventListener('DOMContentLoaded', function () {

    /* ── Change Photo ─────────────────────────────────────────── */

    const changePhotoBtn = document.getElementById('change-photo-btn');
    const photoInput     = document.getElementById('photo-input');
    const profileImg     = document.getElementById('profile-img');

    if (changePhotoBtn && photoInput) {
        changePhotoBtn.addEventListener('click', function () {
            photoInput.click();
        });
    }

    if (photoInput && profileImg) {
        photoInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                profileImg.src = URL.createObjectURL(file);
            }
        });
    }


    /* ── Form Validation ──────────────────────────────────────── */

    const saveBtn = document.getElementById('save-changes-btn');
    const form    = document.getElementById('profile-form');

    if (saveBtn && form) {
        form.addEventListener('submit', function (e) {
            const required = form.querySelectorAll('[required]');
            let valid = true;

            required.forEach(field => {
                // Skip disabled mailing fields when same-address is checked
                if (field.disabled) return;

                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--red)';
                    valid = false;
                } else {
                    field.style.borderColor = '';
                }
            });

            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    }


    /* ── Same as Permanent Address ────────────────────────────── */

    const sameAddressCheckbox = document.getElementById('same-address');
    const mailingContent      = document.getElementById('mailing-address-content');

    if (!sameAddressCheckbox || !mailingContent) return;

    const permanentFields = {
        region:       () => document.getElementById('region')?.value        ?? '',
        province:     () => document.getElementById('province')?.value      ?? '',
        municipality: () => document.getElementById('municipality')?.value  ?? '',
        street:       () => document.getElementById('street-address')?.value ?? '',
        barangay:     () => document.getElementById('barangay')?.value      ?? '',
        zip:          () => document.getElementById('zip-code')?.value      ?? '',
    };

    function syncMailingAddress() {
        const m = id => document.getElementById(id);
        m('mailing-region').value        = permanentFields.region();
        m('mailing-province').value      = permanentFields.province();
        m('mailing-municipality').value  = permanentFields.municipality();
        m('mailing-street-address').value = permanentFields.street();
        m('mailing-barangay').value      = permanentFields.barangay();
        m('mailing-zip-code').value      = permanentFields.zip();
    }

    function clearMailingAddress() {
        const m = id => document.getElementById(id);
        m('mailing-region').value        = '';
        m('mailing-province').value      = '';
        m('mailing-municipality').value  = '';
        m('mailing-street-address').value = '';
        m('mailing-barangay').value      = '';
        m('mailing-zip-code').value      = '';
    }

    sameAddressCheckbox.addEventListener('change', function () {
        const isChecked = this.checked;

        mailingContent.querySelectorAll('input, select').forEach(el => {
            el.disabled = isChecked;
        });

        mailingContent.classList.toggle('sync-disabled', isChecked);

        if (isChecked) {
            syncMailingAddress();
        } else {
            clearMailingAddress();
        }
    });

    // Keep mailing in sync while checkbox is checked and permanent fields change
    ['region', 'province', 'municipality', 'street-address', 'barangay', 'zip-code'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input',  () => { if (sameAddressCheckbox.checked) syncMailingAddress(); });
        el.addEventListener('change', () => { if (sameAddressCheckbox.checked) syncMailingAddress(); });
    });

});