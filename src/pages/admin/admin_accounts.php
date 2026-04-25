<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$self_id    = (int)$_SESSION['admin_id'];

// Only superadmins can access this page
if (($admin_data['role'] ?? 'admin') !== 'superadmin') {
    header("Location: admin_home.php");
    die;
}

$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status='pending'"))['c'];

// Flash messages
$flash = '';
$flash_map = [
    'missing'          => 'Please fill in all required fields.',
    'duplicate'        => 'Username or email already exists.',
    'self_delete'      => 'You cannot delete your own account.',
    'invalid_settings' => 'Invalid semester or school year format.',
    'unauthorized'     => 'You do not have permission to perform this action.',
];
if (isset($_GET['error']) && isset($flash_map[$_GET['error']])) {
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> ' . $flash_map[$_GET['error']] . '</div>';
}
if (isset($_GET['success'])) {
    $msgs = [
        'added'          => 'Admin account created.',
        'updated'        => 'Account updated.',
        'deleted'        => 'Account deleted.',
        'settings_saved' => 'System settings saved.',
        'promoted'       => 'Promoted ' . (int)($_GET['count'] ?? 0) . ' student(s) by one year level.' . ((int)($_GET['inc'] ?? 0) > 0 ? ' ' . (int)$_GET['inc'] . ' student(s) marked INC for ungraded subjects.' : ''),
    ];
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> ' . ($msgs[$_GET['success']] ?? 'Done.') . '</div>';
}

// Current system settings
$cur_semester       = get_setting($con, 'current_semester', '1st');
$cur_school_year    = get_setting($con, 'current_school_year', date('Y') . '-' . (date('Y') + 1));
$enrollment_open    = get_setting($con, 'enrollment_open', '1') === '1';
$min_units          = (int)get_setting($con, 'min_units', '0');
$max_units          = (int)get_setting($con, 'max_units', '0');

// Fetch all admins
$admins = [];
$q = mysqli_query($con, "SELECT * FROM admins ORDER BY created_at DESC");
while ($r = mysqli_fetch_assoc($q)) $admins[] = $r;

