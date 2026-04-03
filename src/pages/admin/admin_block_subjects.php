<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);

$block_id = $_GET['block_id'] ?? 0;

// Get block info
$block_query = mysqli_query($con, "SELECT * FROM blocks WHERE block_id = $block_id");
$block = mysqli_fetch_assoc($block_query);

if (!$block) {
    header("Location: admin_blocks.php");
    exit;
}

// Get subjects assigned to this block
$assigned_query = mysqli_query($con, "
    SELECT bs.*, c.*, s.subject_code, s.subject_name, s.units, 
           f.first_name, f.last_name
    FROM block_subjects bs
    JOIN classes c ON bs.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    WHERE bs.block_id = $block_id
");

// Get available classes not yet assigned to this block
$available_query = mysqli_query($con, "
    SELECT c.*, s.subject_code, s.subject_name, s.units,
           f.first_name, f.last_name
    FROM classes c
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    WHERE c.class_id NOT IN (
        SELECT class_id FROM block_subjects WHERE block_id = $block_id
    )
    AND c.school_year = '{$block['school_year']}'
    AND c.semester = '{$block['semester']}'
    AND c.status = 'open'
    ORDER BY s.subject_code
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Block Subjects - PLM Admin</title>
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
            <a href="admin_students.php" class="sidebar-link">
                <i class="fa-solid fa-users"></i>
                <span>Students</span>
            </a>
            <a href="admin_blocks.php" class="sidebar-link active">
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
                <h1>Block <?php echo htmlspecialchars($block['block_name']); ?> - Subjects</h1>
                <p><?php echo htmlspecialchars($block['course']); ?> | Year <?php echo $block['year_level']; ?> | <?php echo $block['semester']; ?> Semester <?php echo $block['school_year']; ?></p>
                <a href="admin_blocks.php" class="link"><i class="fa-solid fa-arrow-left"></i> Back to Blocks</a>
            </div>

            <div class="content-grid">
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
                                    <?php while ($subject = mysqli_fetch_assoc($assigned_query)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                        <td><?php echo $subject['units']; ?></td>
                                        <td><?php echo htmlspecialchars($subject['schedule_day'] . ' ' . $subject['schedule_time']); ?></td>
                                        <td><?php echo htmlspecialchars(($subject['first_name'] ?? '') . ' ' . ($subject['last_name'] ?? 'TBA')); ?></td>
                                        <td><?php echo htmlspecialchars($subject['room'] ?? 'TBA'); ?></td>
                                        <td>
                                            <form method="POST" action="../../php/remove_block_subject.php" style="display:inline;">
                                                <input type="hidden" name="block_id" value="<?php echo $block_id; ?>">
                                                <input type="hidden" name="class_id" value="<?php echo $subject['class_id']; ?>">
                                                <button type="submit" class="btn-icon" title="Remove" onclick="return confirm('Remove this subject from block?')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center; color: rgba(242,243,242,0.5);">No subjects assigned yet</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Add Subjects</h2>
                    </div>
                    <form method="POST" action="../../php/add_block_subject.php">
                        <input type="hidden" name="block_id" value="<?php echo $block_id; ?>">
                        <div class="form-group" style="padding: 20px;">
                            <label>Select Class to Add</label>
                            <select name="class_id" required style="width: 100%; padding: 0.75rem; background: var(--gray-lt); border: 1px solid rgba(212,175,55,0.2); color: var(--white);">
                                <option value="">Choose a class...</option>
                                <?php while ($class = mysqli_fetch_assoc($available_query)): ?>
                                    <option value="<?php echo $class['class_id']; ?>">
                                        <?php echo htmlspecialchars($class['subject_code'] . ' - ' . $class['subject_name'] . ' | Section ' . $class['section'] . ' | ' . $class['schedule_day'] . ' ' . $class['schedule_time']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <button type="submit" class="btn-primary" style="margin-top: 1rem; width: 100%;">
                                <i class="fa-solid fa-plus"></i> Add Subject to Block
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>







