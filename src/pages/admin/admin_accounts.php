<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$self_id    = (int)$_SESSION['admin_id'];
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status='pending'"))['c'];

// Flash messages
$flash = '';
$flash_map = [
    'missing'     => 'Please fill in all required fields.',
    'duplicate'   => 'Username or email already exists.',
    'self_delete' => 'You cannot delete your own account.',
];
if (isset($_GET['error']) && isset($flash_map[$_GET['error']])) {
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> ' . $flash_map[$_GET['error']] . '</div>';
}
if (isset($_GET['success'])) {
    $msgs = ['added'=>'Admin account created.','updated'=>'Account updated.','deleted'=>'Account deleted.'];
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> ' . ($msgs[$_GET['success']] ?? 'Done.') . '</div>';
}

// Fetch all admins
$admins = [];
$q = mysqli_query($con, "SELECT * FROM admins ORDER BY created_at DESC");
while ($r = mysqli_fetch_assoc($q)) $admins[] = $r;

$superadmin_count = count(array_filter($admins, fn($a) => $a['role'] === 'superadmin'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Accounts - PLM Admin</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/admin_accounts.css">
</head>
<body>

    <!-- ── Top Nav Bar ────────────────────────────────── -->
    <header>
        <div class="nav-section">
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
                    <li>
                        <a href="admin_students.php">
                            <i class="fa-solid fa-users"></i>
                            <span class="li-name">Students</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_blocks.php">
                            <i class="fa-solid fa-layer-group"></i>
                            <span class="li-name">Blocks</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_faculty.php">
                            <i class="fa-solid fa-chalkboard-user"></i>
                            <span class="li-name">Faculty</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_subjects.php">
                            <i class="fa-solid fa-book"></i>
                            <span class="li-name">Subjects</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_classes.php">
                            <i class="fa-solid fa-door-open"></i>
                            <span class="li-name">Classes</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_enrollments.php">
                            <i class="fa-solid fa-file-lines"></i>
                            <span class="li-name">Enrollments</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_announcements.php">
                            <i class="fa-solid fa-bullhorn"></i>
                            <span class="li-name">Announcements</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_calendar.php">
                            <i class="fa-solid fa-calendar-days"></i>
                            <span class="li-name">Calendar</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_accounts.php" class="active">
                            <i class="fa-solid fa-user-shield"></i>
                            <span class="li-name">Admin Accounts</span>
                        </a>
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
                            <h3>Total Admins</h3>
                            <p class="stat-number"><?php echo count($admins); ?></p>
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
</body>
</html>