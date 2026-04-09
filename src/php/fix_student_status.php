<?php
include("connection.php");
include("enrollment_status_helper.php");

echo "<h2>Database Status Check & Fix</h2>";
echo "<style>
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .error { background-color: #ffcccc; }
    .success { background-color: #ccffcc; }
    .warning { background-color: #ffffcc; }
</style>";

// Check all students and their enrollment status
$query = "
    SELECT 
        s.student_id,
        s.student_number,
        s.first_name,
        s.last_name,
        s.status as student_status,
        COUNT(e.enrollment_id) as total_enrollments,
        SUM(CASE WHEN e.status IN ('reserved','confirmed','ongoing') THEN 1 ELSE 0 END) as active_enrollments,
        SUM(CASE WHEN e.status = 'dropped' THEN 1 ELSE 0 END) as dropped_enrollments,
        SUM(CASE WHEN e.status = 'drop_requested' THEN 1 ELSE 0 END) as drop_requested
    FROM students s
    LEFT JOIN enrollments e ON s.student_id = e.student_id
    GROUP BY s.student_id
    ORDER BY s.student_id
";

$result = mysqli_query($con, $query);

echo "<h3>Current Student Status vs Actual Enrollments</h3>";
echo "<table>";
echo "<tr>
    <th>Student ID</th>
    <th>Name</th>
    <th>Current Status</th>
    <th>Active Enrollments</th>
    <th>Dropped</th>
    <th>Drop Requested</th>
    <th>Expected Status</th>
    <th>Match?</th>
    <th>Action</th>
</tr>";

$mismatches = [];

while ($row = mysqli_fetch_assoc($result)) {
    $expected_status = ($row['active_enrollments'] > 0) ? 'Enrolled' : 'Dropped';
    $matches = ($row['student_status'] == $expected_status);
    $row_class = $matches ? 'success' : 'error';
    
    if (!$matches) {
        $mismatches[] = $row['student_id'];
    }
    
    echo "<tr class='$row_class'>";
    echo "<td>" . $row['student_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
    echo "<td><strong>" . $row['student_status'] . "</strong></td>";
    echo "<td>" . $row['active_enrollments'] . "</td>";
    echo "<td>" . $row['dropped_enrollments'] . "</td>";
    echo "<td>" . $row['drop_requested'] . "</td>";
    echo "<td><strong>" . $expected_status . "</strong></td>";
    echo "<td>" . ($matches ? '✓' : '✗') . "</td>";
    echo "<td>";
    if (!$matches) {
        echo "<a href='?fix=" . $row['student_id'] . "' style='color: blue;'>Fix This</a>";
    }
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

// If fix parameter is provided, fix that student
if (isset($_GET['fix'])) {
    $fix_id = (int)$_GET['fix'];
    echo "<h3>Fixing Student ID: $fix_id</h3>";
    
    $result = update_student_enrollment_status($con, $fix_id);
    
    echo "<div class='success'>";
    echo "Updated student status to: <strong>{$result['status']}</strong><br>";
    echo "Rows affected: " . ($result['updated'] ? 'Yes' : 'No') . "<br>";
    echo "<a href='fix_student_status.php'>Refresh to see changes</a>";
    echo "</div>";
}

// Fix all button
if (count($mismatches) > 0) {
    echo "<h3>Found " . count($mismatches) . " mismatches</h3>";
    echo "<a href='?fix_all=1' style='padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Fix All Mismatches</a>";
}

if (isset($_GET['fix_all'])) {
    echo "<h3>Fixing All Mismatches...</h3>";
    
    $fixed = 0;
    foreach ($mismatches as $student_id) {
        $result = update_student_enrollment_status($con, $student_id);
        if ($result['updated']) {
            $fixed++;
        }
        echo "Student ID $student_id: {$result['status']}<br>";
    }
    
    echo "<div class='success'>";
    echo "<strong>Fixed $fixed students</strong><br>";
    echo "<a href='fix_student_status.php'>Refresh to verify</a>";
    echo "</div>";
}

// Show detailed enrollment records
echo "<hr><h3>Detailed Enrollment Records</h3>";
$enrollments = mysqli_query($con, "
    SELECT 
        e.enrollment_id,
        e.student_id,
        e.status as enrollment_status,
        s.student_number,
        s.first_name,
        s.last_name,
        s.status as student_status,
        sub.subject_code,
        sub.subject_name
    FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    JOIN classes c ON e.class_id = c.class_id
    JOIN subjects sub ON c.subject_id = sub.subject_id
    ORDER BY e.student_id, e.enrollment_id DESC
    LIMIT 50
");

echo "<table>";
echo "<tr>
    <th>Enrollment ID</th>
    <th>Student ID</th>
    <th>Student Name</th>
    <th>Subject</th>
    <th>Enrollment Status</th>
    <th>Student Status</th>
</tr>";

while ($enroll = mysqli_fetch_assoc($enrollments)) {
    $color = '';
    if ($enroll['enrollment_status'] == 'dropped') $color = 'background:#ffcccc;';
    if ($enroll['enrollment_status'] == 'drop_requested') $color = 'background:#ffffcc;';
    if (in_array($enroll['enrollment_status'], ['confirmed', 'ongoing'])) $color = 'background:#ccffcc;';
    
    echo "<tr style='$color'>";
    echo "<td>" . $enroll['enrollment_id'] . "</td>";
    echo "<td>" . $enroll['student_id'] . "</td>";
    echo "<td>" . htmlspecialchars($enroll['first_name'] . ' ' . $enroll['last_name']) . "</td>";
    echo "<td>" . htmlspecialchars($enroll['subject_code'] . ' - ' . $enroll['subject_name']) . "</td>";
    echo "<td><strong>" . $enroll['enrollment_status'] . "</strong></td>";
    echo "<td><strong>" . $enroll['student_status'] . "</strong></td>";
    echo "</tr>";
}

echo "</table>";

// Test the helper function directly
echo "<hr><h3>Test Helper Function</h3>";
echo "<form method='GET'>";
echo "Student ID: <input type='number' name='test_student' value='" . ($_GET['test_student'] ?? '') . "'>";
echo "<button type='submit'>Test</button>";
echo "</form>";

if (isset($_GET['test_student'])) {
    $test_id = (int)$_GET['test_student'];
    echo "<h4>Testing student ID: $test_id</h4>";
    
    // Check active enrollments
    $check = mysqli_query($con, "
        SELECT COUNT(*) as active_count 
        FROM enrollments 
        WHERE student_id = $test_id 
        AND status IN ('reserved','confirmed','ongoing')
    ");
    $active = mysqli_fetch_assoc($check)['active_count'];
    
    echo "Active enrollments: <strong>$active</strong><br>";
    echo "Expected status: <strong>" . ($active > 0 ? 'Enrolled' : 'Dropped') . "</strong><br><br>";
    
    // Run the helper function
    $result = update_student_enrollment_status($con, $test_id);
    
    echo "Helper function result:<br>";
    echo "- New status: <strong>{$result['status']}</strong><br>";
    echo "- Updated: " . ($result['updated'] ? 'Yes' : 'No') . "<br>";
    
    // Check current status in database
    $current = mysqli_fetch_assoc(mysqli_query($con, "SELECT status FROM students WHERE student_id = $test_id"));
    echo "- Current status in DB: <strong>{$current['status']}</strong><br>";
}
?>
