<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$self_id    = (int)$_SESSION['admin_id'];
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status='pending'"))['c'];

$flash = '';
$flash_map = [
    'missing'     => ['err', 'Please fill in all required fields.'],
    'duplicate'   => ['err', 'Username or email already exists.'],
    'self_delete' => ['err', 'You cannot delete your own account.'],
];
if (isset($_GET['error']) && isset($flash_map[$_GET['error']])) {
    [$type, $msg] = $flash_map[$_GET['error']];
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> '.$msg.'</div>';
}
if (isset($_GET['success'])) {
    $msgs = ['added'=>'Admin account created.','updated'=>'Account updated.','deleted'=>'Account deleted.'];
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> '.($msgs[$_GET['success']] ?? 'Done.').'</div>';
}

$admins = [];
$q = mysqli_query($con, "SELECT * FROM admins ORDER BY created_at DESC");
while ($r = mysqli_fetch_assoc($q)) $admins[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Accounts - PLM Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin.css">
    <style>
        .modal-content { max-width: 480px; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
        .badge.superadmin { background: rgba(212,175,55,0.2); color: var(--gold); }
        .self-badge { font-size: 0.7rem; background: rgba(59,130,246,0.2); color: #60a5fa; padding: 2px 8px; border-radius: 10px; margin-left: 0.4rem; }
        .pw-toggle { position:relative; }
        .pw-toggle input { padding-right: 2.5rem; }
        .pw-toggle button { position:absolute; right:0.6rem; top:50%; transform:translateY(-50%); background:none; border:none; color:rgba(242,243,242,0.5); cursor:pointer; font-size:0.9rem; }
    </style>
</head>
<body class="dashboard">
<nav class="dashboard-nav">
    <div class="nav-brand">
        <img src="../../assets/plm-logo.png" alt="PLM">
        <span>PLM Admin Portal</span>
    </div>
    <div class="nav-user">
        <span><?php echo htmlspecialchars($admin_data['username'] ?? 'Admin'); ?></span>
        <div class="user-avatar"><?php echo strtoupper(substr($admin_data['username'] ?? 'A', 0, 1)); ?></div>
    </div>
</nav>

<div class="dashboard-container">
    <aside class="sidebar">
        <a href="admin_home.php" class="sidebar-link"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
        <a href="admin_applicants.php" class="sidebar-link">
            <i class="fa-solid fa-user-plus"></i><span>Applicants</span>
            <?php if ($pending_applicants > 0): ?><span class="badge"><?php echo $pending_applicants; ?></span><?php endif; ?>
        </a>
        <a href="admin_students.php" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Students</span></a>
        <a href="admin_blocks.php" class="sidebar-link"><i class="fa-solid fa-layer-group"></i><span>Blocks</span></a>
        <a href="admin_faculty.php" class="sidebar-link"><i class="fa-solid fa-chalkboard-user"></i><span>Faculty</span></a>
        <a href="admin_subjects.php" class="sidebar-link"><i class="fa-solid fa-book"></i><span>Subjects</span></a>
        <a href="admin_classes.php" class="sidebar-link"><i class="fa-solid fa-door-open"></i><span>Classes</span></a>
        <a href="admin_enrollments.php" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Enrollments</span></a>
        <a href="admin_announcements.php" class="sidebar-link"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
        <a href="admin_calendar.php" class="sidebar-link"><i class="fa-solid fa-calendar-days"></i><span>Calendar</span></a>
        <a href="admin_accounts.php" class="sidebar-link active"><i class="fa-solid fa-user-shield"></i><span>Admin Accounts</span></a>
        <a href="../../php/admin_logout.php" class="sidebar-link logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Admin Accounts</h1>
            <p>Manage administrator accounts and access</p>
        </div>

        <?php echo $flash; ?>

        <!-- Stats -->
        <div class="stats-grid" style="margin-bottom:1.5rem;">
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fa-solid fa-user-shield"></i></div>
                <div class="stat-content"><h3>Total Admins</h3><p class="stat-number"><?php echo count($admins); ?></p></div>
            </div>
            <div class="stat-card gold">
                <div class="stat-icon"><i class="fa-solid fa-crown"></i></div>
                <div class="stat-content"><h3>Superadmins</h3><p class="stat-number"><?php echo count(array_filter($admins, fn($a) => $a['role'] === 'superadmin')); ?></p></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>All Admin Accounts</h2>
                <button class="btn-secondary" onclick="openAdd()"><i class="fa-solid fa-plus"></i> New Admin</button>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($admins as $adm):
                        $js = htmlspecialchars(json_encode(['admin_id'=>$adm['admin_id'],'username'=>$adm['username'],'email'=>$adm['email'],'role'=>$adm['role']]), ENT_QUOTES);
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($adm['username']); ?></strong>
                            <?php if ($adm['admin_id'] == $self_id): ?>
                                <span class="self-badge">You</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($adm['email']); ?></td>
                        <td><span class="badge <?php echo $adm['role']; ?>"><?php echo ucfirst($adm['role']); ?></span></td>
                        <td><?php echo date('M j, Y', strtotime($adm['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon" title="Edit" onclick="openEdit(<?php echo $js; ?>)">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <?php if ($adm['admin_id'] != $self_id): ?>
                                <form method="POST" action="../../php/admin_accounts_handler.php" style="display:inline;"
                                    onsubmit="return confirm('Delete this admin account?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="admin_id" value="<?php echo $adm['admin_id']; ?>">
                                    <button type="submit" class="btn-icon" style="color:#ef4444;border-color:#ef4444;" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- ADD / EDIT MODAL -->
<div id="accModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;" id="accModalTitle">New Admin Account</h2>

        <form method="POST" action="../../php/admin_accounts_handler.php">
            <input type="hidden" name="action" id="acc_action" value="add">
            <input type="hidden" name="admin_id" id="acc_id">

            <div class="form-group">
                <label>Username <span style="color:var(--red)">*</span></label>
                <input type="text" name="username" id="acc_username" required placeholder="e.g. admin_registrar">
            </div>

            <div class="form-group">
                <label>Email <span style="color:var(--red)">*</span></label>
                <input type="email" name="email" id="acc_email" required placeholder="admin@plm.edu.ph">
            </div>

            <div class="form-group">
                <label id="pw_label">Password <span style="color:var(--red)">*</span></label>
                <div class="pw-toggle">
                    <input type="password" name="password" id="acc_password" placeholder="Enter password">
                    <button type="button" onclick="togglePw()" tabindex="-1"><i class="fa-solid fa-eye" id="pw_eye"></i></button>
                </div>
                <small id="pw_hint" style="color:rgba(242,243,242,0.4);font-size:0.75rem;"></small>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" id="acc_role">
                    <option value="admin">Admin</option>
                    <option value="superadmin">Superadmin</option>
                </select>
            </div>

            <div style="display:flex;gap:1rem;margin-top:1rem;">
                <button type="submit" class="btn-submit" style="flex:1;" id="accSubmitBtn">Create Account</button>
                <button type="button" class="btn-secondary" onclick="closeModal()" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('accModal');
window.onclick = e => { if (e.target === modal) closeModal(); };
function closeModal() { modal.style.display = 'none'; }

function openAdd() {
    document.getElementById('accModalTitle').textContent = 'New Admin Account';
    document.getElementById('accSubmitBtn').textContent  = 'Create Account';
    document.getElementById('acc_action').value = 'add';
    document.getElementById('acc_id').value     = '';
    document.getElementById('acc_username').value = '';
    document.getElementById('acc_email').value    = '';
    document.getElementById('acc_password').value = '';
    document.getElementById('acc_role').value     = 'admin';
    document.getElementById('pw_label').innerHTML = 'Password <span style="color:var(--red)">*</span>';
    document.getElementById('pw_hint').textContent = '';
    document.getElementById('acc_password').required = true;
    modal.style.display = 'block';
}

function openEdit(a) {
    document.getElementById('accModalTitle').textContent = 'Edit Admin Account';
    document.getElementById('accSubmitBtn').textContent  = 'Save Changes';
    document.getElementById('acc_action').value  = 'edit';
    document.getElementById('acc_id').value      = a.admin_id;
    document.getElementById('acc_username').value = a.username;
    document.getElementById('acc_email').value    = a.email;
    document.getElementById('acc_password').value = '';
    document.getElementById('acc_role').value     = a.role;
    document.getElementById('pw_label').innerHTML = 'New Password';
    document.getElementById('pw_hint').textContent = 'Leave blank to keep current password.';
    document.getElementById('acc_password').required = false;
    modal.style.display = 'block';
}

function togglePw() {
    const inp = document.getElementById('acc_password');
    const eye = document.getElementById('pw_eye');
    if (inp.type === 'password') { inp.type = 'text';     eye.className = 'fa-solid fa-eye-slash'; }
    else                         { inp.type = 'password'; eye.className = 'fa-solid fa-eye'; }
}
</script>
</body>
</html>
