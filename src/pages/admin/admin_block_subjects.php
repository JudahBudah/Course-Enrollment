<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status='pending'"))['c'];

$block_id = (int)($_GET['block_id'] ?? 0);

// Get block info
$block = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM blocks WHERE block_id = $block_id"));
if (!$block) {
    header("Location: admin_blocks.php");
    exit;
}

// Subjects assigned to this block
$assigned_query = mysqli_query($con, "
    SELECT bs.*, c.*, s.subject_code, s.subject_name, s.units,
           f.first_name, f.last_name
    FROM block_subjects bs
    JOIN classes c ON bs.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    WHERE bs.block_id = $block_id
");

// Available classes not yet assigned to this block
$sy_esc  = mysqli_real_escape_string($con, $block['school_year']);
$sem_esc = mysqli_real_escape_string($con, $block['semester']);
$course_esc = mysqli_real_escape_string($con, $block['course']);
$year_esc = (int)$block['year_level'];

$available_query = mysqli_query($con, "
    SELECT c.*, s.subject_code, s.subject_name, s.units, s.year_level as subject_year,
           f.first_name, f.last_name, co.course_code
    FROM classes c
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN courses co ON s.course_id = co.course_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    WHERE c.class_id NOT IN (
        SELECT class_id FROM block_subjects WHERE block_id = $block_id
    )
    AND c.school_year = '$sy_esc'
    AND c.semester    = '$sem_esc'
    AND c.status      = 'open'
    AND (co.course_code = '$course_esc' OR s.course_id IS NULL)
    AND (s.year_level = $year_esc OR s.year_level IS NULL)
    ORDER BY s.subject_code
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Block Subjects - PLM Admin</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/admin_block_subjects.css">
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
                        <a href="admin_blocks.php" class="active">
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
                        <a href="admin_drop_requests.php">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span class="li-name">Drop Requests</span>
                            <?php if (!empty($GLOBALS['pending_drops'])): ?><span class="sidebar-badge li-name"><?php echo $GLOBALS['pending_drops']; ?></span><?php endif; ?>
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
                        <a href="admin_accounts.php">
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

                <!-- Page Header -->
                <div class="page-header">
                    <h1>Block <?php echo htmlspecialchars($block['block_name']); ?> — Subjects</h1>
                    <p class="block-meta">
                        <?php echo htmlspecialchars($block['course']); ?> |
                        Year <?php echo htmlspecialchars($block['year_level']); ?> |
                        <?php echo htmlspecialchars($block['semester']); ?> Semester
                        <?php echo htmlspecialchars($block['school_year']); ?>
                    </p>
                    <a href="admin_blocks.php" class="back-link">
                        <i class="fa-solid fa-arrow-left"></i> Back to Blocks
                    </a>
                </div>

                <div class="content-grid">

                    <!-- ── Assigned Subjects Table ──────── -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Assigned Subjects</h2>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Subject Code</th>
                                        <th>Subject Name</th>
                                        <th>Units</th>
                                        <th>Schedule</th>
                                        <th>Instructor</th>
                                        <th>Room</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($assigned_query) > 0): ?>
                                        <?php while ($sub = mysqli_fetch_assoc($assigned_query)): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($sub['subject_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($sub['units']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars(
                                                    trim(($sub['schedule_day'] ?? '') . ' ' . ($sub['schedule_time'] ?? '')) ?: 'TBA'
                                                ); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $fname = $sub['first_name'] ?? '';
                                                $lname = $sub['last_name']  ?? '';
                                                echo htmlspecialchars(trim("$fname $lname") ?: 'TBA');
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($sub['room'] ?? 'TBA'); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <form method="POST" action="../../php/remove_block_subject.php"
                                                        style="display:inline;">
                                                        <input type="hidden" name="block_id" value="<?php echo $block_id; ?>">
                                                        <input type="hidden" name="class_id" value="<?php echo $sub['class_id']; ?>">
                                                        <button type="submit" class="btn-icon remove" title="Remove"
                                                                onclick="return confirm('Remove this subject from block?')">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" style="text-align:center;color:var(--text-label);padding:1.5rem;">
                                                No subjects assigned yet.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ── Add Subject Panel ────────────── -->
                    <div class="card">
                        <div class="card-header"><h2>Add Subject</h2></div>
                        <form method="POST" action="../../php/add_block_subject.php">
                            <input type="hidden" name="block_id" value="<?php echo $block_id; ?>">
                            <div class="add-subject-body">
                                <label>Select Class to Add</label>
                                <?php if (mysqli_num_rows($available_query) > 0): ?>
                                    <select name="class_id" required>
                                        <option value="">Choose a class…</option>
                                        <?php while ($cls = mysqli_fetch_assoc($available_query)): ?>
                                            <option value="<?php echo $cls['class_id']; ?>">
                                                <?php echo htmlspecialchars(
                                                    $cls['subject_code'] . ' — ' . $cls['subject_name'] .
                                                    ' | Sec ' . $cls['section'] . ' | ' .
                                                    ($cls['schedule_day'] ?? 'TBA') . ' ' . ($cls['schedule_time'] ?? '')
                                                ); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <button type="submit" class="btn-add-subject">
                                        <i class="fa-solid fa-plus"></i> Add Subject to Block
                                    </button>
                                <?php else: ?>
                                    <div class="no-classes-message">
                                        <i class="fa-solid fa-info-circle"></i>
                                        <p><strong>No available classes found</strong></p>
                                        <p>To add subjects to this block, you need to create classes that match:</p>
                                        <ul>
                                            <li>Course: <strong><?php echo htmlspecialchars($block['course']); ?></strong></li>
                                            <li>Year Level: <strong><?php echo htmlspecialchars($block['year_level']); ?></strong></li>
                                            <li>Semester: <strong><?php echo htmlspecialchars($block['semester']); ?></strong></li>
                                            <li>School Year: <strong><?php echo htmlspecialchars($block['school_year']); ?></strong></li>
                                            <li>Status: <strong>Open</strong></li>
                                        </ul>
                                        <p>Go to <a href="admin_classes.php" style="color:var(--primary-color);font-weight:600;">Classes Management</a> to create classes for <?php echo htmlspecialchars($block['course']); ?> subjects.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                </div><!-- /.content-grid -->

            </div><!-- /.main-content -->
        </main>
    </div><!-- /.main-flex -->

    <script src="../../js/admin/admin_main.js"></script>
</body>
</html>