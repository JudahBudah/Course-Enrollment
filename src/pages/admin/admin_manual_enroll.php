<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);

$student_id = $_GET['student_id'] ?? 0;

// Get student info
$student_query = mysqli_query($con, "SELECT * FROM students WHERE student_id = $student_id");
$student = mysqli_fetch_assoc($student_query);

if (!$student) {
    header("Location: admin_students.php");
    exit;
}

// Check if enrollments table exists
$tables_check = mysqli_query($con, "SHOW TABLES LIKE 'enrollments'");
$enrollments_exists = mysqli_num_rows($tables_check) > 0;

// Check if classes table exists
$classes_check = mysqli_query($con, "SHOW TABLES LIKE 'classes'");
$classes_exists = mysqli_num_rows($classes_check) > 0;

// Get student's current enrollments split by status
if ($enrollments_exists && $classes_exists) {
    $reserved_query = mysqli_query($con, "
        SELECT e.enrollment_id, e.status, c.class_id, c.section, c.schedule_day,
               c.schedule_time, c.room, c.max_slots, c.enrolled_count,
               s.subject_code, s.subject_name, s.units,
               CONCAT(f.first_name, ' ', f.last_name) as faculty_name
        FROM enrollments e
        JOIN classes c ON e.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE e.student_id = $student_id AND e.status = 'reserved'
        ORDER BY s.subject_code
    ");

    $drop_requests_query = mysqli_query($con, "
        SELECT e.enrollment_id, e.status, c.class_id, c.section, c.schedule_day,
               c.schedule_time, c.room,
               s.subject_code, s.subject_name, s.units,
               CONCAT(f.first_name, ' ', f.last_name) as faculty_name
        FROM enrollments e
        JOIN classes c ON e.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE e.student_id = $student_id AND e.status = 'drop_requested'
        ORDER BY s.subject_code
    ");

    $enrolled_query = mysqli_query($con, "
        SELECT e.enrollment_id, e.status, c.class_id, c.section, c.schedule_day,
               c.schedule_time, c.room,
               s.subject_code, s.subject_name, s.units,
               CONCAT(f.first_name, ' ', f.last_name) as faculty_name
        FROM enrollments e
        JOIN classes c ON e.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE e.student_id = $student_id AND e.status IN ('confirmed','ongoing')
        ORDER BY s.subject_code
    ");

    $available_query = mysqli_query($con, "
        SELECT c.class_id, c.section, c.schedule_day, c.schedule_time, c.room,
               c.max_slots, c.enrolled_count,
               s.subject_code, s.subject_name, s.units,
               CONCAT(f.first_name, ' ', f.last_name) as faculty_name
        FROM classes c
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE c.class_id NOT IN (
            SELECT class_id FROM enrollments
            WHERE student_id = $student_id
            AND status IN ('reserved','confirmed','ongoing')
        )
        AND c.status = 'open'
        AND c.enrolled_count < c.max_slots
        ORDER BY s.subject_code, c.section
    ");
} else {
    $reserved_query = false;
    $drop_requests_query = false;
    $enrolled_query = false;
    $available_query = false;
}

// Count stats
$reserved_count      = $reserved_query      ? mysqli_num_rows($reserved_query)      : 0;
$drop_requests_count = $drop_requests_query ? mysqli_num_rows($drop_requests_query) : 0;
$enrolled_count      = $enrolled_query      ? mysqli_num_rows($enrolled_query)      : 0;
$total_units = 0;
if ($enrolled_query && $enrolled_count > 0) {
    while ($r = mysqli_fetch_assoc($enrolled_query)) $total_units += $r['units'];
    mysqli_data_seek($enrolled_query, 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Enrollment - PLM Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin.css">
</head>
<body class="dashboard">
    <nav class="dashboard-nav">
        <div class="nav-brand">
            <img src="../../assets/plm-logo.png" alt="PLM">
            <span>PLM Admin Portal</span>
        </div>
        <div class="nav-user">
            <span><?php echo htmlspecialchars(($admin_data['username'] ?? 'Admin')); ?></span>
            <div class="user-avatar"><?php echo strtoupper(substr($admin_data['username'] ?? 'A', 0, 1)); ?></div>
        </div>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <a href="admin_home.php" class="sidebar-link">
                <i class="fa-solid fa-house"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin_applicants.php" class="sidebar-link">
                <i class="fa-solid fa-user-plus"></i>
                <span>Applicants</span>
            </a>
            <a href="admin_students.php" class="sidebar-link active">
                <i class="fa-solid fa-users"></i>
                <span>Students</span>
            </a>
            <a href="admin_blocks.php" class="sidebar-link">
                <i class="fa-solid fa-layer-group"></i>
                <span>Blocks</span>
            </a>
            <a href="admin_faculty.php" class="sidebar-link">
                <i class="fa-solid fa-chalkboard-user"></i>
                <span>Faculty</span>
            </a>
            <a href="admin_subjects.php" class="sidebar-link">
                <i class="fa-solid fa-book"></i>
                <span>Subjects</span>
            </a>
            <a href="admin_classes.php" class="sidebar-link">
                <i class="fa-solid fa-door-open"></i>
                <span>Classes</span>
            </a>
            <a href="admin_enrollments.php" class="sidebar-link">
                <i class="fa-solid fa-file-lines"></i>
                <span>Enrollments</span>
            </a>
            <a href="admin_announcements.php" class="sidebar-link">
                <i class="fa-solid fa-bullhorn"></i>
                <span>Announcements</span>
            </a>
            <a href="../../php/admin_logout.php" class="sidebar-link logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Manual Enrollment</h1>
                <p><strong><?php echo htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?></strong> (<?php echo htmlspecialchars($student['student_id']); ?>)</p>
                <p><?php echo htmlspecialchars($student['course'] ?? 'N/A'); ?> | Year <?php echo htmlspecialchars($student['year_level'] ?? 'N/A'); ?> | 
                <?php if ($student['block_id']): ?>
                    Block: <?php 
                    $block_info = mysqli_fetch_assoc(mysqli_query($con, "SELECT block_name FROM blocks WHERE block_id = {$student['block_id']}"));
                    echo htmlspecialchars($block_info['block_name'] ?? 'N/A');
                    ?>
                <?php else: ?>
                    <span style="color: var(--gold);">Irregular Student (No Block)</span>
                <?php endif; ?>
                </p>
                <a href="admin_students.php" class="link"><i class="fa-solid fa-arrow-left"></i> Back to Students</a>
            </div>

            <?php if (!isset($student['block_id']) || !$student['block_id']): ?>
            <div class="info-message">
                <i class="fa-solid fa-info-circle"></i>
                This is an irregular student without a block assignment. Use manual enrollment to add subjects individually.
            </div>
            <?php endif; ?>

            <div class="stats-grid" style="margin-bottom: 2rem;">
                <div class="stat-card gold">
                    <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
                    <div class="stat-content">
                        <h3>Pending Confirmation</h3>
                        <p class="stat-number"><?php echo $reserved_count; ?></p>
                    </div>
                </div>
                <div class="stat-card red">
                    <div class="stat-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
                    <div class="stat-content">
                        <h3>Drop Requests</h3>
                        <p class="stat-number"><?php echo $drop_requests_count; ?></p>
                    </div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="fa-solid fa-book"></i></div>
                    <div class="stat-content">
                        <h3>Enrolled Subjects</h3>
                        <p class="stat-number"><?php echo $enrolled_count; ?></p>
                    </div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon"><i class="fa-solid fa-calculator"></i></div>
                    <div class="stat-content">
                        <h3>Total Units</h3>
                        <p class="stat-number"><?php echo $total_units; ?></p>
                    </div>
                </div>
                <div class="stat-card <?php echo $total_units > 24 ? 'red' : 'navy'; ?>">
                    <div class="stat-icon"><i class="fa-solid fa-gauge"></i></div>
                    <div class="stat-content">
                        <h3>Load Status</h3>
                        <p class="stat-number"><?php echo $total_units <= 24 ? 'Normal' : 'Overload'; ?></p>
                        <small>Max: 24 units</small>
                    </div>
                </div>
            </div>

            <?php if (!$enrollments_exists || !$classes_exists): ?>
            <div class="error-message">
                <i class="fa-solid fa-exclamation-triangle"></i>
                <strong>Required tables not found!</strong> Please create the following tables first:
                <?php if (!$classes_exists): ?>
                    <br>• classes table (run classes_table.sql)
                    <br>• subjects table (run subjects_table.sql)
                    <br>• faculty table (run faculty_table.sql)
                <?php endif; ?>
                <?php if (!$enrollments_exists): ?>
                    <br>• enrollments table (run enrollments_table.sql)
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- PENDING CONFIRMATION TABLE -->
            <?php
            // Always show the section, even if empty, so admin knows the state
            $reserved_rows = [];
            if ($reserved_query) {
                while ($r = mysqli_fetch_assoc($reserved_query)) $reserved_rows[] = $r;
            }
            // DEBUG — remove after fixing
            if (empty($reserved_rows)) {
                // Re-run query to check raw DB status
                $debug = mysqli_query($con, "SELECT enrollment_id, status FROM enrollments WHERE student_id = $student_id");
                $debug_rows = [];
                while ($d = mysqli_fetch_assoc($debug)) $debug_rows[] = $d;
            }
            ?>
            <div class="card" style="margin-bottom:1.5rem;padding:0;overflow:hidden;">
                <div class="card-header" style="background:var(--gold);margin-bottom:0;border-bottom:none;padding:1rem 1.5rem;">
                    <h2 style="color:var(--navy);font-size:1rem;"><i class="fa-solid fa-clock"></i> Pending Student Confirmation (<?php echo count($reserved_rows); ?>)</h2>
                </div>
                <?php if (isset($debug_rows)): ?>
                <div style="padding:1rem;background:rgba(239,68,68,0.1);font-size:0.8rem;color:#ef4444;">
                    <strong>Debug:</strong> No reserved rows found. Raw enrollment statuses for this student:
                    <?php foreach ($debug_rows as $d): ?>
                        <span style="margin-left:8px;background:#333;padding:2px 6px;border-radius:3px;">
                            #<?php echo $d['enrollment_id']; ?> = <b><?php echo htmlspecialchars($d['status']); ?></b>
                        </span>
                    <?php endforeach; ?>
                    <?php if (empty($debug_rows)): ?>
                        <span>No enrollments at all for this student.</span>
                    <?php endif; ?>
                    <br><small>Run <a href="check_enrollment_status.php?student_id=<?php echo $student_id; ?>" style="color:#60a5fa;">check_enrollment_status.php</a> to fix the ENUM.</small>
                </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Units</th>
                                <th>Section</th>
                                <th>Schedule</th>
                                <th>Instructor</th>
                                <th>Room</th>
                                <th>Slots</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reserved_rows)): ?>
                                <tr><td colspan="9" style="text-align:center;color:rgba(0,0,0,0.4);padding:1.5rem;">No pending reservations.</td></tr>
                            <?php else: ?>
                            <?php foreach ($reserved_rows as $r): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($r['subject_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($r['subject_name']); ?></td>
                                <td><?php echo $r['units']; ?></td>
                                <td><?php echo htmlspecialchars($r['section'] ?? 'TBA'); ?></td>
                                <td><?php echo htmlspecialchars(($r['schedule_day'] ?? 'TBA') . ' ' . ($r['schedule_time'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($r['faculty_name'] ?? 'TBA'); ?></td>
                                <td><?php echo htmlspecialchars($r['room'] ?? 'TBA'); ?></td>
                                <td><?php echo $r['enrolled_count'] . '/' . $r['max_slots']; ?></td>
                                <td>
                                    <form method="POST" action="../../php/drop_enrollment.php" style="display:inline;">
                                        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                        <input type="hidden" name="enrollment_id" value="<?php echo $r['enrollment_id']; ?>">
                                        <input type="hidden" name="class_id" value="<?php echo $r['class_id']; ?>">
                                        <button type="submit" class="btn-icon" title="Cancel Reservation" onclick="return confirm('Cancel this reservation?')" style="color:#ef4444;border-color:#ef4444;">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="content-grid">
                <div class="card">
                    <div class="card-header"><h2>Confirmed Enrollment</h2></div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Name</th>
                                    <th>Units</th>
                                    <th>Section</th>
                                    <th>Schedule</th>
                                    <th>Instructor</th>
                                    <th>Room</th>
                                    <th>Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($enrolled_count === 0): ?>
                                    <tr><td colspan="9" style="text-align:center;color:rgba(242,243,242,0.4);padding:1.5rem;">No confirmed enrollments yet.</td></tr>
                                <?php else: ?>
                                <?php while ($subject = mysqli_fetch_assoc($enrolled_query)):
                                    $s = $subject['status'];
                                    $colors = ['confirmed' => '#16a34a', 'ongoing' => '#2563eb'];
                                    $labels = ['confirmed' => 'Confirmed', 'ongoing' => 'Self Enrolled'];
                                    $color = $colors[$s] ?? '#888';
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                    <td><?php echo $subject['units']; ?></td>
                                    <td><?php echo htmlspecialchars($subject['section'] ?? 'TBA'); ?></td>
                                    <td><?php echo htmlspecialchars(($subject['schedule_day'] ?? 'TBA') . ' ' . ($subject['schedule_time'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($subject['faculty_name'] ?? 'TBA'); ?></td>
                                    <td><?php echo htmlspecialchars($subject['room'] ?? 'TBA'); ?></td>
                                    <td>
                                        <span style="background:<?php echo $color; ?>22;color:<?php echo $color; ?>;padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:600;">
                                            <?php echo $labels[$s] ?? ucfirst($s); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="../../php/drop_enrollment.php" style="display:inline;">
                                            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                            <input type="hidden" name="enrollment_id" value="<?php echo $subject['enrollment_id']; ?>">
                                            <input type="hidden" name="class_id" value="<?php echo $subject['class_id']; ?>">
                                            <button type="submit" class="btn-icon" title="Drop" onclick="return confirm('Drop this subject?')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ADD SUBJECT -->
                <div class="card">
                    <div class="card-header"><h2>Reserve Subject for Student</h2></div>
                    <form method="POST" action="../../php/manual_enroll.php">
                        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                        <div class="form-group" style="padding: 20px;">
                            <label>Select Class to Reserve</label>
                            <select name="class_id" id="classSelect" <?php echo (!$available_query) ? 'disabled' : 'required'; ?> style="width:100%;padding:0.75rem;background:var(--gray-lt);border:1px solid rgba(212,175,55,0.2);color:var(--white);">
                                <option value=""><?php echo (!$available_query) ? 'No classes available' : 'Choose a class...'; ?></option>
                                <?php if ($available_query): ?>
                                    <?php while ($class = mysqli_fetch_assoc($available_query)): ?>
                                    <option value="<?php echo $class['class_id']; ?>" data-units="<?php echo $class['units']; ?>">
                                        <?php echo htmlspecialchars(
                                            $class['subject_code'] . ' - ' . $class['subject_name'] .
                                            ' (' . $class['units'] . ' units) | ' .
                                            $class['section'] . ' | ' .
                                            ($class['schedule_day'] ?? 'TBA') . ' ' . ($class['schedule_time'] ?? '') . ' | ' .
                                            ($class['room'] ?? 'TBA') . ' | ' .
                                            $class['enrolled_count'] . '/' . $class['max_slots']
                                        ); ?>
                                    </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>

                            <div id="unitWarning" style="display:none;margin-top:1rem;padding:0.75rem;background:rgba(239,68,68,0.2);border:1px solid rgba(239,68,68,0.3);border-radius:4px;color:#ef4444;">
                                <i class="fa-solid fa-exclamation-triangle"></i> Warning: Total units will exceed 24!
                            </div>

                            <button type="submit" class="btn-submit" style="margin-top:1rem;width:100%;" <?php echo (!$available_query) ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-bookmark"></i> Reserve for Student
                            </button>
                        </div>
                    </form>
                    <div style="padding:0 20px 20px;">
                        <p style="font-size:0.85rem;color:rgba(242,243,242,0.5);">
                            <i class="fa-solid fa-info-circle"></i> This creates a reservation. The student must confirm it on their portal.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const classSelect = document.getElementById('classSelect');
        const unitWarning = document.getElementById('unitWarning');
        const currentUnits = <?php echo $total_units; ?>;

        classSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const units = parseInt(selectedOption.getAttribute('data-units') || 0);
            const totalUnits = currentUnits + units;

            if (totalUnits > 24) {
                unitWarning.style.display = 'block';
                unitWarning.innerHTML = '<i class="fa-solid fa-exclamation-triangle"></i> Warning: Total units will be ' + totalUnits + ' (exceeds 24 unit limit)';
            } else {
                unitWarning.style.display = 'none';
            }
        });
    </script>
</body>
</html>







