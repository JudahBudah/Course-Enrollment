/* admin_accounts.js — page-specific scripts */

const modal = document.getElementById('accModal');

/* ── Modal open / close ────────────────────────────────── */

window.addEventListener('click', e => { if (e.target === modal) closeModal(); });

function closeModal() {
    modal.style.display = 'none';
}

/* ── Add account modal ─────────────────────────────────── */

function openAdd() {
    document.getElementById('accModalTitle').textContent     = 'New Admin Account';
    document.getElementById('accSubmitBtn').textContent      = 'Create Account';
    document.getElementById('acc_action').value              = 'add';
    document.getElementById('acc_id').value                  = '';
    document.getElementById('acc_username').value            = '';
    document.getElementById('acc_email').value               = '';
    document.getElementById('acc_password').value            = '';
    document.getElementById('acc_role').value                = 'admin';
    document.getElementById('pw_label').innerHTML            = 'Password <span style="color:var(--red)">*</span>';
    document.getElementById('pw_hint').textContent           = '';
    document.getElementById('acc_password').required         = true;
    modal.style.display = 'block';
}

/* ── Edit account modal ────────────────────────────────── */

function openEdit(a) {
    document.getElementById('accModalTitle').textContent     = 'Edit Admin Account';
    document.getElementById('accSubmitBtn').textContent      = 'Save Changes';
    document.getElementById('acc_action').value              = 'edit';
    document.getElementById('acc_id').value                  = a.admin_id;
    document.getElementById('acc_username').value            = a.username;
    document.getElementById('acc_email').value               = a.email;
    document.getElementById('acc_password').value            = '';
    document.getElementById('acc_role').value                = a.role;
    document.getElementById('pw_label').innerHTML            = 'New Password';
    document.getElementById('pw_hint').textContent           = 'Leave blank to keep current password.';
    document.getElementById('acc_password').required         = false;
    modal.style.display = 'block';
}

/* ── Password show/hide toggle ─────────────────────────── */

function togglePw() {
    const inp = document.getElementById('acc_password');
    const eye = document.getElementById('pw_eye');
    if (inp.type === 'password') {
        inp.type      = 'text';
        eye.className = 'fa-solid fa-eye-slash';
    } else {
        inp.type      = 'password';
        eye.className = 'fa-solid fa-eye';
    }
}