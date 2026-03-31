/* Change Photo */

const changeBtn = document.getElementById('change-photo-btn');
const photoInput = document.getElementById('photo-input');
const profileImg = document.getElementById('profile-img');

changeBtn.addEventListener('click', () => {
    photoInput.click();
});

photoInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
        profileImg.src = event.target.result;
    };
    reader.readAsDataURL(file);
});


/* Same as Permanent Address Checkbox */
        const sameAddressCheckbox = document.getElementById('same-address');
        const mailingContent = document.getElementById('mailing-address-content');

        const permanentFields = {
            region:       () => document.getElementById('region').value,
            province:     () => document.getElementById('province').value,
            municipality: () => document.getElementById('municipality').value,
            street:       () => document.getElementById('street-address').value,
            barangay:     () => document.getElementById('barangay').value,
            zip:          () => document.getElementById('zip-code').value,
        };

        function syncMailingAddress() {
            document.getElementById('mailing-region').value       = permanentFields.region();
            document.getElementById('mailing-province').value     = permanentFields.province();
            document.getElementById('mailing-municipality').value = permanentFields.municipality();
            document.getElementById('mailing-street-address').value = permanentFields.street();
            document.getElementById('mailing-barangay').value     = permanentFields.barangay();
            document.getElementById('mailing-zip-code').value     = permanentFields.zip();
        }

        function clearMailingAddress() {
            document.getElementById('mailing-region').value       = '';
            document.getElementById('mailing-province').value     = '';
            document.getElementById('mailing-municipality').value = '';
            document.getElementById('mailing-street-address').value = '';
            document.getElementById('mailing-barangay').value     = '';
            document.getElementById('mailing-zip-code').value     = '';
        }

        sameAddressCheckbox.addEventListener('change', function () {
            const isChecked = this.checked;

            // Disable/enable mailing fields
            mailingContent.querySelectorAll('input, select').forEach(el => {
                el.disabled = isChecked;
            });

            mailingContent.style.opacity = isChecked ? '0.5' : '1';
            mailingContent.style.pointerEvents = isChecked ? 'none' : 'auto';

            if (isChecked) {
                syncMailingAddress();
            } else {
                clearMailingAddress();
            }
        });

        // Keep mailing in sync while checkbox is checked and permanent fields change
        ['region', 'province', 'municipality', 'street-address', 'barangay', 'zip-code'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', () => {
                    if (sameAddressCheckbox.checked) syncMailingAddress();
                });
                el.addEventListener('change', () => {
                    if (sameAddressCheckbox.checked) syncMailingAddress();
                });
            }
        });