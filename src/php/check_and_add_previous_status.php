<?php
// Script to check and add previous_status column if it doesn't exist
include("connection.php");

// Check if previous_status column exists
$check = mysqli_query($con, "SHOW COLUMNS FROM enrollments LIKE 'previous_status'");

if (mysqli_num_rows($check) == 0) {
    echo "previous_status column does NOT exist. Adding it now...<br>";
    
    $add_column = mysqli_query($con, "ALTER TABLE enrollments ADD COLUMN previous_status VARCHAR(20) DEFAULT NULL AFTER status");
    
    if ($add_column) {
        echo "<strong style='color:green;'>SUCCESS!</strong> previous_status column added to enrollments table.<br>";
    } else {
        echo "<strong style='color:red;'>ERROR:</strong> " . mysqli_error($con) . "<br>";
    }
} else {
    echo "<strong style='color:green;'>OK!</strong> previous_status column already exists.<br>";
}

// Show current structure
echo "<br><h3>Current enrollments table structure:</h3>";
$columns = mysqli_query($con, "SHOW COLUMNS FROM enrollments");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while ($col = mysqli_fetch_assoc($columns)) {
    $highlight = ($col['Field'] == 'status' || $col['Field'] == 'previous_status') ? 'background:#ffffcc;' : '';
    echo "<tr style='$highlight'>";
    echo "<td>" . $col['Field'] . "</td>";
    echo "<td>" . $col['Type'] . "</td>";
    echo "<td>" . $col['Null'] . "</td>";
    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
