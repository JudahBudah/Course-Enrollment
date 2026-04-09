document.addEventListener('DOMContentLoaded', function () {

    /* ── Section Tab Nav ─────────────────────────────────────── */

    const navLinks = document.querySelectorAll('.account-nav a');

    const formSections = ['section-student', 'section-academic', 'section-family', 'section-mailing']
        .map(id => document.getElementById(id))
        .filter(Boolean);

    const contentSection  = document.querySelector('.content-section');
    const docsSection     = document.getElementById('section-documents');

    // section-mailing is grouped with section-family under the Address tab
    const addressGroup = ['section-family', 'section-mailing'];

    function showSection(targetId) {
        const isDocuments = targetId === 'section-documents';

        formSections.forEach(sec => {
            sec.style.display = (!isDocuments && (targetId === 'section-family' ? addressGroup.includes(sec.id) : sec.id === targetId)) ? '' : 'none';
        });

        if (docsSection) docsSection.style.display = isDocuments ? '' : 'none';

        navLinks.forEach(link => link.classList.toggle('active', link.getAttribute('href') === '#' + targetId));
    }

    navLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            showSection(this.getAttribute('href').slice(1));
        });
    });

    // Show first section by default
    showSection('section-student');


    /* ── Document Preview Modal ───────────────────────────────── */

    window.viewDoc = function(src, name, ext) {
        document.getElementById('docPreviewName').textContent = name;
        const body = document.getElementById('docPreviewBody');
        body.innerHTML = ext === 'pdf'
            ? `<iframe src="${src}"></iframe>`
            : `<img src="${src}" alt="${name}">`;
        document.getElementById('docPreviewModal').style.display = 'flex';
    };

    window.closeDocPreview = function() {
        document.getElementById('docPreviewModal').style.display = 'none';
        document.getElementById('docPreviewBody').innerHTML = '';
    };

    document.getElementById('docPreviewModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeDocPreview();
    });


    /* ── Change Photo ─────────────────────────────────────────── */

    const changePhotoBtn = document.getElementById('change-photo-btn');
    const photoInput     = document.getElementById('photo-input');
    const profileImg     = document.getElementById('profile-img');

    if (changePhotoBtn && photoInput) {
        changePhotoBtn.addEventListener('click', () => photoInput.click());
    }

    if (photoInput && profileImg) {
        photoInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                profileImg.src = URL.createObjectURL(file);
                document.getElementById('profile-form').submit();
            }
        });
    }

});