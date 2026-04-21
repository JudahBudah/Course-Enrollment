<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status = 'pending'"))['c'];

// Handle flash
$flash = '';
if (isset($_GET['success'])) {
    $msgs = ['drop_accepted' => 'Drop request approved.', 'drop_rejected' => 'Drop request rejected.'];
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> ' . ($msgs[$_GET['success']] ?? 'Done.') . '</div>';
}
if (isset($_GET['error'])) {
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> Action failed. Please try again.</div>';
}

// Stats
$total_pending  = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM enrollments WHERE status = 'drop_requested'"))['c'];
$total_approved = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM enrollments WHERE status = 'dropped'"))['c'];

// Filters
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'pending';

$where = "WHERE e.status = 'drop_requested'";
if ($filter === 'all')     $where = "WHERE e.status IN ('drop_requested','dropped','confirmed')";
if (!empty($search)) {
    $s = mysqli_real_escape_string($con, $search);
    $where .= " AND (st.first_name LIKE '%$s%' OR st.last_name LIKE '%$s%' OR st.student_number LIKE '%$s%' OR s.subject_code LIKE '%$s%' OR s.subject_name LIKE '%$s%')";
}

$drop_requests = mysqli_query($con, "
    SELECT e.enrollment_id, e.status, e.student_id,
           st.first_name, st.last_name, st.student_number, st.course, st.year_level,
           s.subject_code, s.subject_name, s.units,
           c.class_id, c.section, c.schedule_day, c.schedule_time, c.room,
           CONCAT(f.first_name, ' ', f.last_name) as faculty_name
    FROM enrollments e
    JOIN students st ON e.student_id = st.student_id
    JOIN classes c ON e.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    $where
    ORDER BY st.last_name, st.first_name, s.subject_code
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drop Requests - PLM Admin</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/admin_drop_requests.css">
</head>
<body>

    <header>
        <div class="nav-section">
            <button class="nav-button" id="navButton"><i class="fa-solid fa-bars" id="trans-bars"></i></button>
            <div class="logo-container">
                <img src="../../assets/plm-logo.png" alt="PLM Logo" loading="lazy">
                <div class="title-container">
                    <div class="logo-title">PAMANTASAN NG LUNGSOD NG MAYNILA</div>
                    <div class="logo-sub">University of the City of Manila</div>
                </div>
            </div>
            <div class="acc-display-container">
                <div class="acc-name"><?php echo htmlspecialchars($admin_data['username'] ?? 'Admin'); ?></div>
                <div class="user-avatar"><?php echo strtoupper(substr($admin_data['username'] ?? 'A', 0, 1)); ?></div>
            </div>
        </div>

        <nav class="main-nav" id="navMenu">
            <div class="nav-wrapper">
                <ul class="main-ul">
                    <li><a href="admin_home.php"><i class="fa-solid fa-house"></i><span class="li-name">Dashboard</span></a></li>
                    <li>
                        <a href="admin_applicants.php"><i class="fa-solid fa-user-plus"></i><span class="li-name">Applicants</span>
                        <?php if ($pending_applicants > 0): ?><span class="sidebar-badge li-name"><?php echo $pending_applicants; ?></span><?php endif; ?>
                        </a>
                    </li>
                    <li><a href="admin_students.php"><i class="fa-solid fa-users"></i><span class="li-name">Students</span></a></li>
                    <li><a href="admin_blocks.php"><i class="fa-solid fa-layer-group"></i><span class="li-name">Blocks</span></a></li>
                    <li><a href="admin_faculty.php"><i class="fa-solid fa-chalkboard-user"></i><span class="li-name">Faculty</span></a></li>
                    <li><a href="admin_subjects.php"><i class="fa-solid fa-book"></i><span class="li-name">Subjects</span></a></li>
                    <li><a href="admin_classes.php"><i class="fa-solid fa-door-open"></i><span class="li-name">Classes</span></a></li>
                    <li><a href="admin_enrollments.php"><i class="fa-solid fa-file-lines"></i><span class="li-name">Enrollments</span></a></li>
                    <li>
                        <a href="admin_drop_requests.php" class="active"><i class="fa-solid fa-right-from-bracket"></i><span class="li-name">Drop Requests</span>
                        <?php if (!empty($GLOBALS['pending_drops'])): ?><span class="sidebar-badge li-name"><?php echo $GLOBALS['pending_drops']; ?></span><?php endif; ?>
                        </a>
                    </li>
                    <li><a href="admin_announcements.php"><i class="fa-solid fa-bullhorn"></i><span class="li-name">Announcements</span></a></li>
                    <li><a href="admin_calendar.php"><i class="fa-solid fa-calendar-days"></i><span class="li-name">Calendar</span></a></li>
                    <li><?php if (($admin_data['role'] ?? 'admin') === 'superadmin'): ?>
                        <a href="admin_accounts.php"><i class="fa-solid fa-user-shield"></i><span class="li-name">Admin Accounts</span></a>
                    <?php endif; ?></li>
                    <li><a href="../../php/admin_logout.php" class="logout-bg"><i class="fa-solid fa-right-from-bracket"></i><span class="li-name">Logout</span></a></li>
                </ul>
            </div>
            <div class="drk-mode-container">
                <div class="drk-label"><i class="fa-solid fa-moon" id="modeIcon"></i><span class="li-name" id="modeLabel">Dark Mode</span></div>
                <div class="toggle-track li-name" id="toggleTrack"><div class="toggle-thumb"></div></div>
            </div>
        </nav>
    </header>

    <div class="main-flex">
        <div class="spacer"></div>
        <main>
            <div class="main-content">

                <div class="page-header">
                    <h1>Drop Requests</h1>
                    <p>Review and approve or reject student drop requests</p>
                </div>

                <?php echo $flash; ?>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card red">
                        <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
                        <div class="stat-content">
                            <h3>Pending</h3>
                            <p class="stat-number"><?php echo number_format($total_pending); ?></p>
                            <small>Awaiting approval</small>
                        </div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="fa-solid fa-check-circle"></i></div>
                        <div class="stat-content">
                            <h3>Approved</h3>
                            <p class="stat-number"><?php echo number_format($total_approved); ?></p>
                            <small>Total dropped</small>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="card">
                    <div class="card-header">
                        <h2>Drop Requests</h2>
                        <form method="GET" class="header-search-form">
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                            <div class="search-bar-wrap">
                                <i class="fa-solid fa-search"></i>
                                <input type="text" name="search" placeholder="Search student or subject…" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <button type="submit" class="btn-secondary" style="padding:.45rem .75rem;"><i class="fa-solid fa-search"></i></button>
                            <?php if ($search): ?><a href="?filter=<?php echo $filter; ?>" class="btn-secondary" style="padding:.45rem .75rem;">Clear</a><?php endif; ?>
                        </form>
                    </div>

                    <div class="filter-tabs">
                        <a href="?filter=pending<?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                            Pending <?php if ($total_pending > 0): ?><span class="tab-badge"><?php echo $total_pending; ?></span><?php endif; ?>
                        </a>
                        <a href="?filter=all<?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Student No.</th>
                                    <th>Course / Year</th>
                                    <th>Subject</th>
                                    <th>Section</th>
                                    <th>Schedule</th>
                                    <th>Units</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (mysqli_num_rows($drop_requests) === 0): ?>
                                <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text-label);">No drop requests found.</td></tr>
                            <?php else: ?>
                            <?php while ($dr = mysqli_fetch_assoc($drop_requests)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($dr['first_name'] . ' ' . $dr['last_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($dr['student_number']); ?></td>
                                <td>
                                    <span style="font-size:.82rem;"><?php echo htmlspecialchars($dr['course']); ?></span><br>
                                    <span style="font-size:.75rem;color:var(--text-label);">Year <?php echo $dr['year_level']; ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($dr['subject_code']); ?></strong><br>
                                    <span style="font-size:.78rem;color:var(--text-label);"><?php echo htmlspecialchars($dr['subject_name']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($dr['section'] ?? 'TBA'); ?></td>
                                <td style="font-size:.82rem;">
                                    <?php echo htmlspecialchars(($dr['schedule_day'] ?? '') . ' ' . ($dr['schedule_time'] ?? '')); ?>
                                    <?php if ($dr['room']): ?><br><span style="color:var(--text-label);font-size:.75rem;"><?php echo htmlspecialchars($dr['room']); ?></span><?php endif; ?>
                                </td>
                                <td class="center"><?php echo $dr['units']; ?></td>
                                <td>
                                    <?php if ($dr['status'] === 'drop_requested'): ?>
                                        <span class="badge pending">Pending</span>
                                    <?php elseif ($dr['status'] === 'dropped'): ?>
                                        <span class="badge rejected">Dropped</span>
                                    <?php else: ?>
                                        <span class="badge active"><?php echo ucfirst($dr['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($dr['status'] === 'drop_requested'): ?>
                                    <div class="action-buttons">
                                        <form method="POST" action="../../php/handle_drop_request_v2.php" style="display:inline;">
                                            <input type="hidden" name="student_id"    value="<?php echo $dr['student_id']; ?>">
                                            <input type="hidden" name="enrollment_id" value="<?php echo $dr['enrollment_id']; ?>">
                                            <input type="hidden" name="class_id"      value="<?php echo $dr['class_id']; ?>">
                                            <input type="hidden" name="action"        value="accept">
                                            <input type="hidden" name="redirect"      value="drop_requests">
                                            <button type="submit" class="btn-icon approve" title="Approve"
                                                    onclick="return confirm('Approve drop request for <?php echo htmlspecialchars(addslashes($dr['subject_code'])); ?>?')">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="../../php/handle_drop_request_v2.php" style="display:inline;">
                                            <input type="hidden" name="student_id"    value="<?php echo $dr['student_id']; ?>">
                                            <input type="hidden" name="enrollment_id" value="<?php echo $dr['enrollment_id']; ?>">
                                            <input type="hidden" name="action"        value="reject">
                                            <input type="hidden" name="redirect"      value="drop_requests">
                                            <button type="submit" class="btn-icon danger" title="Reject"
                                                    onclick="return confirm('Reject drop request for <?php echo htmlspecialchars(addslashes($dr['subject_code'])); ?>?')">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </form>
                                        <a href="admin_manual_enroll.php?student_id=<?php echo $dr['student_id']; ?>" class="btn-icon" title="View Student">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    </div>
                                    <?php else: ?>
                                        <a href="admin_manual_enroll.php?student_id=<?php echo $dr['student_id']; ?>" class="btn-icon" title="View Student">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script src="../../js/admin/admin_main.js"></script>
</body>
</html>
