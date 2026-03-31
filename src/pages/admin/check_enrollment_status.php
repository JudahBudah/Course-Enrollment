<?php
include("../../php/connection.php");
$student_id = (int)($_GET['student_id'] ?? 1);

echo "<style>body{font-family:sans-serif;padding:2rem;} pre{background:#f5f5f5;padding:1rem;} .ok{color:green;} .err{color:red;}</style>";

// 1. Check ENUM
$result = mysqli_query($con, "SHOW COLUMNS FROM enrollments LIKE 'status'");
$col = mysqli_fetch_assoc($result);
echo "<h3>1. Current status ENUM:</h3><pre>" . htmlspecialchars($col['Type']) . "</pre>";

// 2. Auto-migrate if needed
if (strpos($col['Type'], 'reserved') === false) {
    $sql = "ALTER TABLE enrollments MODIFY COLUMN status ENUM('reserved','confirmed','ongoing','dropped','completed') DEFAULT 'reserved'";
    if (mysqli_query($con, $sql)) {
        echo "<p class='ok'>✅ Migration applied — 'reserved' added to ENUM.</p>";
    } else {
        echo "<p class='err'>❌ Migration failed: " . mysqli_error($con) . "</p>";
    }
} else {
    echo "<p class='ok'>✅ ENUM already has 'reserved'.</p>";
}

// 3. Show all enrollments for this student
echo "<h3>2. All enrollments for student_id=$student_id:</h3>";
$r = mysqli_query($con, "SELECT e.enrollment_id, e.status, e.class_id, s.subject_code 
                          FROM enrollments e 
                          JOIN classes c ON e.class_id = c.class_id
                          JOIN subjects s ON c.subject_id = s.subject_id
                          WHERE e.student_id = $student_id");
if (!$r || mysqli_num_rows($r) === 0) {
    echo "<p class='err'>No enrollments found for this student.</p>";
} else {
    echo "<table border='1' cellpadding='6'><tr><th>enrollment_id</th><th>status</th><th>class_id</th><th>subject_code</th></tr>";
    while ($row = mysqli_fetch_assoc($r)) {
        $color = $row['status'] === 'reserved' ? 'background:#fffbeb' : '';
        echo "<tr style='$color'><td>{$row['enrollment_id']}</td><td><b>{$row['status']}</b></td><td>{$row['class_id']}</td><td>{$row['subject_code']}</td></tr>";
    }
    echo "</table>";
}

// 4. Test insert of a reserved row (dry run — just check if it would work)
echo "<h3>3. ENUM values after migration:</h3>";
$result2 = mysqli_query($con, "SHOW COLUMNS FROM enrollments LIKE 'status'");
$col2 = mysqli_fetch_assoc($result2);
echo "<pre>" . htmlspecialchars($col2['Type']) . "</pre>";

echo "<br><a href='admin_manual_enroll.php?student_id=$student_id'>← Back to Manual Enroll</a>";
?>
