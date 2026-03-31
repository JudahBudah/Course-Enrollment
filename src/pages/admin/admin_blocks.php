<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);

// Get all blocks
$blocks_query = mysqli_query($con, "SELECT * FROM blocks ORDER BY course, year_level, block_name");

// Get statistics
$total_students = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM students"))['count'];
$total_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM applicants"))['count'];
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM applicants WHERE application_status = 'pending'"))['count'];
$total_faculty = 0; // Faculty table not yet created
$total_subjects = 0; // Subjects table not yet created
$total_classes = 0; // Classes table not yet created

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blocks Management - PLM Admin</title>
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
                <?php if ($pending_applicants > 0): ?>
                    <span class="badge"><?php echo $pending_applicants; ?></span>
                <?php endif; ?>
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
            <a href="admin_calendar.php" class="sidebar-link">
                <i class="fa-solid fa-calendar-days"></i>
                <span>Calendar</span>
            </a>
            <a href="admin_accounts.php" class="sidebar-link">
                <i class="fa-solid fa-user-shield"></i>
                <span>Admin Accounts</span>
            </a>
            <a href="../../php/admin_logout.php" class="sidebar-link logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Blocks Management</h1>
                <p>Create and manage student blocks with assigned subjects</p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>All Blocks</h2>
                    <button class="btn-secondary" onclick="document.getElementById('createBlockModal').style.display='block'">
                        <i class="fa-solid fa-plus"></i> Create New Block
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Block Name</th>
                                <th>Course</th>
                                <th>Year Level</th>
                                <th>Semester</th>
                                <th>School Year</th>
                                <th>Students</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($block = mysqli_fetch_assoc($blocks_query)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($block['block_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($block['course']); ?></td>
                                <td><?php echo htmlspecialchars($block['year_level']); ?></td>
                                <td><?php echo htmlspecialchars($block['semester']); ?></td>
                                <td><?php echo htmlspecialchars($block['school_year']); ?></td>
                                <td><?php echo $block['current_students']; ?> / <?php echo $block['max_students']; ?></td>
                                <td><span class="badge <?php echo strtolower($block['status']); ?>"><?php echo htmlspecialchars(ucfirst($block['status'])); ?></span></td>
                                <td>
                                    <a href="admin_block_subjects.php?block_id=<?php echo $block['block_id']; ?>" class="btn-icon" title="Manage Subjects">
                                        <i class="fa-solid fa-book"></i>
                                    </a>
                                    <a href="admin_block_students.php?block_id=<?php echo $block['block_id']; ?>" class="btn-icon" title="View Students">
                                        <i class="fa-solid fa-users"></i>
                                    </a>
                                    <button class="btn-icon" title="Edit" onclick="editBlock(<?php echo $block['block_id']; ?>)">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Block Modal -->
    <div id="createBlockModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('createBlockModal').style.display='none'">&times;</span>
            <h2>Create New Block</h2>
            <form method="POST" action="../../php/create_block.php">
                <div class="form-group">
                    <label>Block Name</label>
                    <input type="text" name="block_name" placeholder="e.g., 1A, 1B, 2A" required>
                </div>
                <div class="form-group">
                    <label>Course</label>
                    <select name="course" required>
                        <option value="">Select Course</option>
                        <option value="BS Computer Science">BS Computer Science</option>
                        <option value="BS Information Technology">BS Information Technology</option>
                        <option value="BS Business Administration">BS Business Administration</option>
                        <option value="BS Accountancy">BS Accountancy</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Year Level</label>
                    <select name="year_level" required>
                        <option value="">Select Year</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <select name="semester" required>
                        <option value="">Select Semester</option>
                        <option value="1st">1st Semester</option>
                        <option value="2nd">2nd Semester</option>
                        <option value="summer">Summer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>School Year</label>
                    <input type="text" name="school_year" placeholder="e.g., 2024-2025" required>
                </div>
                <div class="form-group">
                    <label>Max Students</label>
                    <input type="number" name="max_students" value="40" min="1" required>
                </div>
                <button type="submit" class="btn-submit">Create Block</button>
            </form>
        </div>
    </div>

    <script>
        window.onclick = function(event) {
            const modal = document.getElementById('createBlockModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>







