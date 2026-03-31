<?php
// Database Structure Checker
include("../php/connection.php");

echo "<h1>Database Structure Check</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#1a1612;color:#f2f3f2;} table{border-collapse:collapse;margin:20px 0;} th,td{border:1px solid #d4af37;padding:8px;text-align:left;} th{background:#8c1c24;color:#f2f3f2;} h2{color:#d4af37;margin-top:30px;}</style>";

// Check if enrollments table exists
$check = mysqli_query($con, "SHOW TABLES LIKE 'enrollments'");
if (mysqli_num_rows($check) > 0) {
    echo "<h2>✅ ENROLLMENTS TABLE EXISTS</h2>";
    
    // Show structure
    $structure = mysqli_query($con, "DESCRIBE enrollments");
    echo "<table><tr><th>Column Name</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($col = mysqli_fetch_assoc($structure)) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show sample data
    $data = mysqli_query($con, "SELECT * FROM enrollments LIMIT 5");
    if (mysqli_num_rows($data) > 0) {
        echo "<h3>Sample Data (First 5 rows):</h3>";
        echo "<table><tr>";
        $first = true;
        while ($row = mysqli_fetch_assoc($data)) {
            if ($first) {
                foreach (array_keys($row) as $header) {
                    echo "<th>$header</th>";
                }
                echo "</tr><tr>";
                $first = false;
            }
            foreach ($row as $value) {
                echo "<td>$value</td>";
            }
            echo "</tr><tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data in enrollments table yet.</p>";
    }
} else {
    echo "<h2>❌ ENROLLMENTS TABLE DOES NOT EXIST</h2>";
    echo "<p>You need to run: <strong>enrollments_table.sql</strong></p>";
}

// Check other required tables
$required_tables = ['classes', 'subjects', 'faculty', 'blocks', 'block_subjects'];
echo "<h2>Other Required Tables:</h2>";
echo "<ul>";
foreach ($required_tables as $table) {
    $check = mysqli_query($con, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($check) > 0) {
        echo "<li style='color:#4ade80;'>✅ $table - EXISTS</li>";
    } else {
        echo "<li style='color:#ef4444;'>❌ $table - MISSING (run {$table}_table.sql)</li>";
    }
}
echo "</ul>";

echo "<hr><p><a href='admin_students.php' style='color:#d4af37;'>← Back to Students</a></p>";
?>







