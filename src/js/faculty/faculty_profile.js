document.addEventListener('DOMContentLoaded', function () {

    /* ── Change Photo ────────────────────────────────────────── */

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


    /* ── Form Validation ────────────────────────────────────── */

    const saveBtn = document.getElementById('save-changes-btn');

    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            const inputs = document.querySelectorAll('input[required], select[required]');
            let valid = true;

            inputs.forEach(field => {
                // Skip disabled fields (e.g. mailing when "same" is checked, or read-only employee ID)
                if (field.disabled) return;

                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--red)';
                    valid = false;
                } else {
                    field.style.borderColor = '';
                }
            });

            if (!valid) {
                alert('Please fill in all required fields.');
            }
        });
    }

    /*
     * NOTE: The "Same as Permanent Address" checkbox logic (blur/copy behaviour)
     * is handled directly in the inline <script> block in faculty_profile.php,
     * using the correct f_permanent_* and f_mailing_* field IDs.
     * No duplicate logic needed here.
     */

});