$superadmin_count = count(array_filter($admins, fn($a) => $a['role'] === 'superadmin'));
$admin_count       = count(array_filter($admins, fn($a) => $a['role'] === 'admin'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Accounts - PLM Admin</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/admin_accounts.css">
</head>
<body>

    <!-- ── Top Nav Bar ────────────────────────────────── -->
    <header>
        <div class="nav-section">
            <!-- Mobile toggle -->
            <button class="nav-button" id="navButton">
                <i class="fa-solid fa-bars" id="trans-bars"></i>
            </button>

            <div class="logo-container">
                <img src="../../assets/plm-logo.png" alt="PLM Logo" loading="lazy">
                <div class="title-container">
                    <div class="logo-title">PAMANTASAN NG LUNGSOD NG MAYNILA</div>
                    <div class="logo-sub">University of the City of Manila</div>
                </div>
            </div>

            <div class="acc-display-container">
                <div class="acc-name">
                    <?php echo htmlspecialchars($admin_data['username'] ?? 'Admin'); ?>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($admin_data['username'] ?? 'A', 0, 1)); ?>
                </div>
            </div>
        </div>

        <!-- ── Side Nav ───────────────────────────────── -->
        <nav class="main-nav" id="navMenu">
            <div class="nav-wrapper">
                <ul class="main-ul">
                    <li>
                        <a href="admin_home.php">
                            <i class="fa-solid fa-house"></i>
                            <span class="li-name">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_applicants.php">
                            <i class="fa-solid fa-user-plus"></i>
                            <span class="li-name">Applicants</span>
                            <?php if ($pending_applicants > 0): ?>
                                <span class="sidebar-badge li-name"><?php echo $pending_applicants; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <!-- Student Records Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="student-records-dropdown">
                            <i class="fa-solid fa-user-graduate"></i>
                            <span class="li-name chev-space">
                                Student Records
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="student-records-menu">
                            <ul>
                                <li><a href="admin_students.php">Students</a></li>
                                <li><a href="admin_enrollments.php">Enrollments</a></li>
                                <li>
                                    <a href="admin_drop_requests.php">
                                        Drop Requests
                                        <?php if (!empty($GLOBALS['pending_drops'])): ?>
                                            <span class="sidebar-badge"><?php echo $GLOBALS['pending_drops']; ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Academic Records Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="acad-records-dropdown">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span class="li-name chev-space">
                                Academic Records
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="acad-records-menu">
                            <ul>
                                <li><a href="admin_subjects.php">Subjects</a></li>
                                <li><a href="admin_classes.php">Classes</a></li>
                                <li><a href="admin_blocks.php">Blocks</a></li>
                            </ul>
                        </div>
                    </li>

                    <!-- Personnel Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="personnel-dropdown">
                            <i class="fa-solid fa-users-gear"></i>
                            <span class="li-name chev-space">
                                Personnel
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="personnel-menu">
                            <ul>
                                <li><a href="admin_faculty.php">Faculty</a></li>
                                <?php if (($admin_data['role'] ?? 'admin') === 'superadmin'): ?>
                                    <li><a href="admin_accounts.php">Admin Accounts</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>

                    <!-- Communications Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="comms-dropdown">
                            <i class="fa-solid fa-bullhorn"></i>
                            <span class="li-name chev-space">
                                Communications
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="comms-menu">
                            <ul>
                                <li><a href="admin_announcements.php">Announcements</a></li>
                                <li><a href="admin_calendar.php">Calendar</a></li>
                            </ul>
                        </div>
                    </li>

                    <li>
                        <a href="../../php/admin_logout.php" class="logout-bg">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span class="li-name">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Dark Mode Toggle -->
            <div class="drk-mode-container">
                <div class="drk-label">
                    <i class="fa-solid fa-moon" id="modeIcon"></i>
                    <span class="li-name" id="modeLabel">Dark Mode</span>
                </div>
                <div class="toggle-track li-name" id="toggleTrack">
                    <div class="toggle-thumb"></div>
                </div>
            </div>
        </nav>
    </header>

    <!-- ── Page Body ──────────────────────────────────── -->
    <div class="main-flex">
        <div class="spacer"></div>

        <main>
            <div class="main-content">

                <div class="page-header">
                    <h1>Admin Accounts</h1>
                    <p>Manage administrator accounts and access</p>
                </div>

                <?php echo $flash; ?>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-icon"><i class="fa-solid fa-user-shield"></i></div>
                        <div class="stat-content">
                            <h3>Admins</h3>
                            <p class="stat-number"><?php echo $admin_count; ?></p>
                        </div>
                    </div>
                    <div class="stat-card gold">
                        <div class="stat-icon"><i class="fa-solid fa-crown"></i></div>
                        <div class="stat-content">
                            <h3>Superadmins</h3>
                            <p class="stat-number"><?php echo $superadmin_count; ?></p>
                        </div>
                    </div>
                </div>

                <!-- System Settings -->
                <div class="card">
                    <div class="card-header">
                        <h2>System Settings</h2>
                    </div>
                    <div style="padding:1.5rem;">
                        <form method="POST" action="../../php/admin_settings_handler.php">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;align-items:start;">

                                <!-- LEFT: Enrollment Period -->
                                <div style="border-right:1px solid var(--off);padding-right:2rem;">
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label>Enrollment Period</label>
                                        <div style="font-size:.82rem;color:var(--text-label);margin-bottom:.75rem;" id="epDesc">
                                            <?php echo $enrollment_open ? 'Open — Students can currently enroll' : 'Closed — Students cannot enroll'; ?>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:.75rem;">
                                            <div class="toggle-track <?php echo $enrollment_open ? 'active' : ''; ?>" id="epTrack" style="cursor:pointer;">
                                                <div class="toggle-thumb"></div>
                                            </div>
                                            <span style="font-size:.9rem;font-weight:600;color:var(--dark);" id="epText"><?php echo $enrollment_open ? 'Open' : 'Closed'; ?></span>
                                            <input type="hidden" name="enrollment_open" id="epInput" value="<?php echo $enrollment_open ? '1' : '0'; ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- RIGHT: Semester + School Year -->
                                <div>
                                    <div class="form-group">
                                        <label>Current Semester <span style="color:var(--red)">*</span></label>
                                        <select name="current_semester" required>
                                            <option value="1st"    <?php echo $cur_semester==='1st'    ?'selected':''; ?>>1st Semester</option>
                                            <option value="2nd"    <?php echo $cur_semester==='2nd'    ?'selected':''; ?>>2nd Semester</option>
                                            <option value="summer" <?php echo $cur_semester==='summer' ?'selected':''; ?>>Summer</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label>School Year <span style="color:var(--red)">*</span></label>
                                        <input type="text" name="current_school_year"
                                               value="<?php echo htmlspecialchars($cur_school_year); ?>"
                                               placeholder="e.g. 2024-2025"
                                               pattern="\d{4}-\d{4}" required>
                                    </div>
                                </div>

                            </div>
                            <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--off);display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                                <button type="submit" class="btn-primary">
                                    <i class="fa-solid fa-floppy-disk"></i> Save Settings
                                </button>
                            </div>
                        </form>

                        <!-- Promote All Students -->
                        <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--off);">
                            <div style="font-weight:600;font-size:.9rem;margin-bottom:.4rem;">
                                <i class="fa-solid fa-circle-arrow-up" style="color:var(--gold);"></i> Promote All Students
                            </div>
                            <div style="font-size:.82rem;color:var(--text-label);margin-bottom:.85rem;">
                                Increments every student's year level by 1 (Year 1→2, 2→3, 3→4, 4→5, 5→6). Year 6 students are unaffected.
                                Irregular students are also promoted and will retake deficient subjects alongside their new year's load.
                            </div>
                            <form method="POST" action="../../php/admin_promote_students.php"
                                  onsubmit="return confirm('Promote ALL students by one year level? This affects every student in the system and cannot be undone.')">
                                <button type="submit" class="btn-primary" style="background:var(--gold);border-color:var(--gold);">
                                    <i class="fa-solid fa-circle-arrow-up"></i> Promote All Students
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Accounts Table -->
                <div class="card">
                    <div class="card-header">
                        <h2>All Admin Accounts</h2>
                        <button class="btn-secondary" onclick="openAdd()">
                            <i class="fa-solid fa-plus"></i>
                            <span class="li-name">New Admin</span>
                        </button>
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
                                $js = htmlspecialchars(json_encode([
                                    'admin_id' => $adm['admin_id'],
                                    'username' => $adm['username'],
                                    'email'    => $adm['email'],
                                    'role'     => $adm['role'],
                                ]), ENT_QUOTES);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($adm['username']); ?></strong>
                                    <?php if ($adm['admin_id'] == $self_id): ?>
                                        <span class="self-badge">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($adm['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $adm['role']; ?>">
                                        <?php echo ucfirst($adm['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($adm['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon" title="Edit"
                                                onclick="openEdit(<?php echo $js; ?>)">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <?php if ($adm['admin_id'] != $self_id): ?>
                                        <form method="POST" action="../../php/admin_accounts_handler.php"
                                              style="display:inline;"
                                              onsubmit="return confirm('Delete this admin account?')">
                                            <input type="hidden" name="action"   value="delete">
                                            <input type="hidden" name="admin_id" value="<?php echo $adm['admin_id']; ?>">
                                            <button type="submit" class="btn-icon danger" title="Delete">
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

            </div><!-- /.main-content -->
        </main>
    </div><!-- /.main-flex -->

    <!-- ── Add / Edit Admin Modal ─────────────────────── -->
    <div id="accModal" class="modal">
        <div class="modal-content acc-modal">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;" id="accModalTitle">
                New Admin Account
            </h2>

            <form method="POST" action="../../php/admin_accounts_handler.php">
                <input type="hidden" name="action"   id="acc_action" value="add">
                <input type="hidden" name="admin_id" id="acc_id">

                <div class="form-group">
                    <label>Username <span style="color:var(--red)">*</span></label>
                    <input type="text" name="username" id="acc_username" required
                           placeholder="e.g. admin_registrar">
                </div>

                <div class="form-group">
                    <label>Email <span style="color:var(--red)">*</span></label>
                    <input type="email" name="email" id="acc_email" required
                           placeholder="admin@plm.edu.ph">
                </div>

                <div class="form-group">
                    <label id="pw_label">Password <span style="color:var(--red)">*</span></label>
                    <div class="pw-toggle">
                        <input type="password" name="password" id="acc_password"
                               placeholder="Enter password">
                        <button type="button" class="pw-eye-btn" onclick="togglePw()" tabindex="-1">
                            <i class="fa-solid fa-eye" id="pw_eye"></i>
                        </button>
                    </div>
                    <small class="pw-hint" id="pw_hint"></small>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="acc_role">
                        <option value="admin">Admin</option>
                        <option value="superadmin">Superadmin</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-submit" id="accSubmitBtn">Create Account</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_accounts.js"></script>
    <script>
    const epTrack = document.getElementById('epTrack');
    const epInput = document.getElementById('epInput');
    const epText  = document.getElementById('epText');
    const epDesc  = document.getElementById('epDesc');

    epTrack.addEventListener('click', function() {
        const isOpen = epInput.value === '1';
        const nowOpen = !isOpen;
        epInput.value = nowOpen ? '1' : '0';
        epTrack.classList.toggle('active', nowOpen);
        epText.textContent = nowOpen ? 'Open' : 'Closed';
        epDesc.textContent = nowOpen
            ? 'Open — Students can currently enroll'
            : 'Closed — Students cannot enroll';
    });
    </script>
</body>
</html>