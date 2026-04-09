<?php
// Debug script to check enrollment status updates
include("connection.php");

// Get the enrollment_id from URL parameter
$enrollment_id = (int)($_GET['enrollment_id'] ?? 0);

if ($enrollment_id > 0) {
    echo "<h2>Checking Enrollment ID: $enrollment_id</h2>";
    
    // Check current status
    $result = mysqli_query($con, "SELECT * FROM enrollments WHERE enrollment_id = $enrollment_id");
    $enrollment = mysqli_fetch_assoc($result);
    
    if ($enrollment) {
        echo "<h3>Current Enrollment Data:</h3>";
        echo "<pre>";
        print_r($enrollment);
        echo "</pre>";
        
        // Try to update it
        echo "<h3>Attempting to update status to 'dropped'...</h3>";
        $update = mysqli_query($con, "UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = $enrollment_id");
        
        if ($update) {
            echo "<p style='color:green;'>✓ Update query executed successfully</p>";
            echo "<p>Affected rows: " . mysqli_affected_rows($con) . "</p>";
            
            // Check again
            $result2 = mysqli_query($con, "SELECT * FROM enrollments WHERE enrollment_id = $enrollment_id");
            $enrollment2 = mysqli_fetch_assoc($result2);
            
            echo "<h3>After Update:</h3>";
            echo "<pre>";
            print_r($enrollment2);
            echo "</pre>";
        } else {
            echo "<p style='color:red;'>✗ Update failed: " . mysqli_error($con) . "</p>";
        }
    } else {
        echo "<p style='color:red;'>Enrollment not found!</p>";
    }
} else {
    echo "<h2>All Enrollments with drop_requested status:</h2>";
    $result = mysqli_query($con, "SELECT e.*, s.subject_code, s.subject_name 
                                   FROM enrollments e 
                                   LEFT JOIN classes c ON e.class_id = c.class_id
                                   LEFT JOIN subjects s ON c.subject_id = s.subject_id
                                   WHERE e.status = 'drop_requested'");
    
    if (mysqli_num_rows($result) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Enrollment ID</th><th>Student ID</th><th>Subject</th><th>Status</th><th>Action</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['enrollment_id'] . "</td>";
            echo "<td>" . $row['student_id'] . "</td>";
            echo "<td>" . ($row['subject_code'] ?? 'N/A') . " - " . ($row['subject_name'] ?? 'N/A') . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td><a href='?enrollment_id=" . $row['enrollment_id'] . "'>Debug This</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No drop requests found.</p>";
    }
    
    echo "<hr><h2>All Enrollments (All Statuses):</h2>";
    $all = mysqli_query($con, "SELECT e.*, s.subject_code, s.subject_name 
                                FROM enrollments e 
                                LEFT JOIN classes c ON e.class_id = c.class_id
                                LEFT JOIN subjects s ON c.subject_id = s.subject_id
                                ORDER BY e.student_id, e.enrollment_id DESC
                                LIMIT 50");
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Enrollment ID</th><th>Student ID</th><th>Subject</th><th>Status</th></tr>";
    while ($row = mysqli_fetch_assoc($all)) {
        $color = '';
        if ($row['status'] == 'drop_requested') $color = 'background:#ffcccc;';
        if ($row['status'] == 'dropped') $color = 'background:#cccccc;';
        if ($row['status'] == 'confirmed') $color = 'background:#ccffcc;';
        
        echo "<tr style='$color'>";
        echo "<td>" . $row['enrollment_id'] . "</td>";
        echo "<td>" . $row['student_id'] . "</td>";
        echo "<td>" . ($row['subject_code'] ?? 'N/A') . " - " . ($row['subject_name'] ?? 'N/A') . "</td>";
        echo "<td><strong>" . $row['status'] . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
